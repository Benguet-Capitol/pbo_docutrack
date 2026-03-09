<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for document checklist items
 * Represents individual items in a checklist template
 */
class DocumentChecklistItem extends Model
{
    protected $table = 'document_checklist_items';
    public $timestamps = true;

    protected $fillable = [
        'template_id',
        'item_name',
        'signatories',
        'remarks',
        'order',
        'group_letter',
        'is_subitem',
    ];

    /**
     * Get the template this item belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentChecklistTemplate::class, 'template_id');
    }

    /**
     * Get all checklist records for this item
     */
    public function records(): HasMany
    {
        return $this->hasMany(DocumentChecklistRecord::class, 'checklist_item_id');
    }
}
