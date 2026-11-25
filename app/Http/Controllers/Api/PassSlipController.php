<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PassSlip;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PassSlipController extends Controller
{
    /**
     * Display a listing of pass slips.
     */
    public function index(): JsonResponse
    {
        try {
            $passSlips = PassSlip::with('employees', 'recommendingApprovals:employees.id')
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
            $validated = $request->validate([                
                'control_no' => 'required|string|unique:pass_slips,control_no',
                'date' => 'required|date',
                'inclusive_dates' => 'nullable|array',
                'inclusive_dates.*' => 'string',
                'requested_time' => 'required|date_format:H:i',
                'purpose' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'expected_return_time' => 'required|string',
                'remarks' => 'nullable|string',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
                'recommending_approval_employee_id' => 'nullable|integer|exists:employees,id',
                // Several immediate supervisors when the requesting employees are under different ones
                'recommending_approval_employee_ids' => 'nullable|array',
                'recommending_approval_employee_ids.*' => 'integer|distinct|exists:employees,id',
                'approved_by_employee_id' => 'nullable|integer|exists:employees,id',
                'approved_by_title' => ['nullable', 'string', Rule::in(['Provincial Budget Officer', 'OIC, Provincial Budget Officer', 'Acting Provincial Budget Officer'])],
                'vehicle' => 'required|in:RP Vehicle,PUJ',
            ]);

            // Control number is generated in the frontend modal
            $controlNo = $validated['control_no'];
            $recommenders = PassSlip::recommendingApprovalIds($validated);

            // Create pass slip
            $passSlip = PassSlip::create([
                'control_no' => $controlNo,
                'date' => $validated['date'],
                'inclusive_dates' => $validated['inclusive_dates'] ?? [],
                'requested_time' => $validated['requested_time'],
                'purpose' => $validated['purpose'],
                'location' => $validated['location'],
                'expected_return_time' => $validated['expected_return_time'],
                'remarks' => $validated['remarks'] ?? null,
                'vehicle' => $validated['vehicle'],
                'recommending_approval_employee_id' => $recommenders[0] ?? null,
                'approved_by_employee_id' => $validated['approved_by_employee_id'] ?? null,
                'approved_by_title' => $validated['approved_by_title'] ?? null,
            ]);

            // Attach employees
            $passSlip->employees()->attach($validated['employee_ids']);
            $passSlip->syncRecommendingApprovals($recommenders);

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
                'inclusive_dates' => 'nullable|array',
                'inclusive_dates.*' => 'string',
                'requested_time' => 'required|date_format:H:i',
                'purpose' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'expected_return_time' => 'required|string',
                'remarks' => 'nullable|string',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
                'recommending_approval_employee_id' => 'nullable|integer|exists:employees,id',
                // Several immediate supervisors when the requesting employees are under different ones
                'recommending_approval_employee_ids' => 'nullable|array',
                'recommending_approval_employee_ids.*' => 'integer|distinct|exists:employees,id',
                'approved_by_employee_id' => 'nullable|integer|exists:employees,id',
                'approved_by_title' => ['nullable', 'string', Rule::in(['Provincial Budget Officer', 'OIC, Provincial Budget Officer', 'Acting Provincial Budget Officer'])],
                'vehicle' => 'required|in:RP Vehicle,PUJ',
            ]);

            $recommenders = PassSlip::recommendingApprovalIds($validated);
            // Sent with only the one supervisor field (the Profile / pbo-registryV2 filing API) and it is unchanged: keep the
            // slip's other supervisors rather than dropping them
            $current = $passSlip->recommending_approval_employee_ids;
            if (!array_key_exists('recommending_approval_employee_ids', $validated) && $recommenders && $recommenders[0] === ($current[0] ?? null)) {
                $recommenders = $current;
            }

            // Update pass slip
            $passSlip->update([
                'control_no' => $validated['control_no'],
                'date' => $validated['date'],
                'inclusive_dates' => $validated['inclusive_dates'] ?? [],
                'requested_time' => $validated['requested_time'],
                'purpose' => $validated['purpose'],
                'location' => $validated['location'],
                'expected_return_time' => $validated['expected_return_time'],
                'remarks' => $validated['remarks'] ?? null,
                'vehicle' => $validated['vehicle'],
                'recommending_approval_employee_id' => $recommenders[0] ?? null,
                'approved_by_employee_id' => $validated['approved_by_employee_id'] ?? null,
                'approved_by_title' => $validated['approved_by_title'] ?? null,
            ]);

            // Sync employees (update the relationship)
            $passSlip->employees()->sync($validated['employee_ids']);
            $passSlip->syncRecommendingApprovals($recommenders);
            
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
