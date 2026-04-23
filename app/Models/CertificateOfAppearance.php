<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for certificate of appearances
 */
class CertificateOfAppearance extends Model
{
    protected $table = 'certificate_of_appearances';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'name',
        'office',
        'purpose',
        'date',
        'remarks',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
    ];

    protected $dateFormat = 'Y-m-d';

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }
}
