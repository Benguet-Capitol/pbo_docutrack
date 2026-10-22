<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'control_no',
        'employee_id',
        'date_of_filing',
        'salary',
        'type_of_leave',
        'number_of_working_days_applied_for',
        'is_half_day',
        'half_day_period',
        'inclusive_dates',
        'off_days',
        'within_philippines',
        'purpose',
        'in_hospital',
        'illness',
        'completion_type',
        'other_type',
        'certified_by_employee_id',
        'certified_by_title',
        'recommended_by_employee_id',
        'recommended_by_title',
        'approved_by_employee_id',
        'approved_by_title',
        'recommendation',
        'disapproval_reason',
    ];

    /**
     * CS Form No. 6 signatories: 7.A certification of leave credits, 7.B recommendation and the authorized official.
     * Each signs as the officer, OIC or Acting; with nothing stored, the current officer signs with the default title.
     * Keep in sync with LEAVE_SIGNATORIES in resources/js/Pages/Leaves/Composables/useLeaveSignatories.ts.
     */
    public const SIGNATORIES = [
        'certified_by' => Employee::TITLE_PHRMDO,
        'recommended_by' => Employee::TITLE_PBO,
        'approved_by' => Employee::TITLE_GOVERNOR,
    ];

    /**
     * Officers who can sign: the authorized official is the Provincial Governor for a department head, and the
     * Provincial Administrator or the Assistant Provincial Administrator for other staff.
     */
    public static function signatoryOfficers(string $signatory): array
    {
        return $signatory === 'approved_by'
            ? [Employee::TITLE_GOVERNOR, Employee::TITLE_PA, Employee::TITLE_APA]
            : [self::SIGNATORIES[$signatory]];
    }

    /** Each officer's title, "OIC, <title>" and "Acting <title>" */
    public static function signatoryTitles(string $signatory): array
    {
        return collect(self::signatoryOfficers($signatory))
            ->flatMap(fn ($title) => [$title, "OIC, {$title}", "Acting {$title}"])
            ->all();
    }

    /**
     * Who signs when nothing is stored: [employee, title]. The authorized official depends on the applicant:
     * the Provincial Governor for a department head, else the Provincial Administrator (or the Assistant when
     * there is none).
     */
    public static function defaultSignatory(string $signatory, ?Employee $applicant): array
    {
        if ($signatory !== 'approved_by' || $applicant?->isDepartmentHead()) {
            $title = $signatory === 'approved_by' ? Employee::TITLE_GOVERNOR : self::SIGNATORIES[$signatory];

            return [Employee::official($title), $title];
        }
        if ($administrator = Employee::official(Employee::TITLE_PA)) {
            return [$administrator, Employee::TITLE_PA];
        }

        return [Employee::official(Employee::TITLE_APA), Employee::TITLE_APA];
    }

    /** Printed name and title of a signatory (falls back to the default signatory) */
    public function signatory(string $signatory): array
    {
        [$official, $title] = self::defaultSignatory($signatory, $this->employee);
        $employee = $this->{"{$signatory}_employee_id"}
            ? Employee::withTrashed()->find($this->{"{$signatory}_employee_id"})
            : $official;

        return [
            'name' => $employee?->name ?? '',
            'title' => $this->{"{$signatory}_title"} ?: $title,
        ];
    }

    protected $casts = [
        'inclusive_dates' => 'array',
        'off_days' => 'array',
        'date_of_filing' => 'date:Y-m-d',
        'is_half_day' => 'boolean',
        'salary' => 'decimal:2',
    ];

    /**
     * Days an employee may use per year (by year of filing) for these leave types.
     * Keep in sync with LEAVE_LIMITS in resources/js/Composables/useLeaveCredits.ts.
     */
    public const YEARLY_LIMITS = [
        'Mandatory/Forced Leave' => 5,
        'Special Privilege Leave' => 3,
        'Wellness Leave' => 5,
    ];

    /** Every leave type in this leave's application (rows sharing its control number), oldest first */
    public function applicationRows(): \Illuminate\Support\Collection
    {
        $rows = static::where('control_no', $this->control_no)->orderBy('id')->get();

        return $rows->isEmpty() ? collect([$this]) : $rows;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Days a leave counts against the yearly limit: a half day is 0.5, otherwise the working days applied for.
     */
    public static function daysCounted(bool $isHalfDay, $workingDays): float
    {
        return $isHalfDay ? 0.5 : (float) $workingDays;
    }

    /**
     * Days of a leave type an employee has already filed in a year (trashed leaves don't count).
     */
    /**
     * The working days a leave covers (Y-m-d): every date in its inclusive dates and ranges, without its off days
     * (Saturdays and Sundays unless others are given). Mirrors leaveWorkingDates() in useLeaveFormPrint.ts.
     */
    public static function workingDates(array $inclusiveDates, ?array $offDays): array
    {
        $weekdays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $text = mb_strtolower(implode(' ', $offDays ?? []));
        $off = array_keys(array_filter($weekdays, fn ($day) => str_contains($text, $day)));
        if (!$off && !$offDays) $off = [0, 6];

        $dates = [];
        foreach ($inclusiveDates as $entry) {
            $parts = array_map('trim', explode(' - ', (string) $entry));
            try {
                $day = \Illuminate\Support\Carbon::parse($parts[0])->startOfDay();
                $end = \Illuminate\Support\Carbon::parse($parts[1] ?? $parts[0])->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            for ($guard = 0; $day->lte($end) && $guard < 400; $guard++, $day->addDay()) {
                if (!in_array($day->dayOfWeek, $off, true)) $dates[$day->format('Y-m-d')] = true;
            }
        }

        return array_keys($dates);
    }

    /**
     * Days an employee is already on leave, from live leaves that weren't disapproved: date => [control no, type,
     * half day period or null]. Used to stop filing the same day twice.
     */
    public static function daysOnLeave(int $employeeId, array $excludeLeaveIds = []): array
    {
        $days = [];
        static::where('employee_id', $employeeId)
            ->where(fn ($query) => $query->whereNull('recommendation')->orWhere('recommendation', '!=', 'disapproval'))
            ->when($excludeLeaveIds, fn ($query) => $query->whereKeyNot($excludeLeaveIds))
            ->get(['control_no', 'type_of_leave', 'is_half_day', 'half_day_period', 'inclusive_dates', 'off_days'])
            ->each(function (Leave $leave) use (&$days) {
                foreach (static::workingDates($leave->inclusive_dates ?? [], $leave->off_days) as $date) {
                    $days[$date][] = [$leave->control_no, $leave->type_of_leave, $leave->is_half_day ? $leave->half_day_period : null];
                }
            });

        return $days;
    }

    /** Two leaves on the same day clash, unless both are half days on different halves (AM and PM) */
    public static function halvesClash(?string $a, ?string $b): bool
    {
        return !($a && $b && $a !== $b);
    }

    public static function usedDays(int $employeeId, string $type, int $year, int|array|null $excludeLeaveIds = null): float
    {
        $exclude = array_filter((array) $excludeLeaveIds);

        return (float) static::query()
            ->where('employee_id', $employeeId)
            ->where('type_of_leave', $type)
            ->whereYear('date_of_filing', $year)
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude))
            ->get(['is_half_day', 'number_of_working_days_applied_for'])
            ->sum(fn (Leave $leave) => static::daysCounted((bool) $leave->is_half_day, $leave->number_of_working_days_applied_for));
    }

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d');
    }
}
