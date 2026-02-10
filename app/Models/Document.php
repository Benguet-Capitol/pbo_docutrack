<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for documents
 */
class Document extends Model
{
    protected $table = 'documents';
    public $timestamps = true;

    protected $fillable = [
        'tracking_no',
        'date',
        'document_type',
        'particulars',
        'source',
        'status',
        'remarks',
        'user_id',
    ];

    /**
     * Get the user that the document is forwarded to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this document.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(DocumentTransaction::class);
    }
}

