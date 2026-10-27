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

    /** Uploads live on the private "records" disk (config/filesystems.php), stored as "records/<file>" */
    public const FILE_DISK = 'records';

    protected static function booted(): void
    {
        // Keep the uploaded file while the record is in the Trash; remove it only on permanent delete
        static::forceDeleted(function (Record $record) {
            if ($record->hasFile()) {
                static::files()->delete($record->image_path);
            }
        });
    }

    /** The private disk the uploads are on */
    public static function files(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::FILE_DISK);
    }

    /** Whether the record has an uploaded file that is still there */
    public function hasFile(): bool
    {
        return (bool) $this->image_path && static::files()->exists($this->image_path);
    }

    /** The upload's full path on the server (check hasFile() first) */
    public function filePath(): string
    {
        return static::files()->path($this->image_path);
    }

    /**
     * Get the user that created the record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
