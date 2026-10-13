<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A permission the application checks (e.g. "pass_slips.edit").
 * The catalog is defined by migrations, since each key must be enforced in code; only role assignments are editable.
 */
class Permission extends Model
{
    protected $fillable = [
        'key',
        'label',
        'group',
        'sort_order',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
