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
        Schema::table('travel_orders', function (Blueprint $table) {
            // Drop the foreign key constraint by name
            $table->dropForeign(['driver_employee_id']);
            // Drop the old column
            $table->dropColumn('driver_employee_id');
            // Add new driver column as string
            $table->string('driver')->nullable()->after('plate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('driver');
            $table->foreignId('driver_employee_id')->nullable()->constrained('employees')->onDelete('set null')->after('plate_number');
        });
    }
};
