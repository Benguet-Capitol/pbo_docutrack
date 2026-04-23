<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model for travel orders
 */
class TravelOrder extends Model
{
    protected $table = 'travel_orders';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'date',
        'going_to',
        'from_date',
        'to_date',
        'inclusive_dates',
        'purpose',
        'vehicle',
        'plate_number',
        'driver',
        'supervisor_employee_id',
        'approver_employee_id',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
        'from_date' => 'datetime:Y-m-d',
        'to_date' => 'datetime:Y-m-d',
        'inclusive_dates' => 'array',
        'purpose' => 'array',
    ];

    protected $dateFormat = 'Y-m-d';

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }

    /**
     * Get all employees assigned to this travel order.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'travel_order_employee', 'travel_order_id', 'employee_id');
    }

    /**
     * Get the supervisor associated with this travel order
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }
}
