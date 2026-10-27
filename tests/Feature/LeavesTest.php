<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leaves: who sees and files what (Data Privacy Act), and the rules every application must pass
 * (control numbers, overlapping days, yearly limits).
 */
class LeavesTest extends TestCase
{
    use RefreshDatabase;

    private Employee $samuel;
    private Employee $maria;
    /** Files leaves, sees only their own (no "leaves.view_all") */
    private User $clerk;
    /** Sees and files everyone's leaves */
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->samuel = $this->makeEmployee(['name' => 'Samuel B. Soliman']);
        $this->maria = $this->makeEmployee(['name' => 'Maria C. Santos']);
        $this->makeRole('Leave Clerk', ['leaves.view', 'leaves.create', 'leaves.edit', 'leaves.delete', 'hr_summary.view']);
        $this->clerk = $this->makeUser('Leave Clerk', $this->samuel);
        $this->admin = $this->makeUser('Administrator');
    }

    /** A valid application: one leave type on the given dates (2026, Monday to Friday) */
    private function application(Employee $employee, array $overrides = [], array $entry = []): array
    {
        static $sequence = 0;
        $sequence++;

        return $overrides + [
            'control_no' => sprintf('L-2026-10-%04d', $sequence),
            'employee_id' => $employee->id,
            'date_of_filing' => '2026-10-01',
            'entries' => [$entry + [
                'type_of_leave' => 'Vacation Leave',
                'number_of_working_days_applied_for' => 1,
                'inclusive_dates' => ['2026-10-05'],
            ]],
        ];
    }

    private function fileLeave(User $user, array $application)
    {
        return $this->actingAs($user)->postJson('/api/leaves', $application);
    }

    // ---- Privacy

    public function test_a_user_without_view_all_sees_only_their_own_leaves(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel))->assertCreated();
        $this->fileLeave($this->admin, $this->application($this->maria))->assertCreated();

        $rows = $this->actingAs($this->clerk)->getJson('/api/leaves')->assertOk()->json();

        $this->assertCount(1, $rows);
        $this->assertSame($this->samuel->id, $rows[0]['employee_id']);
    }

    public function test_a_user_with_view_all_sees_everyones_leaves(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel))->assertCreated();
        $this->fileLeave($this->admin, $this->application($this->maria))->assertCreated();

        $this->actingAs($this->admin)->getJson('/api/leaves')->assertOk()->assertJsonCount(2);
    }

    public function test_a_user_without_a_linked_employee_sees_no_leaves(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel))->assertCreated();
        $unlinked = $this->makeUser('Leave Clerk');

        $this->actingAs($unlinked)->getJson('/api/leaves')->assertOk()->assertJsonCount(0);
    }

    public function test_a_user_can_not_file_a_leave_for_another_employee(): void
    {
        $this->fileLeave($this->clerk, $this->application($this->maria))
            ->assertStatus(422)
            ->assertJsonPath('errors.employee_id.0', 'You can only file leaves for yourself.');

        $this->assertSame(0, Leave::count());
    }

    public function test_a_user_can_file_their_own_leave(): void
    {
        $this->fileLeave($this->clerk, $this->application($this->samuel))->assertCreated();

        $this->assertSame(1, Leave::where('employee_id', $this->samuel->id)->count());
    }

    public function test_a_user_can_not_open_edit_or_delete_another_employees_leave(): void
    {
        $this->fileLeave($this->admin, $this->application($this->maria, ['control_no' => 'L-2026-10-9001']))->assertCreated();
        $leave = Leave::firstWhere('control_no', 'L-2026-10-9001');

        $this->actingAs($this->clerk)->get("/api/leaves/{$leave->id}/form")->assertForbidden();
        $this->actingAs($this->clerk)->putJson("/api/leaves/{$leave->id}", $this->application($this->samuel))->assertForbidden();
        $this->actingAs($this->clerk)->deleteJson("/api/leaves/{$leave->id}")->assertForbidden();
        $this->actingAs($this->clerk)->getJson("/api/leaves/employee-salary/{$this->maria->id}")->assertForbidden();

        $this->assertNotSoftDeleted($leave);
    }

    public function test_salaries_are_left_out_for_those_who_can_not_open_leaves(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, ['salary' => 25000]))->assertCreated();
        $this->makeRole('Summary Only', ['hr_summary.view', 'leaves.view_all']);
        $summaryOnly = $this->makeUser('Summary Only');

        $this->actingAs($summaryOnly)->getJson('/api/leaves')->assertOk()->assertJsonPath('0.salary', null);
        $this->actingAs($this->admin)->getJson('/api/leaves')->assertOk()->assertJsonPath('0.salary', '25000.00');
    }

    // ---- Control numbers

    public function test_a_control_number_can_only_be_used_once_even_after_the_leave_is_trashed(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, ['control_no' => 'L-2026-10-0500']))->assertCreated();
        Leave::firstWhere('control_no', 'L-2026-10-0500')->delete();

        $this->fileLeave($this->admin, $this->application($this->maria, ['control_no' => 'L-2026-10-0500']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('control_no', 'errors');
    }

    public function test_an_application_with_several_leave_types_shares_one_control_number(): void
    {
        $application = $this->application($this->samuel, ['control_no' => 'L-2026-10-0600']);
        $application['entries'][] = ['type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-10-06']];

        $this->fileLeave($this->admin, $application)->assertCreated();

        $this->assertSame(['Vacation Leave', 'Sick Leave'], Leave::where('control_no', 'L-2026-10-0600')->orderBy('id')->pluck('type_of_leave')->all());
    }

    // ---- Overlapping days

    public function test_the_same_day_can_not_be_filed_twice(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['inclusive_dates' => ['2026-10-05 - 2026-10-07'], 'number_of_working_days_applied_for' => 3]))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['type_of_leave' => 'Sick Leave', 'inclusive_dates' => ['2026-10-07']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.inclusive_dates', 'errors');
    }

    public function test_morning_and_afternoon_half_days_on_the_same_day_are_allowed(): void
    {
        $half = fn (string $period) => ['is_half_day' => true, 'half_day_period' => $period, 'number_of_working_days_applied_for' => 0.5, 'inclusive_dates' => ['2026-10-08']];

        $this->fileLeave($this->admin, $this->application($this->samuel, [], $half('AM')))->assertCreated();
        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['type_of_leave' => 'Sick Leave'] + $half('PM')))->assertCreated();
        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['type_of_leave' => 'Special Privilege Leave'] + $half('AM')))->assertStatus(422);
    }

    public function test_a_disapproved_leave_frees_its_days(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, ['recommendation' => 'disapproval', 'disapproval_reason' => 'Busy season']))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->samuel))->assertCreated();
    }

    public function test_another_employee_may_be_on_leave_the_same_day(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->maria))->assertCreated();
    }

    // ---- Yearly limits

    public function test_a_yearly_limited_leave_can_not_go_over_its_limit(): void
    {
        $spl = ['type_of_leave' => 'Special Privilege Leave'];
        // 3 days a year: 2 days, then 1 day is fine, a 4th day is not
        $this->fileLeave($this->admin, $this->application($this->samuel, [], $spl + ['inclusive_dates' => ['2026-10-05 - 2026-10-06'], 'number_of_working_days_applied_for' => 2]))->assertCreated();
        $this->fileLeave($this->admin, $this->application($this->samuel, [], $spl + ['inclusive_dates' => ['2026-10-12']]))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->samuel, [], $spl + ['inclusive_dates' => ['2026-10-13']]))
            ->assertStatus(422)
            ->assertJsonPath('errors.type_of_leave.0', 'Special Privilege Leave is already exhausted for 2026: all 3 days have been used.');
    }

    public function test_a_disapproved_leave_still_counts_against_the_yearly_limit(): void
    {
        $spl = ['type_of_leave' => 'Special Privilege Leave', 'inclusive_dates' => ['2026-10-05 - 2026-10-07'], 'number_of_working_days_applied_for' => 3];
        $this->fileLeave($this->admin, $this->application($this->samuel, ['recommendation' => 'disapproval', 'disapproval_reason' => 'No'], $spl))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['type_of_leave' => 'Special Privilege Leave', 'inclusive_dates' => ['2026-10-14']]))
            ->assertStatus(422);
    }

    public function test_the_yearly_limit_starts_over_each_year(): void
    {
        $wellness = ['type_of_leave' => 'Wellness Leave', 'inclusive_dates' => ['2026-10-05 - 2026-10-09'], 'number_of_working_days_applied_for' => 5];
        $this->fileLeave($this->admin, $this->application($this->samuel, [], $wellness))->assertCreated();

        $this->fileLeave($this->admin, $this->application($this->samuel, ['date_of_filing' => '2027-01-04'], ['type_of_leave' => 'Wellness Leave', 'inclusive_dates' => ['2027-01-11']]))
            ->assertCreated();
    }

    public function test_editing_an_application_does_not_count_its_own_days_twice(): void
    {
        $spl = ['type_of_leave' => 'Special Privilege Leave', 'inclusive_dates' => ['2026-10-05 - 2026-10-07'], 'number_of_working_days_applied_for' => 3];
        $application = $this->application($this->samuel, ['control_no' => 'L-2026-10-0700'], $spl);
        $this->fileLeave($this->admin, $application)->assertCreated();
        $leave = Leave::firstWhere('control_no', 'L-2026-10-0700');

        $application['entries'][0]['id'] = $leave->id;
        $application['entries'][0]['purpose'] = 'Family matter';
        $this->actingAs($this->admin)->putJson("/api/leaves/{$leave->id}", $application)->assertOk();
    }

    // ---- Form rules

    public function test_a_half_day_needs_am_or_pm(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['is_half_day' => true, 'number_of_working_days_applied_for' => 0.5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.half_day_period', 'errors');
    }

    public function test_a_date_range_can_not_end_before_it_starts(): void
    {
        $this->fileLeave($this->admin, $this->application($this->samuel, [], ['inclusive_dates' => ['2026-10-09 - 2026-10-05']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('entries.0.inclusive_dates', 'errors');
    }
}
