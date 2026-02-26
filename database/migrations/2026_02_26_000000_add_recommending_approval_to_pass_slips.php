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
            $table->foreignId('recommending_approval_employee_id')->nullable()->constrained('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pass_slips', function (Blueprint $table) {
            $table->dropForeignIdFor('recommending_approval_employee_id');
            $table->dropColumn('recommending_approval_employee_id');
        });
    }
};
