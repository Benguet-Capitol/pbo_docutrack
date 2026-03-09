<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for document checklist templates
 * Stores the template structure for checklists based on document type
 */
class DocumentChecklistTemplate extends Model
{
    protected $table = 'document_checklist_templates';
    public $timestamps = true;

    protected $fillable = [
        'document_type',
        'description',
        'status',
    ];

    /**
     * Get all checklist items for this template
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentChecklistItem::class, 'template_id')
                    ->orderBy('order', 'asc');
    }
}
