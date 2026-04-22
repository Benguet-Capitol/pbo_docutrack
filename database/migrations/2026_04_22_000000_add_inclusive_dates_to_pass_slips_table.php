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
        Schema::table('pass_slips', function (Blueprint $table) {
            $table->json('inclusive_dates')->nullable()->after('date')->comment('JSON array of dates or date ranges (e.g., ["2026-04-22", "2026-04-23 - 2026-04-26"])');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pass_slips', function (Blueprint $table) {
            $table->dropColumn('inclusive_dates');
        });
    }
};
