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
        Schema::create('document_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('document_type'); // e.g., 'Annual Budget', 'Supplemental Budget'
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate templates for same document type
            $table->unique('document_type', 'template_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_checklist_templates');
    }
};
