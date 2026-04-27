<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeSlip;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimeSlipController extends Controller
{
    /**
     * Display a listing of time slips.
     */
    public function index(): JsonResponse
    {
        try {
            $timeSlips = TimeSlip::with('requestingEmployee', 'certifiedByEmployee')
                ->latest('date')
                ->get();
            return response()->json($timeSlips);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created time slip.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:time_slips,control_no',
                'requesting_employee_id' => 'required|integer|exists:employees,id',
                'date' => 'required|date',
                'time_in_am' => 'nullable|date_format:H:i',
                'time_out_am' => 'nullable|date_format:H:i',
                'time_in_pm' => 'nullable|date_format:H:i',
                'time_out_pm' => 'nullable|date_format:H:i',
                'reason' => 'required|string|max:500',
                'certified_by_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            // Validate that at least one time field has a value
            if (!$validated['time_in_am'] && !$validated['time_out_am'] && !$validated['time_in_pm'] && !$validated['time_out_pm']) {
                return response()->json(['errors' => ['times' => 'At least one time field must have a value']], 422);
            }

            // Create time slip
            $timeSlip = TimeSlip::create([
                'control_no' => $validated['control_no'],
                'requesting_employee_id' => $validated['requesting_employee_id'],
                'date' => $validated['date'],
                'time_in_am' => $validated['time_in_am'] ?? null,
                'time_out_am' => $validated['time_out_am'] ?? null,
                'time_in_pm' => $validated['time_in_pm'] ?? null,
                'time_out_pm' => $validated['time_out_pm'] ?? null,
                'reason' => $validated['reason'],
                'certified_by_employee_id' => $validated['certified_by_employee_id'] ?? null,
            ]);

            // Reload with relationships
            $timeSlip->load('requestingEmployee', 'certifiedByEmployee');

            return response()->json([
                'message' => 'Time slip created successfully',
                'data' => $timeSlip,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified time slip.
     */
    public function update(Request $request, TimeSlip $timeSlip): JsonResponse
    {
        try {
            // Validate input (control_no is not editable)
            $validated = $request->validate([
                'requesting_employee_id' => 'required|integer|exists:employees,id',
                'date' => 'required|date',
                'time_in_am' => 'nullable|date_format:H:i',
                'time_out_am' => 'nullable|date_format:H:i',
                'time_in_pm' => 'nullable|date_format:H:i',
                'time_out_pm' => 'nullable|date_format:H:i',
                'reason' => 'required|string|max:500',
                'certified_by_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            // Validate that at least one time field has a value
            if (!$validated['time_in_am'] && !$validated['time_out_am'] && !$validated['time_in_pm'] && !$validated['time_out_pm']) {
                return response()->json(['errors' => ['times' => 'At least one time field must have a value']], 422);
            }

            // Update time slip
            $timeSlip->update([
                'requesting_employee_id' => $validated['requesting_employee_id'],
                'date' => $validated['date'],
                'time_in_am' => $validated['time_in_am'] ?: null,
                'time_out_am' => $validated['time_out_am'] ?: null,
                'time_in_pm' => $validated['time_in_pm'] ?: null,
                'time_out_pm' => $validated['time_out_pm'] ?: null,
                'reason' => $validated['reason'],
                'certified_by_employee_id' => $validated['certified_by_employee_id'] ?: null,
            ]);

            // Reload with relationships
            $timeSlip->load('requestingEmployee', 'certifiedByEmployee');

            return response()->json([
                'message' => 'Time slip updated successfully',
                'data' => $timeSlip,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('TimeSlip Update Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'time_slip_id' => $timeSlip->id ?? null,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the specified time slip.
     */
    public function destroy(TimeSlip $timeSlip): JsonResponse
    {
        try {
            $id = $timeSlip->id;
            $timeSlip->delete();

            return response()->json([
                'message' => "Time slip deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
