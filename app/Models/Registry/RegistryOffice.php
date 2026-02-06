<?php

namespace App\Models\Registry;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for referencing offices from pbo-registry database
 */
class RegistryOffice extends Model
{
    protected $connection = 'pbo_registry';
    protected $table = 'offices';
    public $timestamps = false;

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
    public function employees()
    {
        return $this->hasMany(RegistryEmployee::class, 'fk_office_id');
    }

    /**
     * Get all users in this office.
     */
    public function users()
    {
        return $this->hasMany(RegistryUser::class, 'fk_office_id');
    }
}
