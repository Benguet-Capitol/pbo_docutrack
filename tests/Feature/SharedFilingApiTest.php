<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Office;
use App\Models\PassSlip;
use App\Models\Tardiness;
use App\Models\TimeSlip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The Filing API: PBO staff file and edit their own leaves, pass slips, time slips and tardiness from pbo-registryV2 */
class SharedFilingApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'filing-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.filing_api.keys' => [self::KEY], 'services.leaves_api.keys' => ['leaves-test-key']]);
    }

    public function test_it_needs_its_own_key(): void
    {
        $employee = $this->makeEmployee();

        $this->getJson("/api/v1/filing/employees/{$employee->employee_id}")->assertUnauthorized();
        $this->getJson("/api/v1/filing/employees/{$employee->employee_id}", ['X-API-Key' => 'leaves-test-key'])->assertUnauthorized();
        $this->api('get', $employee)->assertOk();
    }

    public function test_only_pbo_staff_still_with_the_province_can_file(): void
    {
        $other = Employee::create(['employee_id' => 'X-1', 'name' => 'Other Office', 'position_title' => 'Clerk', 'office_id' => Office::create(['office_abbreviation' => 'OTH', 'office_name' => 'Other Office'])->id]);
        $detailed = Employee::create(['employee_id' => 'X-2', 'name' => 'Detailed', 'position_title' => 'Clerk', 'office_id' => $other->office_id, 'detailed_to_pbo' => true]);
        $separated = $this->makeEmployee(['separated_at' => now()]);

        $this->api('get', $other)->assertNotFound();
        $this->api('get', $separated)->assertNotFound();
        $this->api('get', $detailed)->assertOk();
    }

    public function test_the_records_carry_the_leave_signatory_choices_with_their_defaults(): void
    {
        $employee = $this->makeEmployee();
        $budgetOfficer = $this->makeEmployee(['position_title' => Employee::TITLE_PBO]);
        Office::forceCreate(['id' => Employee::PHRMDO_OFFICE_ID, 'office_abbreviation' => 'PHRMDO', 'office_name' => 'Human Resource']);
        $hrmdo = $this->makeEmployee(['position_title' => Employee::TITLE_PHRMDO, 'office_id' => Employee::PHRMDO_OFFICE_ID]);
        $administrator = Employee::create(['employee_id' => 'X-9', 'name' => 'Administrator', 'position_title' => Employee::TITLE_PA, 'office_id' => $hrmdo->office_id]);

        $choices = $this->api('get', $employee)->assertOk()->json('data.leave_signatories.signatories');

        $this->assertSame(['employee_id' => $hrmdo->id, 'title' => Employee::TITLE_PHRMDO], $choices['certified_by']['default']);
        $this->assertEqualsCanonicalizing([$hrmdo->id, $administrator->id], $choices['certified_by']['candidates']);
        $this->assertSame(['employee_id' => $budgetOfficer->id, 'title' => Employee::TITLE_PBO], $choices['recommended_by']['default']);
        // 7.B: the Provincial Budget Officer by default, or the Provincial Governor (OIC / Acting), so anyone can be named
        $this->assertNull($choices['recommended_by']['candidates']);
        $this->assertContains('Acting ' . Employee::TITLE_GOVERNOR, $choices['recommended_by']['titles']);
        $this->assertSame(['employee_id' => $administrator->id, 'title' => Employee::TITLE_PA], $choices['approved_by']['default']);
        $this->assertNull($choices['approved_by']['candidates']);
        $this->assertContains('OIC, ' . Employee::TITLE_GOVERNOR, $choices['approved_by']['titles']);
    }

    public function test_a_department_heads_leave_is_recommended_and_approved_by_the_governor_and_an_acting_head_can_choose_it(): void
    {
        $governor = $this->makeEmployee(['position_title' => Employee::TITLE_GOVERNOR, 'office_id' => Office::create(['office_abbreviation' => 'PGO', 'office_name' => 'Governor'])->id]);
        $head = $this->makeEmployee(['position_title' => 'Provincial Government Department Head', 'parenthetical_title' => Employee::TITLE_PBO]);
        $staff = $this->makeEmployee();

        $choices = $this->api('get', $head)->assertOk()->json('data.leave_signatories');
        $this->assertTrue($choices['department_head']);
        $this->assertSame(['employee_id' => $governor->id, 'title' => Employee::TITLE_GOVERNOR], $choices['signatories']['recommended_by']['default']);
        $this->assertNotContains(Employee::TITLE_PBO, $choices['signatories']['recommended_by']['titles']);
        $this->assertSame(['employee_id' => $governor->id, 'title' => Employee::TITLE_GOVERNOR], $choices['signatories']['approved_by']['default']);

        // Staff who are OIC / Acting department head tick the box: both go to the Provincial Governor
        $choices = $this->api('get', $staff)->json('data.leave_signatories');
        $this->assertFalse($choices['department_head']);
        $this->assertSame(['employee_id' => $governor->id, 'title' => Employee::TITLE_GOVERNOR], $choices['acting_head']['recommended_by']);
        $this->api('post', $staff, 'leaves', $this->leave() + ['recommended_by_employee_id' => $governor->id, 'recommended_by_title' => 'Acting ' . Employee::TITLE_GOVERNOR,
            'approved_by_employee_id' => $governor->id, 'approved_by_title' => Employee::TITLE_GOVERNOR])->assertCreated();
        $this->assertSame('Acting ' . Employee::TITLE_GOVERNOR, Leave::where('employee_id', $staff->id)->sole()->recommended_by_title);
    }

    public function test_a_leave_gets_the_next_control_number_the_employees_salary_for_approval_and_no_signatories(): void
    {
        $employee = $this->makeEmployee(['monthly_salary' => 30000]);
        Leave::create(['control_no' => 'L-' . now()->year . '-01-0007', 'employee_id' => $this->makeEmployee()->id, 'date_of_filing' => now()->startOfYear(), 'type_of_leave' => 'Sick Leave',
            'number_of_working_days_applied_for' => 1, 'inclusive_dates' => [now()->startOfYear()->toDateString()], 'is_half_day' => false]);

        $this->api('post', $employee, 'leaves', $this->leave() + ['employee_id' => 999], ['X-Acting-User' => 'Juan Dela Cruz'])->assertCreated();

        $leave = Leave::where('employee_id', $employee->id)->sole();
        $this->assertSame(sprintf('L-%d-%s-0008', now()->year, now()->format('m')), $leave->control_no);
        $this->assertSame('30000.00', $leave->salary);
        $this->assertSame('approval', $leave->recommendation);
        $this->assertNull($leave->approved_by_employee_id);
        $this->assertSame('Juan Dela Cruz (PBO Registry)', ActivityLog::where('module', 'Leaves')->latest('id')->value('user_name'));
    }

    public function test_one_application_holds_several_leave_types_each_with_several_dates_and_its_off_days(): void
    {
        $employee = $this->makeEmployee();

        $this->api('post', $employee, 'leaves', $this->leave(
            ['inclusive_dates' => ['2026-12-01 - 2026-12-02', '2026-12-04'], 'number_of_working_days_applied_for' => 3],
            ['type_of_leave' => 'Sick Leave', 'inclusive_dates' => ['2026-12-10'], 'number_of_working_days_applied_for' => 1, 'off_days' => ['Friday', 'Saturday', 'Sunday'], 'in_hospital' => false, 'illness' => 'Flu'],
        ))->assertCreated();

        $rows = Leave::orderBy('id')->get();
        $this->assertSame(['Vacation Leave', 'Sick Leave'], $rows->pluck('type_of_leave')->all());
        $this->assertSame(1, $rows->pluck('control_no')->unique()->count());
        $this->assertSame(['2026-12-01 - 2026-12-02', '2026-12-04'], $rows[0]->inclusive_dates);
        $this->assertSame(['Friday', 'Saturday', 'Sunday'], $rows[1]->off_days);
        $this->assertSame(2, count($this->api('get', $employee, '', [], [], 2026)->json('data.leaves.0.entries')));
    }

    public function test_the_leave_rules_still_apply(): void
    {
        $employee = $this->makeEmployee();
        $this->api('post', $employee, 'leaves', $this->leave())->assertCreated();

        $this->api('post', $employee, 'leaves', $this->leave())->assertStatus(422)->assertJsonStructure(['errors' => ['entries.0.inclusive_dates']]);
        $this->api('post', $employee, 'leaves', $this->leave(['type_of_leave' => 'Others']))->assertStatus(422)->assertJsonStructure(['errors' => ['entries.0.type_of_leave']]);
    }

    public function test_a_leave_stays_editable_and_a_type_can_be_dropped(): void
    {
        $employee = $this->makeEmployee();
        $this->api('post', $employee, 'leaves', $this->leave([], ['type_of_leave' => 'Sick Leave', 'inclusive_dates' => ['2026-12-10'], 'number_of_working_days_applied_for' => 1]))->assertCreated();
        $vacation = Leave::where('type_of_leave', 'Vacation Leave')->sole();

        $this->api('put', $employee, "leaves/{$vacation->control_no}", $this->leave(['id' => $vacation->id, 'inclusive_dates' => ['2026-12-08'], 'number_of_working_days_applied_for' => 1]))->assertOk();
        $this->assertSame(['2026-12-08'], $vacation->fresh()->inclusive_dates);
        $this->assertSame(['Vacation Leave'], Leave::pluck('type_of_leave')->all());
        $this->assertTrue($this->api('get', $employee, '', [], [], 2026)->json('data.leaves.0.editable'));
    }

    public function test_a_leave_can_be_recommended_for_disapproval_with_a_reason(): void
    {
        $employee = $this->makeEmployee();

        $this->api('post', $employee, 'leaves', $this->leave() + ['recommendation' => 'disapproval'])->assertStatus(422)->assertJsonStructure(['errors' => ['disapproval_reason']]);
        $this->api('post', $employee, 'leaves', $this->leave() + ['recommendation' => 'disapproval', 'disapproval_reason' => 'Year-end deadlines'])->assertCreated();
        $leave = Leave::sole();
        $this->assertSame(['disapproval', 'Year-end deadlines'], [$leave->recommendation, $leave->disapproval_reason]);

        // Back to For approval clears the reason
        $this->api('put', $employee, "leaves/{$leave->control_no}", $this->leave(['id' => $leave->id]) + ['recommendation' => 'approval', 'disapproval_reason' => 'ignored'])->assertOk();
        $this->assertSame(['approval', null], [$leave->fresh()->recommendation, $leave->fresh()->disapproval_reason]);
        $this->assertSame('approval', $this->api('get', $employee, '', [], [], 2026)->json('data.leaves.0.recommendation'));
    }

    public function test_another_employees_records_are_not_reachable(): void
    {
        $employee = $this->makeEmployee();
        $other = $this->makeEmployee();
        $this->api('post', $other, 'time-slips', ['date' => '2026-11-05', 'time_in_am' => '08:00', 'reason' => 'Forgot to log in'])->assertCreated();

        $this->api('put', $employee, 'time-slips/' . TimeSlip::sole()->id, ['date' => '2026-11-05', 'time_in_am' => '08:00', 'reason' => 'Changed'])->assertNotFound();
    }

    public function test_a_pass_slip_takes_several_dates_a_supervisor_and_an_approving_pbo(): void
    {
        $employee = $this->makeEmployee();
        $supervisor = $this->makeEmployee();
        $officer = $this->makeEmployee();

        $this->api('post', $employee, 'pass-slips', $this->passSlip(['inclusive_dates' => ['2026-11-05', '2026-11-06'], 'employee_ids' => [$supervisor->id],
            'recommending_approval_employee_id' => $supervisor->id, 'approved_by_employee_id' => $officer->id, 'approved_by_title' => 'OIC, Provincial Budget Officer']))->assertCreated();

        $slip = PassSlip::with('employees')->sole();
        $this->assertSame([$employee->id], $slip->employees->pluck('id')->all());
        $this->assertSame(['2026-11-05', '2026-11-06'], $slip->inclusive_dates);
        $this->assertSame('PS-2026-11-0001', $slip->control_no);
        $this->assertSame([$supervisor->id, $officer->id, 'OIC, Provincial Budget Officer'], [$slip->recommending_approval_employee_id, $slip->approved_by_employee_id, $slip->approved_by_title]);

        // Straight to the Provincial Budget Officer: no supervisor; slips stay editable
        $this->api('put', $employee, "pass-slips/{$slip->id}", $this->passSlip(['recommending_approval_employee_id' => null]))->assertOk();
        $this->assertNull($slip->fresh()->recommending_approval_employee_id);
    }

    public function test_signatories_must_be_pbo_staff_and_not_the_employee(): void
    {
        $employee = $this->makeEmployee();
        $outsider = Employee::create(['employee_id' => 'X-1', 'name' => 'Other Office', 'position_title' => 'Clerk', 'office_id' => Office::create(['office_abbreviation' => 'OTH', 'office_name' => 'Other Office'])->id]);

        $this->api('post', $employee, 'time-slips', ['date' => '2026-11-05', 'time_in_am' => '08:00', 'reason' => 'x', 'certified_by_employee_id' => $outsider->id])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['certified_by_employee_id']]);
        $this->api('post', $employee, 'tardiness', ['type' => 'Undertime', 'requested_date' => '2026-11-05', 'requested_time' => '15:00', 'reason' => 'x', 'supervisor_employee_id' => $employee->id])
            ->assertStatus(422);
        $this->api('post', $employee, 'pass-slips', $this->passSlip(['approved_by_employee_id' => $outsider->id]))->assertStatus(422);
        $this->assertSame(0, TimeSlip::count() + Tardiness::count() + PassSlip::count());
    }

    public function test_a_group_pass_slip_is_changed_in_docutrack(): void
    {
        $employee = $this->makeEmployee();
        $slip = PassSlip::create(['control_no' => 'PS-2026-11-0001', 'date' => '2026-11-05', 'inclusive_dates' => ['2026-11-05'], 'requested_time' => '09:00', 'purpose' => 'Group',
            'location' => 'Capitol', 'expected_return_time' => '10:00', 'vehicle' => 'RP Vehicle']);
        $slip->employees()->attach([$employee->id, $this->makeEmployee()->id]);

        $this->api('put', $employee, "pass-slips/{$slip->id}", $this->passSlip())->assertStatus(409);
        $this->assertFalse($this->api('get', $employee, '', [], [], 2026)->json('data.pass_slips.0.editable'));
    }

    public function test_the_budget_officers_own_tardiness_goes_to_the_governor_and_keeps_its_number_and_filing_date(): void
    {
        $officer = $this->makeEmployee(['position_title' => 'Provincial Government Department Head', 'parenthetical_title' => 'Provincial Budget Officer']);
        $governor = Employee::create(['employee_id' => 'G-1', 'name' => 'The Governor', 'position_title' => 'Provincial Governor', 'designation' => 'Provincial Governor', 'office_id' => $officer->office_id]);
        $values = ['type' => 'Undertime', 'requested_date' => '2026-11-05', 'requested_time' => '15:00', 'reason' => 'Clinic', 'approved_by_employee_id' => $officer->id];

        $this->api('post', $officer, 'tardiness', $values)->assertCreated();
        $record = Tardiness::sole();
        $this->assertSame([$governor->id, null], [$record->supervisor_employee_id, $record->approved_by_employee_id]);

        $this->api('put', $officer, "tardiness/{$record->id}", ['requested_time' => '16:00'] + $values)->assertOk();
        $fresh = $record->fresh();
        $this->assertSame([$record->control_no, $record->date_filed->toDateString(), $governor->id], [$fresh->control_no, $fresh->date_filed->toDateString(), $fresh->supervisor_employee_id]);
    }

    public function test_the_print_link_is_signed_short_lived_and_only_for_the_employees_own_record(): void
    {
        $employee = $this->makeEmployee();
        $this->api('post', $employee, 'time-slips', ['date' => '2026-11-05', 'time_in_am' => '08:00', 'reason' => 'Forgot to log in'])->assertCreated();
        $slip = TimeSlip::sole();

        $path = $this->api('get', $employee, "time-slips/{$slip->id}/print")->assertOk()->json('data.path');
        $this->assertStringStartsWith("/filing-print/time-slips/{$slip->id}?expires=", $path);

        $this->withoutVite()->get($path)->assertOk()->assertSee('FilingPrint', false)->assertSee($slip->control_no, false);
        $this->get("/filing-print/time-slips/{$slip->id}")->assertForbidden();
        $this->get(str_replace("/{$slip->id}?", '/' . ($slip->id + 1) . '?', $path))->assertForbidden();
        $this->api('get', $this->makeEmployee(), "time-slips/{$slip->id}/print")->assertNotFound();

        $this->travel(31)->minutes();
        $this->get($path)->assertForbidden();
    }

    public function test_a_leave_application_prints_as_one_form(): void
    {
        $employee = $this->makeEmployee();
        $this->api('post', $employee, 'leaves', $this->leave([], ['type_of_leave' => 'Sick Leave', 'inclusive_dates' => ['2026-12-10'], 'number_of_working_days_applied_for' => 1]))->assertCreated();
        $controlNo = Leave::value('control_no');

        $path = $this->api('get', $employee, "leaves/{$controlNo}/print")->assertOk()->json('data.path');

        $this->withoutVite()->get($path)->assertOk()->assertSee('Vacation Leave', false)->assertSee('Sick Leave', false);
    }

    /** An application: a Vacation Leave entry (changed by $overrides) and any further entries */
    private function leave(array $overrides = [], array ...$more): array
    {
        return ['entries' => [$overrides + ['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 2, 'is_half_day' => false, 'inclusive_dates' => ['2026-12-01 - 2026-12-02'],
            'within_philippines' => true, 'purpose' => 'Baguio City'], ...$more]];
    }

    private function passSlip(array $overrides = []): array
    {
        return $overrides + ['date' => '2026-11-05', 'requested_time' => '09:00', 'purpose' => 'Submit reports', 'location' => 'Capitol', 'expected_return_time' => '10:00'];
    }

    private function api(string $method, Employee $employee, string $path = '', array $data = [], array $headers = [], ?int $year = null)
    {
        $url = rtrim("/api/v1/filing/employees/{$employee->employee_id}/{$path}", '/') . ($year ? "?year={$year}" : '');

        return $this->{"{$method}Json"}($url, ...($method === 'get' ? [$headers + ['X-API-Key' => self::KEY]] : [$data, $headers + ['X-API-Key' => self::KEY]]));
    }
}
