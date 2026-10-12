<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Human-readable past-tense verbs for each action.
     */
    public const ACTION_VERBS = [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'moved to trash',
        'restored' => 'restored',
        'force_deleted' => 'permanently deleted',
        'routed' => 'routed',
        'login' => 'logged in',
        'logout' => 'logged out',
    ];

    /**
     * Write an activity log entry. Never throws: logging must not break the action being logged.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        array $properties = [],
        $user = null
    ): ?ActivityLog {
        try {
            $user = $user ?? Auth::user();
            $request = app()->runningInConsole() ? null : request();

            return ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'usertype' => $user?->usertype,
                'module' => $module,
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel !== null ? Str::limit($subjectLabel, 250) : null,
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write activity log: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log a model lifecycle event (created/updated/deleted/restored/force_deleted).
     */
    public static function logModelEvent(Model $model, string $action, array $properties = []): ?ActivityLog
    {
        $module = method_exists($model, 'getActivityModule') ? $model->getActivityModule() : class_basename($model);
        $label = method_exists($model, 'getActivityLabel') ? $model->getActivityLabel() : '#' . $model->getKey();
        $singular = method_exists($model, 'getActivitySubjectName') ? $model->getActivitySubjectName() : Str::headline(class_basename($model));
        $verb = self::ACTION_VERBS[$action] ?? $action;

        $description = "{$singular} {$label} {$verb}";

        return self::log($action, $module, $description, $model, $label, $properties);
    }
}
