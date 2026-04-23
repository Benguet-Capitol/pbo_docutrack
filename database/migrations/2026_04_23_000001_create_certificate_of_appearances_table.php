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
        Schema::create('certificate_of_appearances', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->string('name'); // Employee name (string, not foreign key)
            $table->string('office'); // Office name (string, not foreign key)
            $table->string('purpose');
            $table->date('date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_of_appearances');
    }
};
