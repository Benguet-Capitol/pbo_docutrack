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
        Schema::create('tardiness', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->date('date_filed');
            $table->enum('type', ['Tardiness', 'Undertime']);
            $table->date('requested_date');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->time('requested_time');
            $table->string('reason');
            $table->string('return_time')->nullable(); // Can be time (HH:MM), ASAP, or NWD
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tardiness');
    }
};
