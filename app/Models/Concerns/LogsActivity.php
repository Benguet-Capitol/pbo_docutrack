<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogService;
use Illuminate\Support\Str;

/**
 * Automatically writes an activity log entry whenever the model is
 * created, updated, soft deleted, restored or permanently deleted.
 *
 * Models may override getActivityModule(), getActivitySubjectName() and
 * getActivityLabel() to customize how they appear in the Activity Log.
 */
trait LogsActivity
{
    /**
     * Attributes never written to the log (in addition to the model's $hidden).
     */
    protected static array $activityIgnoredAttributes = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLogService::logModelEvent($model, 'created', [
                'attributes' => $model->activityLoggableValues($model->getAttributes()),
            ]);
        });

        static::updated(function ($model) {
            $changes = $model->activityLoggableValues($model->getChanges());

            // restore() also triggers an update of deleted_at; that is logged as "restored" instead
            if (empty($changes)) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getRawOriginal($key);
            }

            ActivityLogService::logModelEvent($model, 'updated', [
                'old' => $old,
                'new' => $changes,
            ]);
        });

        static::deleted(function ($model) {
            $forced = !method_exists($model, 'isForceDeleting') || $model->isForceDeleting();

            ActivityLogService::logModelEvent($model, $forced ? 'force_deleted' : 'deleted', [
                'attributes' => $model->activityLoggableValues($model->getAttributes()),
            ]);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                ActivityLogService::logModelEvent($model, 'restored');
            });
        }
    }

    /**
     * Strip timestamps and sensitive/hidden attributes before logging.
     */
    public function activityLoggableValues(array $values): array
    {
        $ignored = array_merge(static::$activityIgnoredAttributes, $this->getHidden());

        return array_diff_key($values, array_flip($ignored));
    }

    /**
     * Module name shown in the Activity Log (e.g. "Pass Slips").
     */
    public function getActivityModule(): string
    {
        return Str::headline(Str::plural(class_basename($this)));
    }

    /**
     * Singular name of the record type (e.g. "Pass Slip").
     */
    public function getActivitySubjectName(): string
    {
        return Str::headline(class_basename($this));
    }

    /**
     * Short identifier for the record (control number, name, ...).
     */
    public function getActivityLabel(): string
    {
        foreach (['control_no', 'tracking_no', 'record_no', 'name', 'office_name', 'title'] as $attribute) {
            if (!empty($this->getAttribute($attribute))) {
                return (string) $this->getAttribute($attribute);
            }
        }

        return '#' . $this->getKey();
    }
}
