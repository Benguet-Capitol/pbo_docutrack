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
        Schema::table('document_checklist_signatories', function (Blueprint $table) {
            $table->string('acting_status')->nullable()->after('is_signed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_checklist_signatories', function (Blueprint $table) {
            $table->dropColumn('acting_status');
        });
    }
};
