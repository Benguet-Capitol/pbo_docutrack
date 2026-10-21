<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee, structured like pbo-registryV2: HRIS position title, parenthetical title and status.
 * `designation` is the printed title derived from them (see titleOf), e.g. "Provincial Budget Officer".
 */
class Employee extends Model
{
    use SoftDeletes, LogsActivity;

    public const STATUSES = ['Permanent', 'Casual', 'COS'];

    /** Provincial Budget Office */
    public const PBO_OFFICE_ID = 12;

    public const TITLE_PBO = 'Provincial Budget Officer';
    public const TITLE_GOVERNOR = 'Provincial Governor';

    protected $table = 'employees';
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'name',
        'position_title',
        'parenthetical_title',
        'status',
        'office_id',
        'detailed_to_pbo',
        'designation',
    ];

    protected $casts = [
        'detailed_to_pbo' => 'boolean',
    ];

    protected $appends = ['full_position'];

    protected static function booted(): void
    {
        // designation always follows the position fields
        static::saving(function (Employee $employee) {
            if (trim((string) $employee->position_title) !== '') {
                $employee->designation = self::titleOf($employee->position_title, $employee->parenthetical_title);
            }
        });
    }

    /**
     * The printed title: the position title, except a (assistant) department head's parenthetical title,
     * e.g. "Provincial Government Department Head (Provincial Budget Officer)" → "Provincial Budget Officer".
     */
    public static function titleOf(?string $position, ?string $parenthetical): string
    {
        return trim((string) $parenthetical) !== '' && stripos((string) $position, 'Department Head') !== false
            ? trim($parenthetical)
            : trim((string) $position);
    }

    /** HRIS position_title "Administrative Aide IV (Clerk II)" → ['Administrative Aide IV', 'Clerk II']; no parentheses → [title, null] */
    public static function splitPosition(?string $title): array
    {
        $title = trim(preg_replace('/\s+/', ' ', (string) $title));

        return preg_match('/^(.*?)\s*\(([^()]+)\)\s*$/', $title, $m) && trim($m[1]) !== '' ? [trim($m[1]), trim($m[2])] : [$title, null];
    }

    /** HRIS employee type → status; null for any other type */
    public static function statusFromHris(?string $type): ?string
    {
        return match (mb_strtolower(trim((string) $type))) {
            'permanent' => 'Permanent',
            'casual' => 'Casual',
            'cos', 'contract of service', 'job order', 'jo' => 'COS',
            default => null,
        };
    }

    /** The full position, e.g. "Administrative Aide IV (Clerk II)"; older records have only their designation */
    public function getFullPositionAttribute(): string
    {
        $position = trim((string) $this->position_title);
        if ($position === '') {
            return trim((string) $this->designation);
        }

        return trim((string) $this->parenthetical_title) !== '' ? "{$position} ({$this->parenthetical_title})" : $position;
    }

    /**
     * The office's staff (Provincial Budget Office) plus the Provincial Governor, who signs and approves
     * their forms. Module dropdowns use this; the Employees page lists everyone.
     */
    public function scopePboScope(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->pboStaff()->orWhere('designation', self::TITLE_GOVERNOR));
    }

    /** Provincial Budget Office staff: its employees and those of other offices detailed to it */
    public function scopePboStaff(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('office_id', self::PBO_OFFICE_ID)->orWhere('detailed_to_pbo', true));
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class)->withTrashed();
    }
}
