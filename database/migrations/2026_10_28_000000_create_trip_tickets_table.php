<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver's Trip Tickets: one per travel order with a driver and an RP vehicle, numbered
 * "YYYY-MM-NNNN" (the travel order's year and month, then a series that runs through the year).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->unique()->constrained('travel_orders')->cascadeOnDelete();
            $table->string('control_no')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
