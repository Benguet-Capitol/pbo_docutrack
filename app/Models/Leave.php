<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'control_no',
        'employee_id',
        'date_of_filing',
        'type_of_leave',
        'number_of_working_days_applied_for',
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
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d');
    }
}
