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
        Schema::create('document_checklist_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_record_id')->constrained('document_checklist_records')->cascadeOnDelete();
            $table->string('signatory_name');
            $table->boolean('is_signed')->default(false);
            $table->timestamps();
            
            // Composite unique constraint with shorter name to avoid MySQL identifier length limit
            $table->unique(['checklist_record_id', 'signatory_name'], 'checklist_signatory_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_checklist_signatories');
    }
};
