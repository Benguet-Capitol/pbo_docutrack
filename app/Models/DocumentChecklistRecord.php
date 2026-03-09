<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for document checklist records
 * Tracks which checklist items have been checked for each document
 */
class DocumentChecklistRecord extends Model
{
    protected $table = 'document_checklist_records';
    public $timestamps = true;

    protected $fillable = [
        'document_id',
        'checklist_item_id',
        'is_checked',
        'remarks',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    /**
     * Get the document this record belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the checklist item this record references
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(DocumentChecklistItem::class, 'checklist_item_id');
    }

    /**
     * Get all signatories for this checklist record
     */
    public function signatories(): HasMany
    {
        return $this->hasMany(DocumentChecklistSignatory::class, 'checklist_record_id');
    }
}
