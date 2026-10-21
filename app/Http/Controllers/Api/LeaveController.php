<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Download the leave as a filled CS Form No. 6 (Application for Leave) Excel file.
     */
    public function exportForm(Leave $leave, \App\Services\LeaveFormExporter $exporter)
    {
        $path = $exporter->export($leave);

        return response()->download($path, $exporter->filename($leave), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Error message when the leave would exceed the employee's yearly limit for its type, or null if within it.
     */
    private function yearlyLimitError(array $data, ?int $excludeLeaveId = null): ?string
    {
        $type = $data['type_of_leave'];
        $limit = Leave::YEARLY_LIMITS[$type] ?? null;
        if ($limit === null) {
            return null;
        }

        $year = \Illuminate\Support\Carbon::parse($data['date_of_filing'])->year;
        $used = Leave::usedDays((int) $data['employee_id'], $type, $year, $excludeLeaveId);
        $requested = Leave::daysCounted((bool) ($data['is_half_day'] ?? false), $data['number_of_working_days_applied_for']);

        if ($used + $requested <= $limit) {
            return null;
        }

        $remaining = max(0, $limit - $used);
        $format = fn (float $days) => rtrim(rtrim(number_format($days, 1), '0'), '.');

        return $remaining <= 0
            ? "{$type} is already exhausted for {$year}: all {$limit} days have been used."
            : "{$type} exceeds the yearly limit for {$year}: only {$format($remaining)} of {$limit} days remaining, but {$format($requested)} requested.";
    }

    /**
     * Display a listing of leaves.
     */
    public function index(): JsonResponse
    {
        try {
            $leaves = Leave::with('employee')
                ->latest('id')
                ->get()
                ->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'control_no' => $leave->control_no,
                        'employee_id' => $leave->employee_id,
                        'employee' => $leave->employee,
                        'date_of_filing' => $leave->date_of_filing,
                        'type_of_leave' => $leave->type_of_leave,
                        'number_of_working_days_applied_for' => $leave->number_of_working_days_applied_for,
                        'is_half_day' => $leave->is_half_day,
                        'half_day_period' => $leave->half_day_period,
                        'inclusive_dates' => $leave->inclusive_dates,
                        'off_days' => $leave->off_days,
                        'within_philippines' => $leave->within_philippines,
                        'purpose' => $leave->purpose,
                        'in_hospital' => $leave->in_hospital,
                        'illness' => $leave->illness,
                        'completion_type' => $leave->completion_type,
                        'other_type' => $leave->other_type,
                        'created_at' => $leave->created_at,
                        'updated_at' => $leave->updated_at,
                    ];
                });
            return response()->json($leaves);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created leave.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:leaves,control_no',
                'employee_id' => 'required|integer|exists:employees,id',
                'date_of_filing' => 'required|date',
                'type_of_leave' => 'required|string',
                'number_of_working_days_applied_for' => 'required|numeric|min:0.5',
                'is_half_day' => 'nullable|boolean',
                'half_day_period' => 'nullable|string|in:AM,PM',
                'inclusive_dates' => 'required|array|min:1',
                'off_days' => 'nullable|array',
                'within_philippines' => 'nullable|boolean',
                'purpose' => 'nullable|string|max:500',
                'in_hospital' => 'nullable|boolean',
                'illness' => 'nullable|string|max:500',
                'completion_type' => 'nullable|string',
                'other_type' => 'nullable|string',
            ]);

            if ($error = $this->yearlyLimitError($validated)) {
                return response()->json(['errors' => ['type_of_leave' => [$error]]], 422);
            }

            // Control number is generated in the frontend modal
            $controlNo = $validated['control_no'];

            // Create leave
            $leave = Leave::create([
                'control_no' => $controlNo,
                'employee_id' => $validated['employee_id'],
                'date_of_filing' => $validated['date_of_filing'],
                'type_of_leave' => $validated['type_of_leave'],
                'number_of_working_days_applied_for' => $validated['number_of_working_days_applied_for'],
                'is_half_day' => $validated['is_half_day'] ?? false,
                'half_day_period' => $validated['half_day_period'] ?? null,
                'inclusive_dates' => $validated['inclusive_dates'],
                'off_days' => $validated['off_days'] ?? null,
                'within_philippines' => $validated['within_philippines'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'in_hospital' => $validated['in_hospital'] ?? null,
                'illness' => $validated['illness'] ?? null,
                'completion_type' => $validated['completion_type'] ?? null,
                'other_type' => $validated['other_type'] ?? null,
            ]);

            // Reload with employee
            $leave->load('employee');

            return response()->json([
                'message' => 'Leave created successfully',
                'data' => $leave,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified leave.
     */
    public function update(Request $request, Leave $leave): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:leaves,control_no,' . $leave->id,
                'employee_id' => 'required|integer|exists:employees,id',
                'date_of_filing' => 'required|date',
                'type_of_leave' => 'required|string',
                'number_of_working_days_applied_for' => 'required|numeric|min:0.5',
                'is_half_day' => 'nullable|boolean',
                'half_day_period' => 'nullable|string|in:AM,PM',
                'inclusive_dates' => 'required|array|min:1',
                'off_days' => 'nullable|array',
                'within_philippines' => 'nullable|boolean',
                'purpose' => 'nullable|string|max:500',
                'in_hospital' => 'nullable|boolean',
                'illness' => 'nullable|string|max:500',
                'completion_type' => 'nullable|string',
                'other_type' => 'nullable|string',
            ]);

            // The leave being edited doesn't count against its own limit
            if ($error = $this->yearlyLimitError($validated, $leave->id)) {
                return response()->json(['errors' => ['type_of_leave' => [$error]]], 422);
            }

            // Update leave
            $leave->update($validated);

            // Reload with employee
            $leave->load('employee');

            return response()->json([
                'message' => 'Leave updated successfully',
                'data' => $leave,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the specified leave.
     */
    public function destroy(Leave $leave): JsonResponse
    {
        try {
            $controlNo = $leave->control_no;
            $leave->delete();

            return response()->json([
                'message' => "Leave {$controlNo} deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
