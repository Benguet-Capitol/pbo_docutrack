<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for local employees
 */
class Employee extends Model
{
    protected $table = 'employees';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'name',
        'office_id',
        'designation',
    ];

    /**
     * Get the office this employee belongs to.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
