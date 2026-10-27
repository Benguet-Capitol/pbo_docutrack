<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\HrisEmployeeSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The monthly HRIS sync (employees:sync-hris): adds, updates, and marks employees who left as separated */
class HrisSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.hris.key' => 'test-key', 'services.hris.url' => 'http://hris.test/api/v1']);
        $this->pboOffice();
        DB::table('hris_office_map')->updateOrInsert(['hris_office' => 'Provincial Budget Office'], ['office_id' => Employee::PBO_OFFICE_ID]);
    }

    /** An HRIS record as the feed sends it */
    private function hrisRecord(string $id, string $first, string $last, array $extra = []): array
    {
        return $extra + [
            'employee_id_number' => $id,
            'fname' => $first,
            'mname' => 'Bautista',
            'lname' => $last,
            'position_title' => 'Administrative Assistant II',
            'type' => 'Permanent',
            'office_desc' => 'Provincial Budget Office',
            'salary' => '₱25,000.00',
        ];
    }

    /** A full-size feed (a feed under 100 records marks no one separated): these records plus filler employees */
    private function fakeFeed(array $records, int $filler = 100): void
    {
        for ($i = 1; $i <= $filler; $i++) {
            $records[] = $this->hrisRecord('0199' . str_pad((string) $i, 7, '0', STR_PAD_LEFT), "Filler{$i}", 'Person');
        }
        Http::fake(['hris.test/*' => Http::response(['data' => $records])]);
    }

    private function sync(): void
    {
        app(HrisEmployeeSync::class)->sync(scheduled: true);
    }

    public function test_new_hris_employees_are_added_with_their_salary(): void
    {
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')], filler: 0);

        $this->sync();

        $employee = Employee::firstWhere('employee_id', '01323001737');
        $this->assertSame('Samuel B. Soliman', $employee->name);
        $this->assertSame(Employee::PBO_OFFICE_ID, (int) $employee->office_id);
        $this->assertEquals(25000, (float) $employee->monthly_salary);
    }

    public function test_an_employee_no_longer_in_hris_is_marked_separated(): void
    {
        $stays = $this->makeEmployee(['employee_id' => '01323001737', 'name' => 'Samuel B. Soliman']);
        $left = $this->makeEmployee(['employee_id' => '01323009999', 'name' => 'Retired Person']);
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')]);

        $this->sync();

        $this->assertNull($stays->fresh()->separated_at);
        $this->assertNotNull($left->fresh()->separated_at);
    }

    public function test_employees_added_by_hand_are_never_marked_separated(): void
    {
        // Not an HRIS-style (all digits) ID
        $manual = $this->makeEmployee(['employee_id' => 'MANUAL-1', 'name' => 'Consultant']);
        $this->fakeFeed([]);

        $this->sync();

        $this->assertNull($manual->fresh()->separated_at);
    }

    public function test_a_nearly_empty_feed_marks_no_one_separated(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323009999']);
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')], filler: 10);

        $this->sync();

        $this->assertNull($employee->fresh()->separated_at);
    }

    public function test_an_employee_back_in_hris_is_no_longer_separated(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001737', 'name' => 'Samuel B. Soliman']);
        $employee->forceFill(['separated_at' => now()->subMonth()])->save();
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')], filler: 0);

        $this->sync();

        $this->assertNull($employee->fresh()->separated_at);
    }

    public function test_a_person_with_a_new_hris_id_keeps_their_record(): void
    {
        // Casual (713...) to permanent (013...): the same record, with the new ID
        $employee = $this->makeEmployee(['employee_id' => '71323001737', 'name' => 'Samuel B. Soliman']);
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')]);

        $this->sync();

        $this->assertSame('01323001737', $employee->fresh()->employee_id);
        $this->assertNull($employee->fresh()->separated_at);
        $this->assertSame(1, Employee::where('name', 'Samuel B. Soliman')->count());
    }

    public function test_detailed_to_pbo_is_kept_by_the_sync(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001737', 'name' => 'Samuel B. Soliman', 'office_id' => null, 'detailed_to_pbo' => true]);
        $this->fakeFeed([$this->hrisRecord('01323001737', 'Samuel', 'Soliman')], filler: 0);

        $this->sync();

        $this->assertTrue((bool) $employee->fresh()->detailed_to_pbo);
    }

    public function test_hris_being_unreachable_changes_nothing(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323009999']);
        Http::fake(['hris.test/*' => Http::response('Server error', 500)]);

        $this->expectException(\RuntimeException::class);
        try {
            $this->sync();
        } finally {
            $this->assertNull($employee->fresh()->separated_at);
        }
    }
}
