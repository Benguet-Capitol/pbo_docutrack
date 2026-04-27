<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for time slips
 */
class TimeSlip extends Model
{
    protected $table = 'time_slips';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'requesting_employee_id',
        'date',
        'time_in_am',
        'time_out_am',
        'time_in_pm',
        'time_out_pm',
        'reason',
        'certified_by_employee_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    /**
     * Get the requesting employee.
     */
    public function requestingEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requesting_employee_id');
    }

    /**
     * Get the employee who certified this time slip.
     */
    public function certifiedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'certified_by_employee_id');
    }
}
