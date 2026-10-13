<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for certificate of appearances
 */
class CertificateOfAppearance extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'certificate_of_appearances';
    public $timestamps = true;

    protected $fillable = [
        'control_no',
        'name',
        'office',
        'purpose',
        'date',
        'remarks',
        'approved_by_employee_id',
        'approved_by_title',
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

    public function getActivityModule(): string
    {
        return 'Certificates of Appearance';
    }

    public function getActivitySubjectName(): string
    {
        return 'Certificate of Appearance';
    }
}
