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
        Schema::table('leaves', function (Blueprint $table) {
            $table->boolean('is_half_day')->default(false)->after('number_of_working_days_applied_for')->comment('Whether this is a half-day leave');
            $table->string('half_day_period')->nullable()->after('is_half_day')->comment('AM or PM for half-day leaves');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['is_half_day', 'half_day_period']);
        });
    }
};
