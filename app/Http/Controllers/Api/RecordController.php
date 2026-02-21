<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordController extends Controller
{
    /**
     * Display a listing of records.
     */
    public function index(): JsonResponse
    {
        try {
            $records = Record::with('user')->get();
            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created record.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Check role permission
            if (!RoleService::canCreateDocument($user)) {
                return response()->json(['error' => 'You do not have permission to create records'], 403);
            }

            $validated = $request->validate([
                'record_no' => 'required|string|unique:records,record_no',
                'record_type' => 'required|string',
                'title' => 'required|string',
                'remarks' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx|max:10240',
            ]);

            // Automatically set user_id to the authenticated user's numeric ID
            $validated['user_id'] = $user->id;

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('records', $filename, 'public');
                $validated['image_path'] = $path;
            }

            unset($validated['file']);
            
            $record = Record::create($validated);
            
            return response()->json($record->load('user'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Check role permission
            if (!RoleService::canEditDocument($user)) {
                return response()->json(['error' => 'You do not have permission to edit records'], 403);
            }

            $record = Record::findOrFail($id);
            
            $validated = $request->validate([
                'record_no' => 'sometimes|string|unique:records,record_no,' . $id,
                'record_type' => 'sometimes|string',
                'title' => 'sometimes|string',
                'remarks' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx|max:10240',
            ]);

            // Handle file upload
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($record->image_path && Storage::disk('public')->exists($record->image_path)) {
                    Storage::disk('public')->delete($record->image_path);
                }

                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('records', $filename, 'public');
                $validated['image_path'] = $path;
            }

            unset($validated['file']);
            $record->update($validated);
            return response()->json($record->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a record.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $record = Record::findOrFail($id);
            
            // Delete file if exists
            if ($record->image_path && Storage::disk('public')->exists($record->image_path)) {
                Storage::disk('public')->delete($record->image_path);
            }

            $record->delete();
            return response()->json(['message' => 'Record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
