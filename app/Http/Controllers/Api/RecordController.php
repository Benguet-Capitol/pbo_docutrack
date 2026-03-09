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
            
            // Add file details to each record
            $records->each(function ($record) {
                if ($record->image_path && Storage::disk('public')->exists($record->image_path)) {
                    $filePath = Storage::disk('public')->path($record->image_path);
                    $fileSize = filesize($filePath);
                    $extension = pathinfo($record->image_path, PATHINFO_EXTENSION);
                    
                    $record->file_size = $fileSize;
                    $record->file_extension = strtoupper($extension);
                } else {
                    $record->file_size = null;
                    $record->file_extension = null;
                }
            });
            
            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate the next record number based on record type and current year.
     * Format: ABBR-YYYY-MM-NNN (e.g., PB-2026-03-001)
     * Finds the highest existing sequence number for the type/year across all months and increments it (handles deleted records)
     * Numbering resets per year, not per month (all months in year share sequence)
     */
    public function generateRecordNo(Request $request): JsonResponse
    {
        try {
            $recordType = $request->query('record_type');
            if (!$recordType) {
                return response()->json(['error' => 'record_type is required'], 400);
            }

            // Record type abbreviations mapping (must match Vue frontend)
            $abbreviations = [
                'Provincial Budget' => 'PB',
                'Municipal Budget' => 'MB',
                'Issuances / Circulars / Other References and Documents' => 'ISO',
            ];

            $abbr = $abbreviations[$recordType] ?? 'REC';
            $now = new \DateTime();
            $year = $now->format('Y');
            $month = $now->format('m');
            $yearPrefix = "{$abbr}-{$year}-";

            // Get all records of same type in current year (all months)
            $existingNumbers = Record::where('record_type', $recordType)
                ->where('record_no', 'like', "{$yearPrefix}%")
                ->pluck('record_no')
                ->map(function($recordNo) {
                    // Extract the numeric part after the last dash
                    $parts = explode('-', $recordNo);
                    return (int) end($parts);
                })
                ->toArray();

            // Find the highest number across all months in the year, or start at 0 if none exist
            $maxNumber = count($existingNumbers) > 0 ? max($existingNumbers) : 0;
            
            $series = str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
            $recordNo = "{$abbr}-{$year}-{$month}-{$series}";

            return response()->json(['record_no' => $recordNo]);
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

            $validated = $request->validate([
                'record_no' => 'required|string|unique:records,record_no',
                'record_type' => 'required|string',
                'record_subtype' => 'nullable|string',
                'title' => 'required|string',
                'remarks' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx|max:204800',
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

            $record = Record::findOrFail($id);
            
            $validated = $request->validate([
                'record_no' => 'sometimes|string|unique:records,record_no,' . $id,
                'record_type' => 'sometimes|string',
                'record_subtype' => 'nullable|string',
                'title' => 'sometimes|string',
                'remarks' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx|max:204800',
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

    /**
     * View a record file in the browser.
     */
    public function viewFile($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $record = Record::findOrFail($id);
            
            if (!$record->image_path || !Storage::disk('public')->exists($record->image_path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            // Get the file path
            $filePath = Storage::disk('public')->path($record->image_path);
            
            // Display the file in the browser
            return response()->file($filePath);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download a record file.
     */
    public function downloadFile($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $record = Record::findOrFail($id);
            
            if (!$record->image_path || !Storage::disk('public')->exists($record->image_path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            // Get the file name
            $fileName = basename($record->image_path);
            
            // Download the file
            return Storage::disk('public')->download($record->image_path, $fileName);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
