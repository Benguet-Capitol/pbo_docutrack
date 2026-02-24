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
        Schema::create('pass_slips', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->date('date');
            $table->time('requested_time');
            $table->string('purpose');
            $table->string('location')->nullable();
            $table->string('expected_return_time'); // Can be time or 'ASAP' or 'NWD'
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('pass_slip_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pass_slip_id')->constrained('pass_slips')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure each employee is only assigned once per pass slip
            $table->unique(['pass_slip_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pass_slip_employee');
        Schema::dropIfExists('pass_slips');
    }
};
