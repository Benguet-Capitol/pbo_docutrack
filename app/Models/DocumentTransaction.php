<?php

namespace App\Models;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTransactionFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'user_id',
        'forwarded_to_user_id',
        'forwarded_to_office_id',
        'forwarded_to_municipality_id',
        'action',
        'remarks',
        'created_at',
        'duration_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Each routing step (create/forward/receive/finalize) is recorded in the Activity Log
        static::created(function (DocumentTransaction $transaction) {
            $document = Document::withTrashed()->find($transaction->document_id);
            $trackingNo = $document?->tracking_no ?? '#' . $transaction->document_id;

            ActivityLogService::log(
                'routed',
                'Documents',
                "Document {$trackingNo}: {$transaction->action}",
                $document,
                $trackingNo,
                [
                    'attributes' => array_filter([
                        'action' => $transaction->action,
                        'remarks' => $transaction->remarks,
                        'forwarded_to_user_id' => $transaction->forwarded_to_user_id,
                        'forwarded_to_office_id' => $transaction->forwarded_to_office_id,
                        'forwarded_to_municipality_id' => $transaction->forwarded_to_municipality_id,
                    ], fn ($value) => $value !== null),
                ]
            );
        });
    }

    /**
     * Get the document this transaction belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who performed this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the user this document was forwarded to.
     */
    public function forwardedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_to_user_id')->withTrashed();
    }

    /**
     * Get the office this document was forwarded to.
     */
    public function forwardedToOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'forwarded_to_office_id')->withTrashed();
    }

    /**
     * Get the municipality this document was forwarded to.
     */
    public function forwardedToMunicipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'forwarded_to_municipality_id')->withTrashed();
    }
}
