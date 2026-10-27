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
        // create_leaves_table already has it on a fresh install (only older databases need it added)
        if (Schema::hasColumn('leaves', 'other_type')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->string('other_type')->nullable()->after('completion_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('other_type');
        });
    }
};
