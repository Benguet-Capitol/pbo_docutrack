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
        Schema::create('time_slips', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->foreignId('requesting_employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->time('time_in_am')->nullable();
            $table->time('time_out_am')->nullable();
            $table->time('time_in_pm')->nullable();
            $table->time('time_out_pm')->nullable();
            $table->text('reason');
            $table->foreignId('certified_by_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slips');
    }
};
