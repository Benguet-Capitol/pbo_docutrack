<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for local offices
 */
class Office extends Model
{
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
