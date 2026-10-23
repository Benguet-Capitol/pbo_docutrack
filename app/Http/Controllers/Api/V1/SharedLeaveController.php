<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared Leaves API (read-only) for other PBO systems, e.g. pbo-registryV2's profile page: one employee's leaves for a
 * year, the same figures as the Dashboard's My Summary (used days per type, the yearly-limit leaves with the days left).
 *
 * The employee is found by their HRIS employee ID (employees.employee_id), which both systems share. Leaves are
 * personal data: the client must only ask for the signed-in user's own employee ID. Requests carry a Leaves API key
 * (EnsureApiKey, api.key:leaves), separate from the Records keys. Salary, signatories and trashed leaves are never
 * returned. See docs/leaves-api.md.
 */
class SharedLeaveController extends Controller
{
    /** Always listed, even when none were filed */
    private const MAIN_TYPES = ['Vacation Leave', 'Sick Leave'];

    /** GET /api/v1/leaves/employees/{employeeId}?year=2026 */
    public function summary(Request $request, string $employeeId): JsonResponse
    {
        $validated = $request->validate(['year' => 'nullable|integer|min:2000|max:2100']);
        $year = (int) ($validated['year'] ?? now()->year);

        $employee = Employee::with('office')->where('employee_id', $employeeId)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $leaves = Leave::where('employee_id', $employee->id)
            ->whereYear('date_of_filing', $year)
            ->orderByDesc('date_of_filing')
            ->orderBy('id')
            ->get(['id', 'control_no', 'date_of_filing', 'type_of_leave', 'number_of_working_days_applied_for', 'is_half_day', 'half_day_period', 'inclusive_dates', 'recommendation']);

        $days = fn (Leave $leave) => Leave::daysCounted((bool) $leave->is_half_day, $leave->number_of_working_days_applied_for);
        $disapproved = fn (Leave $leave) => $leave->recommendation === 'disapproval';

        // Days used per type: leaves that proceeded (disapproved ones didn't)
        $usedByType = array_fill_keys(self::MAIN_TYPES, 0.0);
        foreach ($leaves->reject($disapproved) as $leave) {
            $usedByType[$leave->type_of_leave] = ($usedByType[$leave->type_of_leave] ?? 0) + $days($leave);
        }

        // Yearly-limit leaves: every filed leave counts against the limit, disapproved ones included (as when filing)
        $limited = collect(Leave::YEARLY_LIMITS)->map(function (int $limit, string $type) use ($leaves, $days) {
            $counted = (float) $leaves->where('type_of_leave', $type)->sum($days);

            return ['type' => $type, 'limit' => $limit, 'used' => $counted, 'remaining' => max(0, $limit - $counted)];
        })->values();

        // One application per control number (an application can hold several leave types)
        $applications = $leaves->groupBy('control_no')->map(fn ($rows, $controlNo) => [
            'control_no' => $controlNo,
            'date_of_filing' => $rows->first()->date_of_filing?->format('Y-m-d'),
            'disapproved' => $rows->contains($disapproved),
            'days' => (float) $rows->sum($days),
            'entries' => $rows->map(fn (Leave $leave) => [
                'type' => $leave->type_of_leave,
                'days' => $days($leave),
                'is_half_day' => (bool) $leave->is_half_day,
                'half_day_period' => $leave->is_half_day ? $leave->half_day_period : null,
                'inclusive_dates' => $leave->inclusive_dates ?? [],
            ])->values(),
        ])->values();

        $years = Leave::where('employee_id', $employee->id)
            ->selectRaw('DISTINCT YEAR(date_of_filing) as year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json(['data' => [
            'employee' => [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'position_title' => $employee->position_title ?: $employee->designation,
                'office' => $employee->office?->office_abbreviation ?: $employee->office?->office_name,
            ],
            'year' => $year,
            'years' => $years,
            'total_days_used' => (float) array_sum($usedByType),
            'used_by_type' => collect($usedByType)->map(fn ($used, $type) => ['type' => $type, 'days' => (float) $used])->values(),
            'limited_leaves' => $limited,
            'applications' => $applications,
        ]]);
    }
}
