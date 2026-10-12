<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for activity logs (audit trail of create/update/delete/restore actions)
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'usertype',
        'module',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
