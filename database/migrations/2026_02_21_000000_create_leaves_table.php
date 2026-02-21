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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date_of_filing');
            $table->string('type_of_leave');
            $table->integer('number_of_working_days_applied_for');
            $table->json('inclusive_dates');
            $table->json('off_days')->nullable();
            $table->boolean('within_philippines')->nullable();
            $table->text('purpose')->nullable();
            $table->boolean('in_hospital')->nullable();
            $table->text('illness')->nullable();
            $table->string('completion_type')->nullable();
            $table->string('other_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
