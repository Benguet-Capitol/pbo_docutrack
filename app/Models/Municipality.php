<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for municipalities
 */
class Municipality extends Model
{
    protected $table = 'municipalities';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'municipal_budget_officer',
        'representative',
    ];
}
