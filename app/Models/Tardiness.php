<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tardiness extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'tardiness';

    protected $fillable = [
        'control_no',
        'date_filed',
        'type',
        'requested_date',
        'employee_id',
        'requested_time',
        'reason',
        'return_time',
        'supervisor_employee_id',
        'approved_by_employee_id',
        'approved_by_title',
    ];

    protected $casts = [
        // Sent as "2026-07-20", not midnight converted to UTC ("2026-07-19T16:00:00Z"), which showed a day earlier
        'date_filed' => 'date:Y-m-d',
        'requested_date' => 'date:Y-m-d',
        'requested_time' => 'string',
    ];

    /**
     * Get the employee associated with this tardiness record
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Get the supervisor associated with this tardiness record
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id')->withTrashed();
    }

    public function getActivityModule(): string
    {
        return 'Tardiness/Undertime';
    }

    public function getActivitySubjectName(): string
    {
        return $this->type ?: 'Tardiness/Undertime';
    }
}
