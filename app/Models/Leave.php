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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d');
    }
}
