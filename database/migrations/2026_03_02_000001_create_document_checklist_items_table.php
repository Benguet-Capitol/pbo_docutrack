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
        Schema::create('document_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_checklist_templates')->cascadeOnDelete();
            $table->string('item_name'); // e.g., 'Transmittal Letter', 'Budget Message'
            $table->string('signatories')->nullable(); // e.g., 'Secretary to the Sanggunian, Presiding Officer'
            $table->string('remarks')->nullable(); // e.g., 'Letter dated', 'Any Remarks'
            $table->integer('order')->default(0); // For ordering items in the checklist
            $table->timestamps();
            
            // Index for template_id for faster queries
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_checklist_items');
    }
};
