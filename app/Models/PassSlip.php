<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for pass slips
 */
class PassSlip extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'pass_slips';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'date',
        'inclusive_dates',
        'requested_time',
        'purpose',
        'location',
        'expected_return_time',
        'remarks',
        'vehicle',
        'recommending_approval_employee_id',
        'approved_by_employee_id',
        'approved_by_title',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'inclusive_dates' => 'array',
    ];

    /** Every immediate supervisor recommending approval, in the form's order (the list and print previews read the ids) */
    protected $appends = ['recommending_approval_employee_ids'];

    protected $hidden = ['recommendingApprovals'];

    /**
     * Get all employees assigned to this pass slip.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_employee', 'pass_slip_id', 'employee_id')->withTrashed();
    }

    /**
     * The immediate supervisors recommending approval: several when the requesting employees are under different
     * supervisors. recommending_approval_employee_id is the first of them.
     */
    public function recommendingApprovals(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_recommending_approvals', 'pass_slip_id', 'employee_id')
            ->withPivot('position')->withTimestamps()->orderBy('pass_slip_recommending_approvals.position')->withTrashed();
    }

    public function getRecommendingApprovalEmployeeIdsAttribute(): array
    {
        $ids = $this->exists ? $this->recommendingApprovals->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        // A slip saved before several supervisors were possible: its one supervisor
        return $ids ?: array_values(array_filter([$this->recommending_approval_employee_id]));
    }

    /** Save the supervisors in the given order; the first one stays in recommending_approval_employee_id */
    public function syncRecommendingApprovals(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        $old = $this->wasRecentlyCreated ? $ids : $this->recommending_approval_employee_ids;
        $this->recommendingApprovals()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        $this->unsetRelation('recommendingApprovals');

        // The supervisors live in their own table, so the model's own "updated" log doesn't see them: log a change
        if ($old !== $ids) {
            $names = Employee::withTrashed()->whereIn('id', [...$old, ...$ids])->pluck('name', 'id');
            $list = fn (array $list) => implode(', ', array_map(fn ($id) => $names[$id] ?? "#{$id}", $list)) ?: null;
            ActivityLogService::logModelEvent($this, 'updated', [
                'old' => ['recommending_approval' => $list($old)],
                'new' => ['recommending_approval' => $list($ids)],
            ]);
        }
    }

    /** The supervisors a request names: the list when given, else the one supervisor field */
    public static function recommendingApprovalIds(array $validated): array
    {
        if (array_key_exists('recommending_approval_employee_ids', $validated) && $validated['recommending_approval_employee_ids'] !== null) {
            return array_values(array_unique(array_map('intval', $validated['recommending_approval_employee_ids'])));
        }

        return array_values(array_filter([$validated['recommending_approval_employee_id'] ?? null]));
    }
}
