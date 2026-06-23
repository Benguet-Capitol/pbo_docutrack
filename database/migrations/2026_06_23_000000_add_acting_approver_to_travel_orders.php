<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->boolean('is_acting_approver')->default(false)->after('approver_employee_id');
            $table->string('acting_approver_name')->nullable()->after('is_acting_approver');
            $table->string('acting_approver_designation')->nullable()->after('acting_approver_name');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn(['is_acting_approver', 'acting_approver_name', 'acting_approver_designation']);
        });
    }
};
