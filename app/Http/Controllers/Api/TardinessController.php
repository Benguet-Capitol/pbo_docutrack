<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tardiness;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TardinessController extends Controller
{
    /**
     * Display a listing of tardiness records.
     */
    public function index(): JsonResponse
    {
        try {
            $tardiness = Tardiness::with('employee')
                ->latest('created_at')
                ->get();
            return response()->json([
                'message' => 'Tardiness records retrieved successfully',
                'data' => $tardiness,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created tardiness record.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'control_no' => 'required|string|unique:tardiness,control_no',
                'date_filed' => 'required|date',
                'type' => 'required|in:Tardiness,Undertime',
                'requested_date' => 'required|date',
                'employee_id' => 'required|integer|exists:employees,id',
                'requested_time' => 'required|date_format:H:i',
                'reason' => 'required|string|max:500',
                'return_time' => 'nullable|string',
                'supervisor_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            $tardiness = Tardiness::create($validated);

            return response()->json([
                'message' => 'Tardiness record created successfully',
                'data' => $tardiness->load('employee'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified tardiness record.
     */
    public function show($id): JsonResponse
    {
        try {
            $tardiness = Tardiness::with('employee')->find($id);
            if (!$tardiness) {
                return response()->json(['error' => 'Tardiness record not found'], 404);
            }
            return response()->json([
                'message' => 'Tardiness record retrieved successfully',
                'data' => $tardiness,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified tardiness record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $tardiness = Tardiness::find($id);
            if (!$tardiness) {
                return response()->json(['error' => 'Tardiness record not found'], 404);
            }

            $validated = $request->validate([
                'control_no' => 'required|string|unique:tardiness,control_no,' . $id,
                'date_filed' => 'required|date',
                'type' => 'required|in:Tardiness,Undertime',
                'requested_date' => 'required|date',
                'employee_id' => 'required|integer|exists:employees,id',
                'requested_time' => 'required|date_format:H:i',
                'reason' => 'required|string|max:500',
                'return_time' => 'nullable|string',
                'supervisor_employee_id' => 'nullable|integer|exists:employees,id',
            ]);

            $tardiness->update($validated);

            return response()->json([
                'message' => 'Tardiness record updated successfully',
                'data' => $tardiness->load('employee'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified tardiness record.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $tardiness = Tardiness::find($id);
            if (!$tardiness) {
                return response()->json(['error' => 'Tardiness record not found'], 404);
            }

            $tardiness->delete();

            return response()->json(['message' => 'Tardiness record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
