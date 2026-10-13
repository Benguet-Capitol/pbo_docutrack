<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
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

    /**
     * Get all employees assigned to this pass slip.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_employee', 'pass_slip_id', 'employee_id')->withTrashed();
    }
}
