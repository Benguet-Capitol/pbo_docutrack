<?php

namespace App\Support;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every column that points at an employee. Used to block deleting an employee that records still use
 * (several of these foreign keys cascade, so a permanent delete would silently remove the records), and to
 * re-point records when "Sync from HRIS" merges a duplicate employee into the original.
 */
class EmployeeReferences
{
    /** table => [columns, one row, several rows] */
    private const REFERENCES = [
        'leaves' => [['employee_id'], 'leave', 'leaves'],
        'pass_slip_employee' => [['employee_id'], 'pass slip', 'pass slips'],
        'pass_slips' => [['recommending_approval_employee_id', 'approved_by_employee_id'], 'pass slip signature', 'pass slip signatures'],
        'time_slips' => [['requesting_employee_id', 'certified_by_employee_id', 'approved_by_employee_id'], 'time slip', 'time slips'],
        'tardiness' => [['employee_id', 'supervisor_employee_id', 'approved_by_employee_id'], 'tardiness/undertime record', 'tardiness/undertime records'],
        'travel_order_employee' => [['employee_id'], 'travel order', 'travel orders'],
        'travel_orders' => [['approver_employee_id', 'supervisor_employee_id'], 'travel order signature', 'travel order signatures'],
        'certificate_of_appearances' => [['approved_by_employee_id'], 'certificate of appearance signature', 'certificate of appearance signatures'],
        'notice_of_meetings' => [['employee_id'], 'notice of meeting', 'notices of meeting'],
        'plantillas' => [['employee_id'], 'plantilla record', 'plantilla records'],
    ];

    /** Pivot tables: their parent column, so a merge doesn't create a duplicate (parent, employee) row */
    private const PIVOT_PARENTS = [
        'pass_slip_employee' => 'pass_slip_id',
        'travel_order_employee' => 'travel_order_id',
    ];

    /**
     * Records still referring to the employee, e.g. ["3 leaves", "1 travel order"]; trashed records count too,
     * since restoring them would need the employee.
     */
    public static function usages(Employee $employee): array
    {
        $usages = [];
        foreach (self::REFERENCES as $table => [$columns, $one, $many]) {
            if (!Schema::hasTable($table)) continue;
            $count = DB::table($table)->where(function ($query) use ($columns, $employee) {
                foreach ($columns as $column) $query->orWhere($column, $employee->id);
            })->count();
            if ($count > 0) {
                $usages[] = $count . ' ' . ($count === 1 ? $one : $many);
            }
        }

        return $usages;
    }

    /** Ids of every employee some record refers to (trashed records included) */
    public static function referencedIds(): array
    {
        $queries = [];
        foreach (self::REFERENCES as $table => [$columns]) {
            if (!Schema::hasTable($table)) continue;
            foreach ($columns as $column) {
                $queries[] = DB::table($table)->select($column . ' as id')->whereNotNull($column);
            }
        }
        if (!$queries) return [];

        $union = array_shift($queries);
        foreach ($queries as $query) $union->union($query);

        return $union->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Message explaining why the employee can't be deleted, or null if nothing uses them */
    public static function deleteBlocker(Employee $employee): ?string
    {
        $usages = self::usages($employee);

        return $usages
            ? "{$employee->name} can't be deleted because records still use them: " . implode(', ', $usages) . '.'
            : null;
    }

    /** Point every record from one employee to another (both ids exist) */
    public static function repoint(int $fromId, int $toId): void
    {
        foreach (self::REFERENCES as $table => [$columns]) {
            if (!Schema::hasTable($table)) continue;
            foreach ($columns as $column) {
                if ($parent = self::PIVOT_PARENTS[$table] ?? null) {
                    // Drop rows whose parent already lists the target employee
                    $parentsWithTarget = DB::table($table)->where($column, $toId)->pluck($parent);
                    DB::table($table)->where($column, $fromId)->whereIn($parent, $parentsWithTarget)->delete();
                }
                DB::table($table)->where($column, $fromId)->update([$column => $toId]);
            }
        }
    }
}
