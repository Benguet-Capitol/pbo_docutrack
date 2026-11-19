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
