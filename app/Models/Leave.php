<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'control_no',
        'employee_id',
        'date_of_filing',
        'type_of_leave',
        'number_of_working_days_applied_for',
        'is_half_day',
        'half_day_period',
        'inclusive_dates',
        'off_days',
        'within_philippines',
        'purpose',
        'in_hospital',
        'illness',
        'completion_type',
        'other_type',
    ];

    protected $casts = [
        'inclusive_dates' => 'array',
        'off_days' => 'array',
        'date_of_filing' => 'date:Y-m-d',
        'is_half_day' => 'boolean',
    ];

    /**
     * Days an employee may use per year (by year of filing) for these leave types.
     * Keep in sync with LEAVE_LIMITS in resources/js/Composables/useLeaveCredits.ts.
     */
    public const YEARLY_LIMITS = [
        'Mandatory/Forced Leave' => 5,
        'Special Privilege Leave' => 3,
        'Wellness Leave' => 5,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Days a leave counts against the yearly limit: a half day is 0.5, otherwise the working days applied for.
     */
    public static function daysCounted(bool $isHalfDay, $workingDays): float
    {
        return $isHalfDay ? 0.5 : (float) $workingDays;
    }

    /**
     * Days of a leave type an employee has already filed in a year (trashed leaves don't count).
     */
    public static function usedDays(int $employeeId, string $type, int $year, ?int $excludeLeaveId = null): float
    {
        return (float) static::query()
            ->where('employee_id', $employeeId)
            ->where('type_of_leave', $type)
            ->whereYear('date_of_filing', $year)
            ->when($excludeLeaveId, fn ($query) => $query->whereKeyNot($excludeLeaveId))
            ->get(['is_half_day', 'number_of_working_days_applied_for'])
            ->sum(fn (Leave $leave) => static::daysCounted((bool) $leave->is_half_day, $leave->number_of_working_days_applied_for));
    }

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d');
    }
}
