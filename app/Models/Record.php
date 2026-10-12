<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Model for records
 */
class Record extends Model
{
    use SoftDeletes, LogsActivity;

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

    protected static function booted(): void
    {
        // Keep the uploaded file while the record is in the Trash; remove it only on permanent delete
        static::forceDeleted(function (Record $record) {
            if ($record->image_path && Storage::disk('public')->exists($record->image_path)) {
                Storage::disk('public')->delete($record->image_path);
            }
        });
    }

    /**
     * Get the user that created the record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
