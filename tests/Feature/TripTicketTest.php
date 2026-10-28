<?php

namespace Tests\Feature;

use App\Models\TravelOrder;
use App\Models\TripTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Driver's Trip Tickets: numbered by the system ("YYYY-MM-NNNN", the series running through the year),
 * one per travel order, dated the travel order's date.
 */
class TripTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeRole('Travel Viewer', ['travel_orders.view']);
        $this->viewer = $this->makeUser('Travel Viewer');
    }

    private function travelOrder(string $date, array $overrides = []): TravelOrder
    {
        static $sequence = 0;
        $sequence++;

        $order = TravelOrder::create($overrides + [
            'control_no' => 'TO-' . substr($date, 0, 7) . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'date' => $date,
            'from_date' => $date,
            'to_date' => $date,
            'going_to' => 'San Isidro Sur, Luna, Apayao',
            'inclusive_dates' => [$date],
            'purpose' => ['Budget hearing'],
            'vehicle' => 'RP Vehicle',
            'plate_number' => 'SAC 3512',
            'driver' => 'Jonathan L. Domingo',
        ]);
        $order->employees()->attach($this->makeEmployee()->id);

        return $order;
    }

    private function issue(TravelOrder $order)
    {
        return $this->actingAs($this->viewer)->postJson("/api/travel-orders/{$order->id}/trip-ticket");
    }

    public function test_the_first_ticket_of_the_year_is_numbered_from_the_travel_orders_date(): void
    {
        $this->issue($this->travelOrder('2026-09-23'))
            ->assertOk()
            ->assertExactJson(['control_no' => '2026-09-0001', 'date' => '2026-09-23']);
    }

    public function test_opening_the_ticket_again_keeps_its_number(): void
    {
        $order = $this->travelOrder('2026-09-23');

        $this->issue($order)->assertJsonPath('control_no', '2026-09-0001');
        $this->issue($order)->assertJsonPath('control_no', '2026-09-0001');

        $this->assertSame(1, TripTicket::count());
    }

    public function test_the_series_runs_through_the_year_and_restarts_the_next(): void
    {
        $this->issue($this->travelOrder('2026-02-10'))->assertJsonPath('control_no', '2026-02-0001');
        $this->issue($this->travelOrder('2026-09-23'))->assertJsonPath('control_no', '2026-09-0002');
        $this->issue($this->travelOrder('2026-09-25'))->assertJsonPath('control_no', '2026-09-0003');
        $this->issue($this->travelOrder('2027-01-05'))->assertJsonPath('control_no', '2027-01-0001');
    }

    public function test_only_travel_orders_with_a_driver_and_an_rp_vehicle_get_a_ticket(): void
    {
        $this->issue($this->travelOrder('2026-09-23', ['vehicle' => 'PUJ', 'driver' => null]))->assertStatus(422);
        $this->issue($this->travelOrder('2026-09-23', ['driver' => '']))->assertStatus(422);

        $this->assertSame(0, TripTicket::count());
    }

    public function test_a_user_who_cannot_view_travel_orders_cannot_get_a_ticket(): void
    {
        $this->makeRole('No Travel', ['leaves.view']);

        $this->actingAs($this->makeUser('No Travel'))
            ->postJson('/api/travel-orders/' . $this->travelOrder('2026-09-23')->id . '/trip-ticket')
            ->assertForbidden();

        $this->assertSame(0, TripTicket::count());
    }

    public function test_existing_travel_orders_are_numbered_in_date_order_and_new_tickets_continue(): void
    {
        $september = $this->travelOrder('2026-09-23');
        $february = $this->travelOrder('2026-02-19');
        $puj = $this->travelOrder('2026-03-01', ['vehicle' => 'PUJ', 'driver' => null]);
        $trashed = $this->travelOrder('2026-01-05');
        $trashed->delete();
        // Opened out of order before the numbering (e.g. while testing): numbered again
        TripTicket::issueFor($september);

        (require database_path('migrations/2026_10_28_000001_number_existing_trip_tickets.php'))->up();

        $this->assertSame(
            [$february->id => '2026-02-0001', $september->id => '2026-09-0002'],
            TripTicket::orderBy('control_no')->pluck('control_no', 'travel_order_id')->all()
        );
        $this->assertNull(TripTicket::where('travel_order_id', $puj->id)->first());
        $this->issue($this->travelOrder('2026-10-01'))->assertJsonPath('control_no', '2026-10-0003');
    }

    public function test_issuing_a_ticket_is_in_the_activity_log(): void
    {
        $this->issue($this->travelOrder('2026-09-23'));

        $this->assertDatabaseHas('activity_logs', ['module' => 'Trip Tickets', 'action' => 'created']);
    }
}
