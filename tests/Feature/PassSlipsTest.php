<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PassSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassSlipsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('Administrator');
    }

    private function slip(array $overrides = []): array
    {
        return $overrides + [
            'control_no' => 'PS-2026-11-0001',
            'date' => '2026-11-25',
            'inclusive_dates' => ['2026-11-25'],
            'requested_time' => '08:00',
            'purpose' => 'Submit reports',
            'location' => 'Capitol',
            'expected_return_time' => '12:00',
            'vehicle' => 'RP Vehicle',
        ];
    }

    public function test_a_group_pass_slip_takes_several_immediate_supervisors_in_order(): void
    {
        [$ana, $ben, $supervisorA, $supervisorB] = [$this->makeEmployee(), $this->makeEmployee(), $this->makeEmployee(), $this->makeEmployee()];

        $id = $this->actingAs($this->admin)->postJson('/api/pass-slips', $this->slip([
            'employee_ids' => [$ana->id, $ben->id],
            'recommending_approval_employee_ids' => [$supervisorB->id, $supervisorA->id],
        ]))->assertCreated()->json('data.id');

        $slip = PassSlip::findOrFail($id);
        $this->assertSame([$supervisorB->id, $supervisorA->id], $slip->recommending_approval_employee_ids);
        $this->assertSame($supervisorB->id, $slip->recommending_approval_employee_id);

        $listed = collect($this->actingAs($this->admin)->getJson('/api/pass-slips')->assertOk()->json())->firstWhere('id', $id);
        $this->assertSame([$supervisorB->id, $supervisorA->id], $listed['recommending_approval_employee_ids']);
        $this->assertArrayNotHasKey('recommending_approvals', $listed);

        // Down to one supervisor
        $this->actingAs($this->admin)->putJson("/api/pass-slips/{$id}", $this->slip([
            'employee_ids' => [$ana->id, $ben->id],
            'recommending_approval_employee_ids' => [$supervisorA->id],
        ]))->assertOk();
        $this->assertSame([$supervisorA->id], $slip->fresh()->recommending_approval_employee_ids);
        $this->assertSame($supervisorA->id, $slip->fresh()->recommending_approval_employee_id);
    }

    public function test_the_one_supervisor_field_still_works(): void
    {
        [$ana, $supervisor] = [$this->makeEmployee(), $this->makeEmployee()];

        $id = $this->actingAs($this->admin)->postJson('/api/pass-slips', $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_id' => $supervisor->id,
        ]))->assertCreated()->json('data.id');

        $this->assertSame([$supervisor->id], PassSlip::findOrFail($id)->recommending_approval_employee_ids);
    }

    public function test_changing_a_later_supervisor_is_logged_and_the_profile_api_keeps_the_others(): void
    {
        [$ana, $first, $second, $third] = [$this->makeEmployee(), $this->makeEmployee(['name' => 'First Sup']), $this->makeEmployee(['name' => 'Second Sup']), $this->makeEmployee(['name' => 'Third Sup'])];

        $id = $this->actingAs($this->admin)->postJson('/api/pass-slips', $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_ids' => [$first->id, $second->id],
        ]))->assertCreated()->json('data.id');

        $this->actingAs($this->admin)->putJson("/api/pass-slips/{$id}", $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_ids' => [$first->id, $third->id],
        ]))->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated']);
        $this->assertTrue(\App\Models\ActivityLog::latest('id')->get()->contains(
            fn ($log) => str_contains(json_encode($log->properties), 'First Sup, Third Sup')
        ));

        // Only the one supervisor field, unchanged (as the Profile filing API sends it): the second supervisor stays
        $this->actingAs($this->admin)->putJson("/api/pass-slips/{$id}", $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_id' => $first->id,
            'purpose' => 'Changed purpose',
        ]))->assertOk();
        $this->assertSame([$first->id, $third->id], PassSlip::findOrFail($id)->recommending_approval_employee_ids);
    }

    public function test_the_same_supervisor_cannot_be_named_twice(): void
    {
        [$ana, $supervisor] = [$this->makeEmployee(), $this->makeEmployee()];

        $this->actingAs($this->admin)->postJson('/api/pass-slips', $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_ids' => [$supervisor->id, $supervisor->id],
        ]))->assertStatus(422);
    }

    public function test_an_employee_recommending_a_slip_cannot_be_deleted_and_is_carried_over_on_a_merge(): void
    {
        [$ana, $first, $second, $duplicate] = [$this->makeEmployee(), $this->makeEmployee(), $this->makeEmployee(), $this->makeEmployee()];

        $id = $this->actingAs($this->admin)->postJson('/api/pass-slips', $this->slip([
            'employee_ids' => [$ana->id],
            'recommending_approval_employee_ids' => [$first->id, $duplicate->id],
        ]))->assertCreated()->json('data.id');

        $this->assertNotNull(\App\Support\EmployeeReferences::deleteBlocker($duplicate));

        \App\Support\EmployeeReferences::repoint($duplicate->id, $second->id);
        $this->assertSame([$first->id, $second->id], PassSlip::findOrFail($id)->recommending_approval_employee_ids);
    }
}
