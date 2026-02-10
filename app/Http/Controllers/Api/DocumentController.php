<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents.
     */
    public function index(): JsonResponse
    {
        try {
            $documents = Document::with('user')->get();
            return response()->json($documents);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'tracking_no' => 'required|string|unique:documents,tracking_no',
                'date' => 'required|date',
                'document_type' => 'required|string',
                'particulars' => 'required|string',
                'source' => 'required|string',
                'status' => 'sometimes|string|in:pending,finalized',
                'remarks' => 'nullable|string',
            ]);

            // Automatically set user_id to the authenticated user's numeric ID
            $validated['user_id'] = $user->id;
            
            $document = Document::create($validated);
            
            // Log the creation transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'created',
                'remarks' => null,
            ]);
            
            return response()->json($document->load('user'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a document.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            
            $validated = $request->validate([
                'tracking_no' => 'sometimes|string|unique:documents,tracking_no,' . $id,
                'date' => 'sometimes|date',
                'document_type' => 'sometimes|string',
                'particulars' => 'sometimes|required|string',
                'source' => 'sometimes|required|string',
                'status' => 'sometimes|string|in:pending,finalized',
                'remarks' => 'nullable|string',
                'user_id' => 'nullable|exists:users,id',
            ]);

            $document->update($validated);
            return response()->json($document->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a document.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $document->delete();
            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Forward a document to another user.
     */
    public function forward(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $document = Document::findOrFail($id);
            
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'remarks' => 'nullable|string',
            ]);

            // Update the document's forwarded user
            $document->update(['user_id' => $validated['user_id']]);
            
            // Log the forward transaction with forwarded_to_user_id
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'forwarded_to_user_id' => $validated['user_id'],
                'action' => 'forwarded',
                'remarks' => $validated['remarks'] ?? null,
            ]);
            
            return response()->json($document->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Finalize a document.
     */
    public function finalize(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $document = Document::findOrFail($id);
            
            // Check if already finalized
            if ($document->status === 'finalized') {
                return response()->json(['error' => 'Document has already been finalized'], 400);
            }

            // Update the document status to finalized
            $document->update(['status' => 'finalized']);
            
            // Log the finalize transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'finalized',
                'remarks' => null,
            ]);
            
            return response()->json($document->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all transactions for a document.
     */
    public function getTransactions($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $transactions = DocumentTransaction::where('document_id', $id)
                ->with('user', 'forwardedToUser')
                ->orderBy('created_at', 'asc')
                ->get();
            
            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
