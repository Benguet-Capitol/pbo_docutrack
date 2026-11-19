<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Office;
use App\Models\Employee;
use App\Models\Tardiness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The profile, as on pbo-registryV2: users change their own name, username and password; PBO staff linked to an employee
 * see their leaves and file / edit their own leaves and slips. Accounts are still deleted only by administrators.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Profile/Edit')->where('leaves', null)->where('filings', null)->where('employee', null));
    }

    public function test_users_can_change_their_own_name_and_username(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $this->actingAs($user)->from('/profile')
            ->patch('/profile', ['name' => 'New Name', 'username' => 'new.username'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('new.username', $user->fresh()->username);
    }

    public function test_the_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')
            ->patch('/profile', ['name' => $user->name, 'username' => 'taken'])
            ->assertSessionHasErrors('username');
    }

    public function test_users_can_not_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors('profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->from('/register')
            ->post('/register', ['name' => 'Someone', 'username' => 'someone', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('registration');

        $this->assertNull(User::where('username', 'someone')->first());
    }

    public function test_pbo_staff_see_their_employee_leaves_and_filings(): void
    {
        $employee = $this->makeEmployee(['name' => 'Juan Dela Cruz']);
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->get('/profile')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.name', 'Juan Dela Cruz')
                ->missing('leaves')
                ->loadDeferredProps('filings', fn (Assert $reload) => $reload
                    ->where('leaves.summary.year', now()->year)
                    ->where('filings.records.year', now()->year)));
    }

    public function test_others_get_no_filings_and_can_not_file(): void
    {
        $other = Employee::create(['employee_id' => 'X-1', 'name' => 'Other Office', 'position_title' => 'Clerk',
            'office_id' => Office::create(['office_abbreviation' => 'OTH', 'office_name' => 'Other Office'])->id]);
        $user = User::factory()->create(['employee_id' => $other->id]);

        $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $page) => $page->where('filings', null));
        $this->actingAs($user)->post('/profile/filings/tardiness', ['values' => $this->tardiness()])->assertForbidden();
        $this->assertSame(0, Tardiness::count());
    }

    public function test_pbo_staff_file_and_edit_their_own_tardiness(): void
    {
        $employee = $this->makeEmployee();
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->from('/profile')->post('/profile/filings/tardiness', ['values' => $this->tardiness()])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'tardiness']));

        $record = Tardiness::sole();
        $this->assertSame($employee->id, (int) $record->employee_id);
        $this->assertStringStartsWith('TU-' . now()->year . '-', $record->control_no);

        $this->actingAs($user)->from('/profile')->post("/profile/filings/tardiness/{$record->id}", ['values' => $this->tardiness(['reason' => 'Changed'])])
            ->assertSessionHasNoErrors();
        $this->assertSame('Changed', $record->fresh()->reason);
    }

    public function test_a_leave_is_filed_for_the_users_own_employee_with_the_modules_checks(): void
    {
        $employee = $this->makeEmployee();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $leave = ['entries' => [['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 2, 'is_half_day' => false,
            'inclusive_dates' => ['2026-12-01 - 2026-12-02'], 'within_philippines' => true, 'purpose' => 'Baguio City']]];

        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + ['employee_id' => 999]])->assertSessionHasNoErrors();
        $this->assertSame($employee->id, (int) Leave::sole()->employee_id);

        // The same days again: refused by the Leaves module's own overlap check
        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave])->assertSessionHasErrors();
        $this->assertSame(1, Leave::count());
    }

    public function test_a_leave_is_filed_with_an_oic_or_acting_signatory(): void
    {
        $employee = $this->makeEmployee();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $oic = $this->makeEmployee(['name' => 'Officer In Charge']);
        $leave = ['entries' => [['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 1, 'is_half_day' => false,
            'inclusive_dates' => ['2026-12-07'], 'within_philippines' => true, 'purpose' => 'Home']]];

        // The form lists who can sign
        $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('filings', fn (Assert $reload) => $reload->has('filings.leave_signatories.employees')->where('filings.leave_signatories.applicant.id', $employee->id)));

        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + [
            'recommended_by_employee_id' => $oic->id, 'recommended_by_title' => 'OIC, Provincial Budget Officer',
        ]])->assertSessionHasNoErrors();
        $filed = Leave::sole();
        $this->assertSame($oic->id, (int) $filed->recommended_by_employee_id);
        $this->assertSame('OIC, Provincial Budget Officer', $filed->recommended_by_title);

        // An edit that doesn't send them keeps them; a title that isn't the officer's, OIC or Acting is refused
        $this->actingAs($user)->from('/profile')->post("/profile/filings/leaves/{$filed->control_no}", ['values' => $leave])->assertSessionHasNoErrors();
        $this->assertSame('OIC, Provincial Budget Officer', Leave::where('control_no', $filed->control_no)->sole()->recommended_by_title);
        $this->actingAs($user)->from('/profile')->post("/profile/filings/leaves/{$filed->control_no}", ['values' => $leave + ['recommended_by_title' => 'Mayor']])->assertSessionHasErrors();
        $this->assertSame('OIC, Provincial Budget Officer', Leave::where('control_no', $filed->control_no)->sole()->recommended_by_title);
    }

    public function test_a_leave_signatory_must_be_someone_who_can_sign_it(): void
    {
        $user = User::factory()->create(['employee_id' => $this->makeEmployee()->id]);
        $outsider = Employee::create(['employee_id' => 'X-2', 'name' => 'Not PBO', 'position_title' => 'Clerk',
            'office_id' => Office::create(['office_abbreviation' => 'OTH', 'office_name' => 'Other Office'])->id]);
        $leave = ['entries' => [['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 1, 'is_half_day' => false,
            'inclusive_dates' => ['2026-12-08'], 'within_philippines' => true, 'purpose' => 'Home']]];

        // 7.A is certified by the PHRMDO's staff only
        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + [
            'certified_by_employee_id' => $outsider->id, 'certified_by_title' => 'OIC, ' . Employee::TITLE_PHRMDO,
        ]])->assertSessionHasErrors('certified_by_employee_id');
        $this->assertSame(0, Leave::count());
    }

    public function test_staff_may_have_the_governor_recommend_their_leave_but_the_budget_officer_is_the_default(): void
    {
        $staff = $this->makeEmployee(['position_title' => 'Supervising Administrative Officer']); // e.g. OIC department head
        $user = User::factory()->create(['employee_id' => $staff->id]);
        $vice = Employee::create(['employee_id' => 'G-3', 'name' => 'Vice Governor', 'position_title' => 'Provincial Vice Governor',
            'office_id' => Office::create(['office_abbreviation' => 'PGO', 'office_name' => 'Provincial Governor\'s Office'])->id]);
        $leave = ['entries' => [['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 1, 'is_half_day' => false,
            'inclusive_dates' => ['2026-12-10'], 'within_philippines' => true, 'purpose' => 'Home']]];

        $this->assertSame(Employee::TITLE_PBO, Leave::defaultSignatory('recommended_by', $staff)[1]);
        $this->assertContains('Acting Provincial Governor', Leave::signatoryTitles('recommended_by', $staff));
        $this->assertContains('OIC, Provincial Budget Officer', Leave::signatoryTitles('recommended_by', $staff));

        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + [
            'recommended_by_employee_id' => $vice->id, 'recommended_by_title' => 'Acting Provincial Governor',
            'approved_by_employee_id' => $vice->id, 'approved_by_title' => 'Acting Provincial Governor',
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['name' => 'Vice Governor', 'title' => 'Acting Provincial Governor'], Leave::sole()->signatory('recommended_by'));
    }

    public function test_the_governor_recommends_and_approves_a_department_heads_leave(): void
    {
        $head = $this->makeEmployee(['name' => 'Budget Officer', 'position_title' => 'Provincial Government Department Head', 'parenthetical_title' => 'Provincial Budget Officer']);
        $governor = Employee::create(['employee_id' => 'G-1', 'name' => 'The Governor', 'position_title' => 'Provincial Governor', 'designation' => Employee::TITLE_GOVERNOR,
            'office_id' => Office::create(['office_abbreviation' => 'PGO', 'office_name' => 'Provincial Governor\'s Office'])->id]);
        $user = User::factory()->create(['employee_id' => $head->id]);
        $leave = ['entries' => [['type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 1, 'is_half_day' => false,
            'inclusive_dates' => ['2026-12-09'], 'within_philippines' => true, 'purpose' => 'Home']]];

        // By default the Governor signs both
        $this->assertSame([$governor->id, Employee::TITLE_GOVERNOR], [Leave::defaultSignatory('recommended_by', $head)[0]?->id, Leave::defaultSignatory('recommended_by', $head)[1]]);
        $this->assertSame(Employee::TITLE_GOVERNOR, Leave::defaultSignatory('approved_by', $head)[1]);
        $this->assertSame(Employee::TITLE_PBO, Leave::defaultSignatory('recommended_by', $this->makeEmployee())[1]);

        // A Budget Officer's title is not the department head's 7.B; an Acting Governor from another office is
        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + ['recommended_by_title' => 'OIC, Provincial Budget Officer']])
            ->assertSessionHasErrors('recommended_by_title');
        $vice = Employee::create(['employee_id' => 'G-2', 'name' => 'Vice Governor', 'position_title' => 'Provincial Vice Governor', 'office_id' => $governor->office_id]);
        $this->actingAs($user)->from('/profile')->post('/profile/filings/leaves', ['values' => $leave + [
            'recommended_by_employee_id' => $vice->id, 'recommended_by_title' => 'Acting Provincial Governor',
            'approved_by_employee_id' => $vice->id, 'approved_by_title' => 'Acting Provincial Governor',
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['name' => 'Vice Governor', 'title' => 'Acting Provincial Governor'], Leave::sole()->signatory('recommended_by'));
    }

    public function test_users_can_not_edit_or_print_someone_elses_record(): void
    {
        $user = User::factory()->create(['employee_id' => $this->makeEmployee()->id]);
        $theirs = Tardiness::create(['control_no' => 'TU-2026-11-0001', 'date_filed' => '2026-11-05', 'employee_id' => $this->makeEmployee()->id] + $this->tardiness());

        $this->actingAs($user)->from('/profile')->post("/profile/filings/tardiness/{$theirs->id}", ['values' => $this->tardiness(['reason' => 'Mine now'])])
            ->assertSessionHasErrors('filing');
        $this->assertNotSame('Mine now', $theirs->fresh()->reason);

        $this->actingAs($user)->getJson("/profile/filings/tardiness/{$theirs->id}/print")->assertNotFound();
    }

    public function test_the_print_link_is_signed(): void
    {
        $employee = $this->makeEmployee();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $record = Tardiness::create(['control_no' => 'TU-2026-11-0001', 'date_filed' => '2026-11-05', 'employee_id' => $employee->id] + $this->tardiness());

        $url = $this->actingAs($user)->getJson("/profile/filings/tardiness/{$record->id}/print")->assertOk()->json('url');
        $this->assertStringContainsString('signature=', $url);
    }

    private function tardiness(array $overrides = []): array
    {
        return $overrides + ['type' => 'Undertime', 'requested_date' => '2026-11-05', 'requested_time' => '15:00', 'reason' => 'Doctor\'s appointment'];
    }
}
