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
        Schema::create('document_checklist_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained('document_checklist_items')->cascadeOnDelete();
            $table->boolean('is_checked')->default(false);
            $table->text('remarks')->nullable(); // User-provided remarks for this item
            $table->timestamps();
            
            // Composite unique constraint with shorter name to avoid MySQL identifier length limit
            $table->unique(['document_id', 'checklist_item_id'], 'checklist_record_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_checklist_records');
    }
};
