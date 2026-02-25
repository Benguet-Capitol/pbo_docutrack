<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for records
 */
class Record extends Model
{
    protected $table = 'records';
    public $timestamps = true;

    protected $fillable = [
        'record_no',
        'record_type',
        'record_subtype',
        'title',
        'remarks',
        'image_path',
        'user_id',
    ];

    /**
     * Get the user that created the record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
