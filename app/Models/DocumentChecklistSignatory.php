<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for document checklist signatories
 * Tracks which signatories have signed off on checklist items
 */
class DocumentChecklistSignatory extends Model
{
    protected $table = 'document_checklist_signatories';
    public $timestamps = true;

    protected $fillable = [
        'checklist_record_id',
        'signatory_name',
        'is_signed',
        'acting_status',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
    ];

    /**
     * Get the checklist record this signatory belongs to
     */
    public function checklistRecord(): BelongsTo
    {
        return $this->belongsTo(DocumentChecklistRecord::class, 'checklist_record_id');
    }
}
