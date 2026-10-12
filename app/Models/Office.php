<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for local offices
 */
class Office extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'offices';
    public $timestamps = true;

    protected $fillable = [
        'office_abbreviation',
        'office_name',
        'sub_office',
        'fund',
        'fpp_code',
        'responsibility_code',
        'branch',
    ];

    /**
     * Get all employees in this office.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
