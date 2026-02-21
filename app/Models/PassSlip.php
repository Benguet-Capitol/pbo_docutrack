<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model for pass slips
 */
class PassSlip extends Model
{
    protected $table = 'pass_slips';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'date',
        'requested_time',
        'purpose',
        'location',
        'expected_return_time',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get all employees assigned to this pass slip.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_employee', 'pass_slip_id', 'employee_id');
    }
}
