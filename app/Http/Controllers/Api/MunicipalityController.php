<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    /**
     * Display a listing of municipalities.
     */
    public function index(): JsonResponse
    {
        try {
            $municipalities = Municipality::all();
            return response()->json($municipalities);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created municipality.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:municipalities,name',
                'code' => 'required|string|unique:municipalities,code',
                'municipal_budget_officer' => 'nullable|string',
                'representative' => 'nullable|string',
            ]);

            $municipality = Municipality::create($validated);
            return response()->json($municipality, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a municipality.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $municipality = Municipality::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|string|unique:municipalities,name,' . $id,
                'code' => 'sometimes|string|unique:municipalities,code,' . $id,
                'municipal_budget_officer' => 'nullable|string',
                'representative' => 'nullable|string',
            ]);

            $municipality->update($validated);
            return response()->json($municipality);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a municipality.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $municipality = Municipality::findOrFail($id);
            $municipality->delete();
            return response()->json(['message' => 'Municipality deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
