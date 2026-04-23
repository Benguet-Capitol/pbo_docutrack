<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateOfAppearance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateOfAppearanceController extends Controller
{
    /**
     * Display a listing of certificates
     */
    public function index(): JsonResponse
    {
        try {
            $certificates = CertificateOfAppearance::latest('date')->get();
            return response()->json($certificates);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created certificate
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'control_no' => 'required|string|unique:certificate_of_appearances,control_no',
                'name' => 'required|string|max:255',
                'office' => 'required|string|max:255',
                'purpose' => 'required|string|max:500',
                'date' => 'required|date',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $certificate = CertificateOfAppearance::create($validated);

            return response()->json([
                'message' => 'Certificate of Appearance created successfully',
                'data' => $certificate,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified certificate
     */
    public function show(CertificateOfAppearance $certificateOfAppearance): JsonResponse
    {
        try {
            return response()->json($certificateOfAppearance);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified certificate
     */
    public function update(Request $request, CertificateOfAppearance $certificateOfAppearance): JsonResponse
    {
        try {
            $validated = $request->validate([
                'control_no' => 'required|string|unique:certificate_of_appearances,control_no,' . $certificateOfAppearance->id,
                'name' => 'required|string|max:255',
                'office' => 'required|string|max:255',
                'purpose' => 'required|string|max:500',
                'date' => 'required|date',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $certificateOfAppearance->update($validated);

            return response()->json([
                'message' => 'Certificate of Appearance updated successfully',
                'data' => $certificateOfAppearance,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the specified certificate
     */
    public function destroy(CertificateOfAppearance $certificateOfAppearance): JsonResponse
    {
        try {
            $certificateOfAppearance->delete();
            return response()->json(['message' => 'Certificate of Appearance deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
