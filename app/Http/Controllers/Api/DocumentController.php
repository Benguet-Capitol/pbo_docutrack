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
     * Generate the next tracking number based on current month and highest existing number.
     * Format: YYYY-MM-NNNN (e.g., 2026-03-0001)
     * Finds the highest existing sequence number and increments it (handles deleted records)
     */
    public function generateTrackingNo(): JsonResponse
    {
        try {
            $now = new \DateTime();
            $year = $now->format('Y');
            $month = $now->format('m');
            $yearMonth = "{$year}-{$month}";

            // Get all tracking numbers with same year-month prefix
            $existingNumbers = Document::where('tracking_no', 'like', "{$yearMonth}-%")
                ->pluck('tracking_no')
                ->map(function($trackingNo) {
                    // Extract the numeric part after the last dash
                    $parts = explode('-', $trackingNo);
                    return (int) end($parts);
                })
                ->toArray();

            // Find the highest number, or start at 0 if none exist
            $maxNumber = count($existingNumbers) > 0 ? max($existingNumbers) : 0;
            
            $series = str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
            $trackingNo = "{$yearMonth}-{$series}";

            return response()->json(['tracking_no' => $trackingNo]);
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
                'sb_no' => 'nullable|string',
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
                'action' => 'Created / Received document ' . $document->particulars . ' from ' . $document->source . ' with Tracking no: ' . $document->tracking_no . ' dated ' . $document->date,
                'remarks' => $validated['remarks'] ?? null,
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
                'sb_no' => 'nullable|string',
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
                    $durationHours = $this->calculateBusinessHours($receivedAt, $now);
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
                    $durationHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
                }
                // Check if it was forwarded to a municipality
                elseif ($lastForwardTransaction->forwarded_to_municipality_id) {
                    $municipality = Municipality::find($lastForwardTransaction->forwarded_to_municipality_id);
                    $receivedFromName = $municipality ? $municipality->name : 'Unknown Municipality';
                    
                    // Calculate duration for office/municipality forwards
                    $forwardedAt = new \DateTime($lastForwardTransaction->created_at);
                    $now = new \DateTime();
                    $interval = $forwardedAt->diff($now);
                    $durationHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
                }
                // Otherwise it was forwarded to a user - use the forwarder's name (the user_id from the transaction, not the recipient)
                elseif ($lastForwardTransaction->forwarded_to_user_id) {
                    $forwardingUser = User::find($lastForwardTransaction->user_id);
                    $receivedFromName = $forwardingUser ? $forwardingUser->name : 'Unknown User';
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
                'remarks' => $request->input('remarks'),
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
                return response()->json(['error' => 'You do not have permission to end  document transactions'], 403);
            }

            $document = Document::findOrFail($id);
            
            // Check if already finalized
            if ($document->status === 'finalized') {
                return response()->json(['error' => 'Document transaction has already ended'], 400);
            }

            // Only allow finalization from pending status
            if ($document->status !== 'pending') {
                return response()->json(['error' => 'Only pending documents can have their transactions ended'], 400);
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
                $durationHours = $this->calculateBusinessHours($receivedAt, $now);
            }
            
            // Log the finalize transaction
            DocumentTransaction::create([
                'document_id' => $document->id,
                'user_id' => $user->id,
                'action' => 'Document Transaction Ended!',
                'remarks' => $request->input('remarks'),
                'duration_hours' => $durationHours,
            ]);
            
            return response()->json($document->load('user'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate business hours between two dates, excluding weekends
     */
    private function calculateBusinessHours(\DateTime $startDate, \DateTime $endDate): float
    {
        $businessHours = 0;
        $current = clone $startDate;
        
        // Iterate through each day
        while ($current < $endDate) {
            $dayOfWeek = (int)$current->format('w'); // 0 = Sunday, 6 = Saturday
            
            // Only count business days (Monday-Friday)
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                $nextDay = clone $current;
                $nextDay->modify('+1 day');
                $nextDay->setTime(0, 0, 0);
                
                if ($nextDay <= $endDate) {
                    // Full business day worth of hours
                    $msToMidnight = $nextDay->getTimestamp() - $current->getTimestamp();
                    $businessHours += $msToMidnight / 3600; // Convert seconds to hours
                } else {
                    // Partial day - calculate hours from current time to end time
                    $msElapsed = $endDate->getTimestamp() - $current->getTimestamp();
                    $businessHours += $msElapsed / 3600; // Convert seconds to hours
                }
            }
            
            $current->modify('+1 day');
            $current->setTime(0, 0, 0);
        }
        
        return $businessHours;
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

    /**
     * Get checklist template for a document type
     */
    public function getChecklistTemplate($documentType): JsonResponse
    {
        try {
            $template = \App\Models\DocumentChecklistTemplate::where('document_type', $documentType)
                ->with(['items' => function ($query) {
                    $query->orderBy('order', 'asc');
                }])
                ->first();
            
            if (!$template) {
                return response()->json(['items' => []], 200);
            }
            
            return response()->json($template);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save checklist records for a document
     */
    public function saveChecklist(Request $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $document = Document::findOrFail($id);
            
            $validated = $request->validate([
                'checklist_items' => 'required|array',
                'checklist_items.*.checklist_item_id' => 'required|integer|exists:document_checklist_items,id',
                'checklist_items.*.is_checked' => 'required|boolean',
                'checklist_items.*.remarks' => 'nullable|string',
                'checklist_items.*.signatories' => 'nullable|array',
                'checklist_items.*.signatories.*.name' => 'required|string',
                'checklist_items.*.signatories.*.is_checked' => 'required|boolean',
                'checklist_items.*.signatories.*.acting_status' => 'nullable|string',
            ]);

            // Delete existing checklist records for this document (cascades to signatories)
            $document->checklistRecords()->delete();

            // Create new checklist records with signatories
            foreach ($validated['checklist_items'] as $item) {
                $checklistRecord = \App\Models\DocumentChecklistRecord::create([
                    'document_id' => $document->id,
                    'checklist_item_id' => $item['checklist_item_id'],
                    'is_checked' => $item['is_checked'],
                    'remarks' => $item['remarks'] ?? null,
                ]);

                // Create signatory records for this checklist item
                if (!empty($item['signatories'])) {
                    foreach ($item['signatories'] as $signatory) {
                        \App\Models\DocumentChecklistSignatory::create([
                            'checklist_record_id' => $checklistRecord->id,
                            'signatory_name' => $signatory['name'],
                            'is_signed' => $signatory['is_checked'],
                            'acting_status' => $signatory['acting_status'] ?? null,
                        ]);
                    }
                }
            }

            return response()->json(['message' => 'Checklist saved successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get existing checklist records for a document
     */
    public function getChecklistRecords($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            
            // Get checklist records with their signatories
            $records = \App\Models\DocumentChecklistRecord::where('document_id', $document->id)
                ->with(['checklistItem', 'signatories'])
                ->get();
            
            // Transform records into the structure needed by the Vue component
            $checklistData = $records->map(function ($record) {
                return [
                    'checklist_item_id' => $record->checklist_item_id,
                    'is_checked' => $record->is_checked,
                    'remarks' => $record->remarks ? json_decode($record->remarks, true) : [],
                    'signatories' => $record->signatories->map(function ($signatory) {
                        return [
                            'name' => $signatory->signatory_name,
                            'is_checked' => $signatory->is_signed,
                            'acting_status' => $signatory->acting_status,
                        ];
                    })->toArray(),
                ];
            });
            
            return response()->json($checklistData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
