<?php

use App\Models\TravelOrder;
use App\Models\TripTicket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Numbers the trip tickets of the travel orders already in the system (a driver and an RP vehicle), in date order,
 * so new tickets continue the series. Runs once, at the deploy that adds trip tickets: tickets opened before it
 * (only while testing) are numbered again in that order. Trashed travel orders get one if they are restored and opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('trip_tickets')->delete();

        $orders = TravelOrder::where('vehicle', 'RP Vehicle')
            ->whereNotNull('driver')
            ->where('driver', '!=', '')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // One numbering, not one Activity Log entry per travel order
        TripTicket::withoutEvents(function () use ($orders) {
            foreach ($orders as $order) {
                TripTicket::issueFor($order);
            }
        });
    }

    public function down(): void
    {
        DB::table('trip_tickets')->delete();
    }
};
