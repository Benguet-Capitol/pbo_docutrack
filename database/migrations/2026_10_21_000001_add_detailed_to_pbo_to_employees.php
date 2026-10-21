<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employees of another office (per HRIS) who work at the Provincial Budget Office count as PBO staff.
 * Set by hand on the employee; the HRIS sync never changes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('detailed_to_pbo')->default(false)->after('office_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['detailed_to_pbo']);
            $table->dropColumn('detailed_to_pbo');
        });
    }
};
