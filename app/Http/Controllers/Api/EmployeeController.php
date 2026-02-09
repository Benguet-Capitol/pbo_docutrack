<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees from pbo-registry.
     */
    public function index(): JsonResponse
    {
        try {
            $employees = RegistryEmployee::with('office')->get();
            
            // Ensure fk_office_id is included in the response
            $employees = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'fk_office_id' => $employee->fk_office_id,
                    'designation' => $employee->designation,
                    'office' => $employee->office,
                ];
            });
            
            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|string',
                'name' => 'required|string',
                'office' => 'required|integer',
                'designation' => 'required|string',
            ]);

            $employee = RegistryEmployee::create($validated);
            return response()->json($employee->load('office'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an employee.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $employee = RegistryEmployee::findOrFail($id);
            
            $validated = $request->validate([
                'employee_id' => 'sometimes|string',
                'name' => 'sometimes|string',
                'office' => 'sometimes|integer',
                'designation' => 'sometimes|string',
            ]);

            $employee->update($validated);
            return response()->json($employee->load('office'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an employee.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $employee = RegistryEmployee::findOrFail($id);
            $employee->delete();
            return response()->json(['message' => 'Employee deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

