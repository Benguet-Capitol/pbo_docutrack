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
        Schema::table('document_checklist_items', function (Blueprint $table) {
            $table->char('group_letter', 1)->nullable()->after('order'); // a, b, c, d, e, f, g, etc.
            $table->boolean('is_subitem')->default(false)->after('group_letter'); // true if it's a sub-item
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_checklist_items', function (Blueprint $table) {
            $table->dropColumn('group_letter');
            $table->dropColumn('is_subitem');
        });
    }
};
