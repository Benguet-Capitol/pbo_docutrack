<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\NoticeOfMeeting;
use App\Models\Office;
use App\Models\PassSlip;
use App\Models\Tardiness;
use App\Models\TravelOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The read-only APIs for other PBO systems (pbo-registryV2): each has its own keys */
class SharedApiTest extends TestCase
{
    use RefreshDatabase;

    private const RECORDS_KEY = 'records-test-key';
    private const LEAVES_KEY = 'leaves-test-key';
    private const LOCATOR_KEY = 'locator-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.records_api.keys' => [self::RECORDS_KEY],
            'services.leaves_api.keys' => [self::LEAVES_KEY],
            'services.locator_api.keys' => [self::LOCATOR_KEY],
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

    public function test_the_summary_carries_the_employees_own_travel_orders_pass_slips_and_tardiness(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799']);
        $other = $this->makeEmployee();
        $this->travelOrder([$employee], '2026-03-02', ['2026-03-02'], 'Baguio City');
        $this->travelOrder([$other], '2026-03-03', ['2026-03-03'], 'Somewhere Else');
        $this->travelOrder([$employee], '2025-03-03', ['2025-03-03'], 'Last Year');
        $this->passSlip([$employee], '2026-04-01', ['2026-04-01'], 'Provincial Capitol');
        $this->undertime($employee, '2026-05-04', 'Clinic');
        $this->undertime($other, '2026-05-04', 'Not mine');

        $response = $this->getJson('/api/v1/leaves/employees/01323001799?year=2026', ['X-API-Key' => self::LEAVES_KEY])->assertOk();

        $response->assertJsonCount(1, 'data.travel_orders')->assertJsonPath('data.travel_orders.0.going_to', 'Baguio City')
            ->assertJsonCount(1, 'data.pass_slips')->assertJsonPath('data.pass_slips.0.location', 'Provincial Capitol')
            ->assertJsonCount(1, 'data.tardiness')->assertJsonPath('data.tardiness.0.reason', 'Clinic')
            ->assertJsonPath('data.tardiness.0.date', '2026-05-04')
            ->assertJsonPath('data.years', [2026, 2025]);
        $this->assertStringNotContainsString('Somewhere Else', $response->getContent());
    }

    public function test_the_locator_api_has_its_own_key_and_a_month_at_most(): void
    {
        $url = '/api/v1/locator?from=2026-11-02&to=2026-11-03';
        $this->getJson($url)->assertUnauthorized();
        $this->getJson($url, ['X-API-Key' => self::LEAVES_KEY])->assertUnauthorized();
        $this->getJson($url, ['X-API-Key' => self::LOCATOR_KEY])->assertOk();

        $this->getJson('/api/v1/locator?from=2026-11-03&to=2026-11-02', ['X-API-Key' => self::LOCATOR_KEY])->assertUnprocessable();
        $this->getJson('/api/v1/locator?from=2026-01-01&to=2026-03-01', ['X-API-Key' => self::LOCATOR_KEY])->assertUnprocessable();
        $this->getJson('/api/v1/locator?from=2026-10-01&to=2026-10-31', ['X-API-Key' => self::LOCATOR_KEY])->assertOk();

        config(['services.locator_api.keys' => []]);
        $this->getJson($url, ['X-API-Key' => self::LOCATOR_KEY])->assertStatus(503);
    }

    public function test_the_locator_follows_the_charts_order_of_rules(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799', 'name' => 'Ana B. Cruz']);
        $this->undertime($employee, '2026-11-02', 'Personal reason');
        Leave::create(['employee_id' => $employee->id, 'control_no' => 'L-1', 'date_of_filing' => '2026-10-01', 'is_half_day' => false, 'type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 3, 'inclusive_dates' => ['2026-11-02 - 2026-11-03']]);
        $this->passSlip([$employee], '2026-11-03', ['2026-11-03', '2026-11-04'], 'Capitol', 'Deliver forms');
        $this->travelOrder([$employee], '2026-11-04', ['2026-11-04 - 2026-11-05'], 'Baguio City', ['Seminar', 'Training']);
        NoticeOfMeeting::create(['employee_id' => $employee->id, 'date' => '2026-11-05', 'time' => '10:00', 'particulars' => 'Ignored: on travel']);
        NoticeOfMeeting::create(['employee_id' => $employee->id, 'date' => '2026-11-06', 'time' => '14:30', 'particulars' => 'Budget review']);
        NoticeOfMeeting::create(['employee_id' => $employee->id, 'date' => '2026-11-06', 'time' => '09:00', 'particulars' => 'Planning']);
        NoticeOfMeeting::create(['employee_id' => $employee->id, 'date' => '2026-11-07', 'time' => '09:00', 'particulars' => 'On a Saturday']);

        $days = $this->getJson('/api/v1/locator?from=2026-11-02&to=2026-11-09', ['X-API-Key' => self::LOCATOR_KEY])->assertOk()->json('data.employees.0.days');

        $this->assertSame(['status' => 'Undertime', 'label' => null, 'text' => '08:00 AM - 12:00 PM', 'meetings' => []], $days['2026-11-02']);
        $this->assertSame(['status' => 'On Leave', 'label' => null, 'text' => 'Leave', 'meetings' => []], $days['2026-11-03']);
        $this->assertSame(['status' => 'On Official Business', 'label' => 'Pass Slip', 'text' => 'Deliver forms at Capitol', 'meetings' => []], $days['2026-11-04']);
        $this->assertSame(['status' => 'On Official Business', 'label' => 'Travel Order', 'text' => 'Seminar, Training at Baguio City', 'meetings' => []], $days['2026-11-05']);
        $this->assertSame('Present with Meetings', $days['2026-11-06']['status']);
        $this->assertSame([['time' => '09:00 AM', 'particulars' => 'Planning'], ['time' => '02:30 PM', 'particulars' => 'Budget review']], $days['2026-11-06']['meetings']);
        $this->assertSame('Off Day', $days['2026-11-07']['status']);
        $this->assertSame('Off Day', $days['2026-11-08']['status']);
        $this->assertSame('Present', $days['2026-11-09']['status']);
    }

    public function test_the_locator_shows_a_half_day_as_a_half_day(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799']);
        $leave = fn (string $no, string $type, string $date, string $period) => Leave::create(['employee_id' => $employee->id, 'control_no' => $no, 'date_of_filing' => '2026-10-01', 'is_half_day' => true, 'half_day_period' => $period, 'type_of_leave' => $type, 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => [$date]]);
        $leave('L-1', 'Sick Leave', '2026-11-03', 'AM');
        $leave('L-2', 'Sick Leave', '2026-11-04', 'AM');
        $leave('L-3', 'Vacation Leave', '2026-11-04', 'PM');

        $days = $this->getJson('/api/v1/locator?from=2026-11-03&to=2026-11-04', ['X-API-Key' => self::LOCATOR_KEY])->assertOk()->json('data.employees.0.days');

        $this->assertSame(['status' => 'On Leave', 'label' => 'Half Day', 'text' => 'Leave (AM)', 'meetings' => []], $days['2026-11-03']);
        $this->assertSame(['status' => 'On Leave', 'label' => null, 'text' => 'Leave', 'meetings' => []], $days['2026-11-04'], 'Morning and afternoon together are the whole day.');
    }

    public function test_the_locator_does_not_show_a_leaves_off_days_as_on_leave(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799']);
        Leave::create(['employee_id' => $employee->id, 'control_no' => 'L-1', 'date_of_filing' => '2026-10-01', 'is_half_day' => false, 'type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 2, 'inclusive_dates' => ['2026-11-04 - 2026-11-13'], 'off_days' => ['Friday', 'Saturday', 'Sunday']]);
        $this->passSlip([$employee], '2026-11-13', ['2026-11-13'], 'Capitol', 'Deliver forms');

        $days = $this->getJson('/api/v1/locator?from=2026-11-04&to=2026-11-13', ['X-API-Key' => self::LOCATOR_KEY])->assertOk()->json('data.employees.0.days');

        $this->assertSame('On Leave', $days['2026-11-05']['status'], 'Thursday is a working day of the leave.');
        $this->assertSame('Off Day', $days['2026-11-06']['status'], 'Friday is an off day of the leave.');
        $this->assertSame('On Official Business', $days['2026-11-13']['status'], 'A record on an off day still shows.');
    }

    public function test_the_locator_hides_private_details_and_skips_disapproved_leaves(): void
    {
        $employee = $this->makeEmployee(['employee_id' => '01323001799']);
        $this->undertime($employee, '2026-11-02', 'Private medical reason');
        Leave::create(['employee_id' => $employee->id, 'control_no' => 'L-1', 'date_of_filing' => '2026-10-01', 'is_half_day' => false, 'type_of_leave' => 'Sick Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-11-03']]);
        Leave::create(['employee_id' => $employee->id, 'control_no' => 'L-2', 'date_of_filing' => '2026-10-01', 'is_half_day' => false, 'type_of_leave' => 'Vacation Leave', 'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-11-04'], 'recommendation' => 'disapproval']);

        $response = $this->getJson('/api/v1/locator?from=2026-11-02&to=2026-11-04', ['X-API-Key' => self::LOCATOR_KEY])->assertOk();

        $this->assertSame('Present', $response->json('data.employees.0.days.2026-11-04.status'), 'A disapproved leave did not happen.');
        foreach (['Private medical', 'Sick Leave', 'Vacation Leave', $employee->id.'"'] as $private) {
            $this->assertStringNotContainsString($private, str_replace('"employee_id":"01323001799"', '', $response->getContent()));
        }
    }

    public function test_the_locator_roster_is_pbo_staff_with_records_budget_officer_first_then_by_last_name(): void
    {
        $officer = $this->makeEmployee(['name' => 'Zed A. Young', 'position_title' => 'Provincial Government Department Head', 'parenthetical_title' => 'Provincial Budget Officer']);
        $b = $this->makeEmployee(['name' => 'Ana B. Santos Jr.']);
        $a = $this->makeEmployee(['name' => 'Carl D. Reyes']);
        $onlyPassSlip = $this->makeEmployee(['name' => 'Dan E. Tan']);
        $this->makeEmployee(['name' => 'No Records At All']);
        Employee::create(['employee_id' => 'X-1', 'name' => 'Other Office', 'position_title' => 'Clerk', 'office_id' => Office::create(['office_abbreviation' => 'OTH', 'office_name' => 'Other Office'])->id]);
        foreach ([$officer, $b, $a] as $person) {
            $this->undertime($person, '2026-01-05', 'Long ago');
        }
        $this->passSlip([$onlyPassSlip], '2026-01-05', ['2026-01-05'], 'Capitol');

        $names = $this->getJson('/api/v1/locator?from=2026-11-02&to=2026-11-02', ['X-API-Key' => self::LOCATOR_KEY])->assertOk()->json('data.employees.*.name');

        $this->assertSame(['Zed A. Young', 'Carl D. Reyes', 'Ana B. Santos Jr.', 'Dan E. Tan'], $names);
    }

    public function test_undertime_shows_on_the_day_it_happened_not_the_day_it_was_filed(): void
    {
        $employee = $this->makeEmployee();
        $this->undertime($employee, '2026-11-03', 'Clinic')->update(['date_filed' => '2026-11-05']);

        $days = $this->getJson('/api/v1/locator?from=2026-11-03&to=2026-11-05', ['X-API-Key' => self::LOCATOR_KEY])->assertOk()->json('data.employees.0.days');

        $this->assertSame('Undertime', $days['2026-11-03']['status']);
        $this->assertSame('Present', $days['2026-11-05']['status']);
    }

    private function travelOrder(array $employees, string $date, array $dates, string $goingTo, array $purpose = ['Official']): TravelOrder
    {
        $order = TravelOrder::create(['control_no' => 'TO-'.uniqid(), 'date' => $date, 'going_to' => $goingTo, 'from_date' => $date, 'to_date' => $date, 'inclusive_dates' => $dates, 'purpose' => $purpose, 'vehicle' => 'Government']);
        $order->employees()->sync(collect($employees)->pluck('id'));

        return $order;
    }

    private function passSlip(array $employees, string $date, array $dates, string $location, string $purpose = 'Official'): PassSlip
    {
        $slip = PassSlip::create(['control_no' => 'PS-'.uniqid(), 'date' => $date, 'inclusive_dates' => $dates, 'requested_time' => '09:00', 'purpose' => $purpose, 'location' => $location, 'vehicle' => 'Government', 'expected_return_time' => '11:00']);
        $slip->employees()->sync(collect($employees)->pluck('id'));

        return $slip;
    }

    private function undertime(Employee $employee, string $date, string $reason): Tardiness
    {
        return Tardiness::create(['control_no' => 'TU-'.uniqid(), 'date_filed' => $date, 'type' => 'Undertime', 'requested_date' => $date, 'employee_id' => $employee->id, 'requested_time' => '08:00', 'reason' => $reason, 'return_time' => '12:00']);
    }

    public function test_an_unknown_employee_is_not_found(): void
    {
        $this->getJson('/api/v1/leaves/employees/does-not-exist', ['X-API-Key' => self::LEAVES_KEY])->assertNotFound();
    }
}
