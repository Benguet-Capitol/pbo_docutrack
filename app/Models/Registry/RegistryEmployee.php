<?php

namespace App\Models\Registry;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for referencing employees from pbo-registry database
 */
class RegistryEmployee extends Model
{
    protected $connection = 'pbo_registry';
    protected $table = 'employees';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'name',
        'fk_office_id',
        'designation',
    ];

    /**
     * Get the office this employee belongs to.
     */
    public function office()
    {
        return $this->belongsTo(RegistryOffice::class, 'fk_office_id');
    }
}
