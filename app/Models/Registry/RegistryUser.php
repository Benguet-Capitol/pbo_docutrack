<?php

namespace App\Models\Registry;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for referencing users from pbo-registry database
 */
class RegistryUser extends Model
{
    protected $connection = 'pbo_registry';
    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'username',
        'password',
        'usertype',
        'office',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the office this user belongs to.
     */
    public function office()
    {
        return $this->belongsTo(RegistryOffice::class, 'office');
    }
}
