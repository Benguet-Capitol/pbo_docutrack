<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registry\RegistryOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    /**
     * Display a listing of offices from pbo-registry.
     */
    public function index(): JsonResponse
    {
        try {
            $offices = RegistryOffice::all();
            return response()->json($offices);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created office.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'office_abbreviation' => 'required|string',
                'office_name' => 'required|string',
                'sub_office' => 'nullable|string',
                'fund' => 'required|string',
                'fpp_code' => 'nullable|string',
                'responsibility_code' => 'nullable|string',
                'branch' => 'required|string',
            ]);

            $office = RegistryOffice::create($validated);
            return response()->json($office, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an office.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $office = RegistryOffice::findOrFail($id);
            
            $validated = $request->validate([
                'office_abbreviation' => 'sometimes|string',
                'office_name' => 'sometimes|string',
                'sub_office' => 'nullable|string',
                'fund' => 'sometimes|string',
                'fpp_code' => 'nullable|string',
                'responsibility_code' => 'nullable|string',
                'branch' => 'sometimes|string',
            ]);

            $office->update($validated);
            return response()->json($office);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an office.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $office = RegistryOffice::findOrFail($id);
            $office->delete();
            return response()->json(['message' => 'Office deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
