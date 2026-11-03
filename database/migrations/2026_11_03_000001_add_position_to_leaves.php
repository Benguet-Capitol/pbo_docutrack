<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "4. POSITION" as of filing, saved on the leave like the salary, so a reprint doesn't show a later promotion.
 * Existing leaves get the employee's position today, the closest known (no history of positions is kept).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('position')->nullable()->after('salary');
        });

        DB::table('leaves')->update([
            'position' => DB::raw("(select coalesce(nullif(employees.designation, ''), employees.position_title) from employees where employees.id = leaves.employee_id)"),
        ]);
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
