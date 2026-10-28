<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A driver's trip ticket issued for a travel order (its date is the travel order's date)
 */
class TripTicket extends Model
{
    use LogsActivity;

    protected $table = 'trip_tickets';

    protected $fillable = [
        'travel_order_id',
        'control_no',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class)->withTrashed();
    }

    /**
     * The travel order's trip ticket, issued with the next control no. the first time it is asked for.
     * Numbers run through the year ("2026-09-0012"), so they are taken under a lock.
     */
    public static function issueFor(TravelOrder $travelOrder): self
    {
        return DB::transaction(function () use ($travelOrder) {
            $existing = static::where('travel_order_id', $travelOrder->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $date = Carbon::parse($travelOrder->date);
            $year = $date->format('Y');

            $lastSeries = static::where('control_no', 'like', "{$year}-%")
                ->lockForUpdate()
                ->pluck('control_no')
                ->map(fn (string $controlNo) => (int) substr($controlNo, strrpos($controlNo, '-') + 1))
                ->max() ?? 0;

            return static::create([
                'travel_order_id' => $travelOrder->id,
                'control_no' => sprintf('%s-%s-%04d', $year, $date->format('m'), $lastSeries + 1),
            ]);
        });
    }
}
