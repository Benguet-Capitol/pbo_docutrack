<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for municipalities
 */
class Municipality extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'municipalities';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'city_class',
        'municipal_budget_officer',
        'representative',
    ];
}
