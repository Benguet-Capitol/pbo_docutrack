<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the "Approved" signatory be someone other than the Provincial Budget Officer
 * (e.g. an OIC or Acting PBO). Null means the Provincial Budget Officer, as before.
 */
return new class extends Migration
{
    private array $tables = ['pass_slips', 'time_slips', 'tardiness', 'certificate_of_appearances'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->onDelete('set null');
                $table->string('approved_by_title')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by_employee_id');
                $table->dropColumn('approved_by_title');
            });
        }
    }
};
