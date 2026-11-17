<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Record;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The navbar search: only modules the user can open, with their pages' own limits (own leaves, Administrative records),
 * never Trash, and links that open the module's list on the record.
 */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private function search(User $user, string $q): array
    {
        return $this->actingAs($user)->getJson('/search?q=' . urlencode($q))->assertOk()->json('groups');
    }

    private function labels(array $groups): array
    {
        return array_column($groups, 'label');
    }

    private function travelOrder(string $controlNo, array $overrides = []): TravelOrder
    {
        $order = TravelOrder::create($overrides + [
            'control_no' => $controlNo, 'date' => '2025-09-23', 'from_date' => '2025-09-23', 'to_date' => '2025-09-23',
            'going_to' => 'San Isidro Sur, Luna, Apayao', 'inclusive_dates' => ['2025-09-23'], 'purpose' => ['Budget hearing'], 'vehicle' => 'PUJ',
        ]);
        $order->employees()->attach($this->makeEmployee(['name' => 'Tomasa S. Atayoc'])->id);

        return $order;
    }

    private function leave(int $employeeId, string $controlNo): Leave
    {
        return Leave::create([
            'control_no' => $controlNo, 'employee_id' => $employeeId, 'date_of_filing' => '2026-10-20', 'type_of_leave' => 'Vacation Leave',
            'number_of_working_days_applied_for' => 1, 'inclusive_dates' => ['2026-10-21'],
        ]);
    }

    public function test_results_link_to_the_list_on_the_record_and_its_year(): void
    {
        $this->travelOrder('TO-2025-09-0008');

        $groups = $this->search($this->makeUser('Administrator'), 'Luna');

        $this->assertSame(['Travel Orders'], $this->labels($groups));
        $this->assertSame('TO-2025-09-0008 · San Isidro Sur, Luna, Apayao', $groups[0]['items'][0]['title']);
        $this->assertStringEndsWith('/travel-orders?search=TO-2025-09-0008&year=2025', $groups[0]['items'][0]['url']);
    }

    public function test_employee_names_find_their_records(): void
    {
        $this->travelOrder('TO-2025-09-0008');

        $this->assertSame(['Travel Orders'], $this->labels($this->search($this->makeUser('Administrator'), 'atayoc')));
    }

    public function test_only_modules_the_user_can_open_are_searched(): void
    {
        $this->travelOrder('TO-2025-09-0008');
        $this->makeRole('Records Only', ['records.view', 'global_search.use']);

        $this->assertSame([], $this->search($this->makeUser('Records Only'), 'TO-2025'));
    }

    public function test_trashed_records_are_not_found(): void
    {
        $this->travelOrder('TO-2025-09-0008')->delete();

        $this->assertSame([], $this->search($this->makeUser('Administrator'), 'TO-2025'));
    }

    public function test_without_view_all_only_your_own_leaves_are_found(): void
    {
        $own = $this->makeEmployee(['name' => 'Samuel B. Soliman']);
        $other = $this->makeEmployee(['name' => 'Maria C. Santos']);
        $this->leave($own->id, 'L-2026-10-0001');
        $this->leave($other->id, 'L-2026-10-0002');
        $this->makeRole('Leave Clerk', ['leaves.view', 'global_search.use']);

        $groups = $this->search($this->makeUser('Leave Clerk', $own), 'L-2026');
        $this->assertSame(['L-2026-10-0001 · Samuel B. Soliman'], array_column($groups[0]['items'], 'title'));

        $this->assertCount(2, $this->search($this->makeUser('Administrator'), 'L-2026')[0]['items']);
    }

    public function test_administrative_records_need_their_permission(): void
    {
        Record::create(['record_no' => 'ADM-0001', 'record_type' => 'Administrative', 'title' => 'Personnel memo']);
        Record::create(['record_no' => 'PB-0001', 'record_type' => 'Provincial Budget', 'title' => 'Annual budget memo']);
        $this->makeRole('Records Reader', ['records.view', 'global_search.use']);

        $groups = $this->search($this->makeUser('Records Reader'), 'memo');

        $this->assertSame(['PB-0001 · Annual budget memo'], array_column($groups[0]['items'], 'title'));
        $this->assertStringContainsString('tab=Provincial%20Budget', $groups[0]['items'][0]['url']);
    }

    public function test_auxiliary_modules_are_not_searched(): void
    {
        $this->makeEmployee(['name' => 'Tomasa S. Atayoc']);

        $this->assertSame([], $this->search($this->makeUser('Administrator'), 'Atayoc'));
    }

    public function test_one_character_finds_nothing_and_the_search_needs_its_permission(): void
    {
        $this->travelOrder('TO-2025-09-0008');
        $this->assertSame([], $this->search($this->makeUser('Administrator'), 'T'));

        $this->makeRole('No Search', ['travel_orders.view']);
        $this->actingAs($this->makeUser('No Search'))->getJson('/search?q=TO-2025')->assertForbidden();
    }
}
