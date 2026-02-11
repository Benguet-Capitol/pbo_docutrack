<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTransaction;
use App\Models\User;
use App\Models\Office;
use App\Models\Municipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'status' => 'sometimes|string|in:created,forwarded,pending,finalized',
                'remarks' => 'nullable|string',
            ]);

            // Automatically set user_id to the authenticated user's numeric ID
            $validated['user_id'] = $user->id;
            // Set initial status to 'created'
            $validated['status'] = 'created';
            
            $document = Document::create($validated);
            
            // Log the creation transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Created document ' . $document->document_type . ' with Tracking no: ' . $document->tracking_no . ' dated ' . $document->date . ' from ' . $document->source,
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
                'status' => 'sometimes|string|in:created,forwarded,pending,finalized',
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
     * Forward a document to another user, office, or municipality.
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
                'forward_to_type' => 'required|in:user,office,municipality',
                'forward_to_id' => 'required|numeric',
                'remarks' => 'nullable|string',
            ]);

            $forwardedToName = '';
            $transactionData = [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'remarks' => $validated['remarks'] ?? null,
            ];

            // Handle different forward types
            if ($validated['forward_to_type'] === 'user') {
                $forwardedUser = User::findOrFail($validated['forward_to_id']);
                $forwardedToName = $forwardedUser->name;
                $document->update(['user_id' => $validated['forward_to_id'], 'status' => 'forwarded']);
                $transactionData['forwarded_to_user_id'] = $validated['forward_to_id'];
            } elseif ($validated['forward_to_type'] === 'office') {
                $office = Office::findOrFail($validated['forward_to_id']);
                $forwardedToName = $office->office_name;
                $document->update(['status' => 'forwarded']);
                $transactionData['forwarded_to_office_id'] = $validated['forward_to_id'];
            } elseif ($validated['forward_to_type'] === 'municipality') {
                $municipality = Municipality::findOrFail($validated['forward_to_id']);
                $forwardedToName = $municipality->name;
                $document->update(['status' => 'forwarded']);
                $transactionData['forwarded_to_municipality_id'] = $validated['forward_to_id'];
            }

            $transactionData['action'] = 'Forwarded document to ' . $forwardedToName;
            
            // Log the forward transaction
            DocumentTransaction::create($transactionData);
            
            return response()->json($document->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function receive(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $document = Document::findOrFail($id);
            
            // Get the last forward transaction to find who forwarded it
            $lastForwardTransaction = DocumentTransaction::where('document_id', $id)
                ->whereRaw("action LIKE 'Forwarded%'")
                ->orderBy('created_at', 'desc')
                ->first();
            
            $forwarderName = 'Unknown';
            if ($lastForwardTransaction) {
                if ($lastForwardTransaction->forwarded_to_user_id) {
                    $forwarder = User::find($lastForwardTransaction->forwarded_to_user_id);
                    $forwarderName = $forwarder ? $forwarder->name : 'Unknown';
                } elseif ($lastForwardTransaction->forwarded_to_office_id) {
                    $forwarder = Office::find($lastForwardTransaction->forwarded_to_office_id);
                    $forwarderName = $forwarder ? $forwarder->office_name : 'Unknown';
                } elseif ($lastForwardTransaction->forwarded_to_municipality_id) {
                    $forwarder = Municipality::find($lastForwardTransaction->forwarded_to_municipality_id);
                    $forwarderName = $forwarder ? $forwarder->name : 'Unknown';
                }
            }
            
            // Update document status to 'pending'
            $document->update(['status' => 'pending']);
            
            // Log the receive transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Received document from ' . $forwarderName,
                'remarks' => null,
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

            // Only allow finalization from pending status
            if ($document->status !== 'pending') {
                return response()->json(['error' => 'Only pending documents can be finalized'], 400);
            }

            // Update the document status to finalized
            $document->update(['status' => 'finalized']);
            
            // Log the finalize transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Document Finalized!',
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
                ->with('user', 'forwardedToUser', 'forwardedToOffice', 'forwardedToMunicipality')
                ->orderBy('created_at', 'asc')
                ->get();
            
            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
