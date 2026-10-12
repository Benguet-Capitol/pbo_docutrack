<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for local employees
 */
class Employee extends Model
{
    use SoftDeletes, LogsActivity;

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
        return $this->belongsTo(Office::class)->withTrashed();
    }
}
