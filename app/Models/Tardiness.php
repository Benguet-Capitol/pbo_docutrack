<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tardiness extends Model
{
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
    ];

    protected $casts = [
        'date_filed' => 'date',
        'requested_date' => 'date',
        'requested_time' => 'string',
    ];

    /**
     * Get the employee associated with this tardiness record
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
