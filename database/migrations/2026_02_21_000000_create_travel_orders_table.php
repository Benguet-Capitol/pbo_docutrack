<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create travel_orders table
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->date('date');
            $table->string('going_to');
            $table->date('from_date');
            $table->date('to_date');
            $table->json('purpose');
            $table->enum('vehicle', ['PUJ', 'RP Vehicle']);
            $table->timestamps();
        });

        // Create travel_order_employee pivot table
        Schema::create('travel_order_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained('travel_orders')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['travel_order_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_order_employee');
        Schema::dropIfExists('travel_orders');
    }
};
