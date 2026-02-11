<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all offices
        $offices = DB::table('offices')->orderBy('id')->pluck('id')->toArray();
        
        if (empty($offices)) {
            return;
        }

        // Get all employees that have null office_id
        $employees = DB::table('employees')
            ->whereNull('office_id')
            ->orderBy('id')
            ->get();

        // Assign offices to employees in round-robin fashion
        foreach ($employees as $index => $employee) {
            $officeId = $offices[$index % count($offices)];
            DB::table('employees')
                ->where('id', $employee->id)
                ->update(['office_id' => $officeId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset office_id to null for all employees
        DB::table('employees')->update(['office_id' => null]);
    }
};
