<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeController extends Controller
{
    /**
     * Display a listing of offices from pbo-registry.
     */
    public function index(): JsonResponse
    {
        try {
            $offices = Office::all();
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
        $user = Auth::user();
        if (!RoleService::hasPermission($user, 'offices.edit')) {
            return response()->json(['error' => 'You do not have permission to create offices'], 403);
        }

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

            $office = Office::create($validated);
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
        $user = Auth::user();
        if (!RoleService::hasPermission($user, 'offices.edit')) {
            return response()->json(['error' => 'You do not have permission to edit offices'], 403);
        }

        try {
            $office = Office::findOrFail($id);
            
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
        $user = Auth::user();
        if (!RoleService::hasPermission($user, 'offices.edit')) {
            return response()->json(['error' => 'You do not have permission to delete offices'], 403);
        }

        try {
            $office = Office::findOrFail($id);
            $office->delete();
            return response()->json(['message' => 'Office deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
