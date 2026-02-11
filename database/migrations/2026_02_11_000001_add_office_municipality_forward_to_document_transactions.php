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
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('forwarded_to_office_id')->nullable()->after('forwarded_to_user_id');
            $table->unsignedBigInteger('forwarded_to_municipality_id')->nullable()->after('forwarded_to_office_id');
            $table->foreign('forwarded_to_office_id')->references('id')->on('offices')->onDelete('set null');
            $table->foreign('forwarded_to_municipality_id')->references('id')->on('municipalities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_transactions', function (Blueprint $table) {
            $table->dropForeignKey(['forwarded_to_office_id']);
            $table->dropForeignKey(['forwarded_to_municipality_id']);
            $table->dropColumn('forwarded_to_office_id');
            $table->dropColumn('forwarded_to_municipality_id');
        });
    }
};
