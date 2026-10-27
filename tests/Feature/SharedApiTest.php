<?php

namespace Tests\Feature;

use App\Models\Leave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The read-only APIs for other PBO systems (pbo-registryV2): each has its own keys */
class SharedApiTest extends TestCase
{
    use RefreshDatabase;

    private const RECORDS_KEY = 'records-test-key';
    private const LEAVES_KEY = 'leaves-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.records_api.keys' => [self::RECORDS_KEY],
            'services.leaves_api.keys' => [self::LEAVES_KEY],
        ]);
    }

    public function test_the_apis_need_a_key(): void
    {
        $this->getJson('/api/v1/records/categories')->assertUnauthorized();
        $this->getJson('/api/v1/leaves/employees/01323001799')->assertUnauthorized();
        $this->getJson('/api/v1/records/categories', ['X-API-Key' => 'wrong'])->assertUnauthorized();
    }

    public function test_an_api_without_keys_is_off(): void
    {
        config(['services.leaves_api.keys' => []]);

        $this->getJson('/api/v1/leaves/employees/01323001799', ['X-API-Key' => self::LEAVES_KEY])->assertStatus(503);
    }

    public function test_a_records_key_can_not_read_leaves(): void
    {
        $this->getJson('/api/v1/records/categories', ['X-API-Key' => self::RECORDS_KEY])->assertOk();
        $this->getJson('/api/v1/leaves/employees/01323001799', ['X-API-Key' => self::RECORDS_KEY])->assertUnauthorized();
    }

    public function test_the_bearer_header_works_too(): void
    {
        $this->getJson('/api/v1/records/categories', ['Authorization' => 'Bearer ' . self::RECORDS_KEY])->assertOk();
    }

    public function test_the_leave_summary_of_an_employee(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799']);
        $other = $this->makeEmployee();
        $row = fn (array $values) => Leave::create($values + [
            'employee_id' => $employee->id,
            'date_of_filing' => '2026-04-22',
            'is_half_day' => false,
        ]);
        $row(['control_no' => 'L-2026-04-0004', 'type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 2, 'inclusive_dates' => ['2026-04-30 - 2026-05-01'], 'salary' => 30000]);
        $row(['control_no' => 'L-2026-01-0001', 'type_of_leave' => 'Mandatory/Forced Leave', 'number_of_working_days_applied_for' => 2, 'inclusive_dates' => ['2026-02-16 - 2026-02-17']]);
        // Disapproved: not a used day, but it still counts against the yearly limit
        $row(['control_no' => 'L-2026-03-0002', 'type_of_leave' => 'Mandatory/Forced Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-03-02'], 'recommendation' => 'disapproval', 'disapproval_reason' => 'Busy']);
        // Another year and another employee: left out
        $row(['control_no' => 'L-2025-05-0001', 'type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2025-05-05'], 'date_of_filing' => '2025-05-05']);
        $row(['control_no' => 'L-2026-04-0010', 'type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 3, 'inclusive_dates' => ['2026-04-06 - 2026-04-08'], 'employee_id' => $other->id]);
        // Trashed: left out
        $row(['control_no' => 'L-2026-06-0005', 'type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-06-01']])->delete();

        $response = $this->getJson('/api/v1/leaves/employees/01323001799?year=2026', ['X-API-Key' => self::LEAVES_KEY])->assertOk();

        $response->assertJsonPath('data.employee.employee_id', '01323001799')
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.years', [2026, 2025])
            ->assertJsonPath('data.total_days_used', 4)
            ->assertJsonPath('data.limited_leaves.0', ['type' => 'Mandatory/Forced Leave', 'limit' => 5, 'used' => 3, 'remaining' => 2])
            ->assertJsonCount(3, 'data.applications');
        $this->assertEquals(
            ['Vacation Leave' => 2, 'Sick Leave' => 0, 'Mandatory/Forced Leave' => 2],
            collect($response->json('data.used_by_type'))->pluck('days', 'type')->all()
        );
        // Personal details that aren't needed never leave
        $this->assertStringNotContainsString('30000', $response->getContent());
        $this->assertStringNotContainsString('Busy', $response->getContent());
    }

    public function test_an_unknown_employee_is_not_found(): void
    {
        $this->getJson('/api/v1/leaves/employees/does-not-exist', ['X-API-Key' => self::LEAVES_KEY])->assertNotFound();
    }
}
