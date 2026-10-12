<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoticeOfMeeting extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'employee_id',
        'date',
        'time',
        'particulars',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function getActivityModule(): string
    {
        return 'Notices of Meeting';
    }

    public function getActivitySubjectName(): string
    {
        return 'Notice of Meeting';
    }

    public function getActivityLabel(): string
    {
        $date = $this->date ? $this->date->format('Y-m-d') : '';

        return trim("{$this->particulars} ({$date})");
    }
}