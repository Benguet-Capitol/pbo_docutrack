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
        Schema::table('document_transactions', function (Blueprint $table) {
            // Track how long the document was with this user (in hours)
            $table->integer('duration_hours')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->dropColumn('duration_hours');
        });
    }
};
