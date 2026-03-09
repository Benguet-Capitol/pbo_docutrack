<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_transactions', function (Blueprint $table) {
            // Add a temporary float column
            $table->float('duration_hours_float')->nullable()->after('duration_hours');
        });

        // Copy data from integer column to float column
        DB::statement('UPDATE document_transactions SET duration_hours_float = duration_hours WHERE duration_hours IS NOT NULL');

        // Drop the old integer column and rename the new one
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->dropColumn('duration_hours');
        });

        Schema::table('document_transactions', function (Blueprint $table) {
            $table->renameColumn('duration_hours_float', 'duration_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_transactions', function (Blueprint $table) {
            // Add temporary integer column
            $table->integer('duration_hours_int')->nullable()->after('duration_hours');
        });

        // Copy data back
        DB::statement('UPDATE document_transactions SET duration_hours_int = CAST(duration_hours AS UNSIGNED) WHERE duration_hours IS NOT NULL');

        // Drop the float column and rename integer column back
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->dropColumn('duration_hours');
        });

        Schema::table('document_transactions', function (Blueprint $table) {
            $table->renameColumn('duration_hours_int', 'duration_hours');
        });
    }
};
