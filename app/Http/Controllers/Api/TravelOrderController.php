<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelOrderController extends Controller
{
    /**
     * Display a listing of travel orders.
     */
    public function index(): JsonResponse
    {
        try {
            $travelOrders = TravelOrder::with('employees')
                ->latest('date')
                ->get();
            return response()->json($travelOrders);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created travel order.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:travel_orders,control_no',
                'date' => 'required|date',
                'going_to' => 'required|string|max:255',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'purpose' => 'required|array|min:1',
                'purpose.*' => 'string|max:255',
                'vehicle' => 'required|in:PUJ,RP Vehicle',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
                'supervisor_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            // Create travel order
            $travelOrder = TravelOrder::create([
                'control_no' => $validated['control_no'],
                'date' => $validated['date'],
                'going_to' => $validated['going_to'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'purpose' => $validated['purpose'],
                'vehicle' => $validated['vehicle'],
                'supervisor_employee_id' => $validated['supervisor_employee_id'] ?? null,
            ]);

            // Attach employees
            $travelOrder->employees()->attach($validated['employee_ids']);
            
            // Reload with employees
            $travelOrder->load('employees');

            return response()->json([
                'message' => 'Travel order created successfully',
                'data' => $travelOrder,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified travel order.
     */
    public function update(Request $request, TravelOrder $travelOrder): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'control_no' => 'required|string|unique:travel_orders,control_no,' . $travelOrder->id,
                'date' => 'required|date',
                'going_to' => 'required|string|max:255',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'purpose' => 'required|array|min:1',
                'purpose.*' => 'string|max:255',
                'vehicle' => 'required|in:PUJ,RP Vehicle',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
                'supervisor_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            // Update travel order
            $travelOrder->update([
                'control_no' => $validated['control_no'],
                'date' => $validated['date'],
                'going_to' => $validated['going_to'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'purpose' => $validated['purpose'],
                'vehicle' => $validated['vehicle'],
                'supervisor_employee_id' => $validated['supervisor_employee_id'] ?? null,
            ]);

            // Sync employees (update the relationship)
            $travelOrder->employees()->sync($validated['employee_ids']);
            
            // Reload with employees
            $travelOrder->load('employees');

            return response()->json([
                'message' => 'Travel order updated successfully',
                'data' => $travelOrder,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the specified travel order.
     */
    public function destroy(TravelOrder $travelOrder): JsonResponse
    {
        try {
            $controlNo = $travelOrder->control_no;
            $travelOrder->delete();

            return response()->json([
                'message' => "Travel order {$controlNo} deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
