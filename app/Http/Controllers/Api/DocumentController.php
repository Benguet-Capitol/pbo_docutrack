<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTransaction;
use App\Models\User;
use App\Models\Office;
use App\Models\Municipality;
use App\Services\RoleService;
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
            $documents = Document::with('user')
                ->with(['transactions' => function ($query) {
                    $query->latest('created_at')
                        ->with('user')
                        ->with('forwardedToUser')
                        ->with('forwardedToOffice')
                        ->with('forwardedToMunicipality');
                }])
                ->get();
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

            // Check role permission
            if (!RoleService::canCreateDocument($user)) {
                return response()->json(['error' => 'You do not have permission to create documents'], 403);
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
                'action' => 'Created / Received document ' . $document->particulars . ' with Tracking no: ' . $document->tracking_no . ' dated ' . $document->date . ' from ' . $document->source,
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
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Check role permission
            if (!RoleService::canEditDocument($user)) {
                return response()->json(['error' => 'You do not have permission to edit documents'], 403);
            }

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

            // Check role permission
            if (!RoleService::canForwardDocument($user)) {
                return response()->json(['error' => 'You do not have permission to forward documents'], 403);
            }

            $document = Document::findOrFail($id);
            
            $validated = $request->validate([
                'forward_to_type' => 'required|in:user,office,municipality',
                'forward_to_id' => 'required|numeric',
                'remarks' => 'nullable|string',
            ]);

            // Calculate duration only when forwarding to a user
            // Do NOT calculate duration when forwarding to office/municipality
            $durationHours = null;
            if ($validated['forward_to_type'] === 'user') {
                $userReceiveTransaction = DocumentTransaction::where('document_id', $id)
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($userReceiveTransaction) {
                    $receivedAt = new \DateTime($userReceiveTransaction->created_at);
                    $now = new \DateTime();
                    $interval = $receivedAt->diff($now);
                    // Convert total time to hours
                    $durationHours = ($interval->days * 24) + $interval->h;
                }
            }

            $forwardedToName = '';
            $transactionData = [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'remarks' => $validated['remarks'] ?? null,
                'duration_hours' => $durationHours,
            ];

            // Handle different forward types
            if ($validated['forward_to_type'] === 'user') {
                $forwardedUser = User::findOrFail($validated['forward_to_id']);
                $forwardedToName = $forwardedUser->name;
                // Don't change user_id - keep it with current owner
                $document->update(['status' => 'forwarded']);
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
            
            // Get the last forward transaction with relationships
            $lastForwardTransaction = DocumentTransaction::where('document_id', $id)
                ->whereRaw("action LIKE 'Forwarded%'")
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Determine who the document is being received from
            $receivedFromName = 'Unknown';
            $durationHours = null;
            
            if ($lastForwardTransaction) {
                // Check if it was forwarded to an office
                if ($lastForwardTransaction->forwarded_to_office_id) {
                    $office = Office::find($lastForwardTransaction->forwarded_to_office_id);
                    $receivedFromName = $office ? $office->office_name : 'Unknown Office';
                    
                    // Calculate duration for office/municipality forwards
                    $forwardedAt = new \DateTime($lastForwardTransaction->created_at);
                    $now = new \DateTime();
                    $interval = $forwardedAt->diff($now);
                    $durationHours = ($interval->days * 24) + $interval->h;
                }
                // Check if it was forwarded to a municipality
                elseif ($lastForwardTransaction->forwarded_to_municipality_id) {
                    $municipality = Municipality::find($lastForwardTransaction->forwarded_to_municipality_id);
                    $receivedFromName = $municipality ? $municipality->name : 'Unknown Municipality';
                    
                    // Calculate duration for office/municipality forwards
                    $forwardedAt = new \DateTime($lastForwardTransaction->created_at);
                    $now = new \DateTime();
                    $interval = $forwardedAt->diff($now);
                    $durationHours = ($interval->days * 24) + $interval->h;
                }
                // Otherwise it was forwarded to a user - use that user's name
                elseif ($lastForwardTransaction->forwarded_to_user_id) {
                    $forwardedUser = User::find($lastForwardTransaction->forwarded_to_user_id);
                    $receivedFromName = $forwardedUser ? $forwardedUser->name : 'Unknown User';
                    // No duration for user-to-user forwards
                    $durationHours = null;
                }
            } else {
                // Fallback to the current owner
                $previousUser = User::find($document->user_id);
                $receivedFromName = $previousUser ? $previousUser->name : 'Unknown';
            }
            
            // Update document: change user_id to current user and status to 'pending'
            $document->update([
                'user_id' => $user->id,
                'status' => 'pending'
            ]);
            
            // Log the receive transaction with who it came from
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Received document from ' . $receivedFromName,
                'remarks' => null,
                'duration_hours' => $durationHours,
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

            // Check role permission
            if (!RoleService::canFinalizeDocument($user)) {
                return response()->json(['error' => 'You do not have permission to finalize documents'], 403);
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
            
            // Calculate duration: find when this user received the document
            $userReceiveTransaction = DocumentTransaction::where('document_id', $id)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $durationHours = null;
            if ($userReceiveTransaction) {
                $receivedAt = new \DateTime($userReceiveTransaction->created_at);
                $now = new \DateTime();
                $interval = $receivedAt->diff($now);
                // Convert total time to hours
                $durationHours = ($interval->days * 24) + $interval->h;
            }
            
            // Log the finalize transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Document Finalized!',
                'remarks' => null,
                'duration_hours' => $durationHours,
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
