<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Paginated, filterable list of activity log entries (newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'nullable|string',
            'action' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:5|max:200',
        ]);

        try {
            $query = ActivityLog::query()
                ->when($validated['module'] ?? null, fn ($q, $module) => $q->where('module', $module))
                ->when($validated['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
                ->when($validated['user_id'] ?? null, fn ($q, $userId) => $q->where('user_id', $userId))
                ->when($validated['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
                ->when($validated['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
                ->when($validated['search'] ?? null, function ($q, $search) {
                    $q->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhere('subject_label', 'like', "%{$search}%")
                            ->orWhere('user_name', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($validated['per_page'] ?? 25));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Distinct modules, actions and users for the filter dropdowns.
     */
    public function filters(): JsonResponse
    {
        return response()->json([
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'users' => ActivityLog::query()
                ->whereNotNull('user_id')
                ->select('user_id', 'user_name')
                ->distinct()
                ->orderBy('user_name')
                ->get()
                ->unique('user_id')
                ->values(),
        ]);
    }
}
