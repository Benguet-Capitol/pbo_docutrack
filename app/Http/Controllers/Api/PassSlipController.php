<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PassSlip;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassSlipController extends Controller
{
    /**
     * Display a listing of pass slips.
     */
    public function index(): JsonResponse
    {
        try {
            $passSlips = PassSlip::with('employees')
                ->latest('date')
                ->get();
            return response()->json($passSlips);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created pass slip.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([                'control_no' => 'required|string|unique:pass_slips,control_no',                'date' => 'required|date',
                'requested_time' => 'required|date_format:H:i',
                'purpose' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'expected_return_time' => 'required|string',
                'remarks' => 'nullable|string',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
            ]);

            // Control number is generated in the frontend modal
            $controlNo = $validated['control_no'];

            // Create pass slip
            $passSlip = PassSlip::create([
                'control_no' => $controlNo,
                'date' => $validated['date'],
                'requested_time' => $validated['requested_time'],
                'purpose' => $validated['purpose'],
                'location' => $validated['location'],
                'expected_return_time' => $validated['expected_return_time'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Attach employees
            $passSlip->employees()->attach($validated['employee_ids']);
            
            // Reload with employees
            $passSlip->load('employees');

            return response()->json([
                'message' => 'Pass slip created successfully',
                'data' => $passSlip,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified pass slip.
     */
    public function update(Request $request, PassSlip $passSlip): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:pass_slips,control_no,' . $passSlip->id,
                'date' => 'required|date',
                'requested_time' => 'required|date_format:H:i',
                'purpose' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'expected_return_time' => 'required|string',
                'remarks' => 'nullable|string',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
            ]);

            // Update pass slip
            $passSlip->update([
                'control_no' => $validated['control_no'],
                'date' => $validated['date'],
                'requested_time' => $validated['requested_time'],
                'purpose' => $validated['purpose'],
                'location' => $validated['location'],
                'expected_return_time' => $validated['expected_return_time'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Sync employees (update the relationship)
            $passSlip->employees()->sync($validated['employee_ids']);
            
            // Reload with employees
            $passSlip->load('employees');

            return response()->json([
                'message' => 'Pass slip updated successfully',
                'data' => $passSlip,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the specified pass slip.
     */
    public function destroy(PassSlip $passSlip): JsonResponse
    {
        try {
            $controlNo = $passSlip->control_no;
            $passSlip->delete();

            return response()->json([
                'message' => "Pass slip {$controlNo} deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
