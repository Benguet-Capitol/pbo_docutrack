<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\NoticeOfMeeting;
use App\Models\PassSlip;
use App\Models\Tardiness;
use App\Models\TravelOrder;
use App\Support\EmployeeReferences;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The Locator Chart's status per employee per day, for the Shared Locator API. Follows the rules of the page
 * (Pages/LocatorChart/Index.vue): a weekend is an Off Day; otherwise undertime, then leave, then pass slip, then
 * travel order, then meetings, then Present. Keep the two in step when one changes.
 *
 * Only what the chart needs leaves this class: a leave shows as "Leave" without its type, and undertime without
 * its reason, as for anyone who can't open those modules.
 */
class LocatorChart
{
    /** Pinned to the top of the roster, ahead of the alphabetical order */
    private const PRIORITY_DESIGNATIONS = [
        'Provincial Budget Officer',
        'Provincial Government Department Head (Provincial Budget Officer)',
    ];

    private const NAME_SUFFIXES = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];

    /** @return array{from: string, to: string, days: string[], employees: array<int, array>} */
    public function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $days[] = $day->toDateString();
        }

        // Newest first, so the record shown when two overlap is the one the page shows
        $undertime = Tardiness::latest('created_at')->get(['id', 'employee_id', 'requested_date', 'requested_time', 'return_time']);
        $leaves = Leave::latest('id')->get(['id', 'employee_id', 'inclusive_dates', 'recommendation'])
            ->reject(fn (Leave $leave) => $leave->recommendation === 'disapproval');
        $passSlips = PassSlip::with('employees:employees.id')->latest('date')->get(['id', 'date', 'inclusive_dates', 'purpose', 'location']);
        $travelOrders = TravelOrder::with('employees:employees.id')->latest('date')->get(['id', 'date', 'inclusive_dates', 'purpose', 'going_to']);
        $meetings = NoticeOfMeeting::whereBetween('date', [$from->toDateString(), $to->toDateString()])->orderBy('time')->get(['id', 'employee_id', 'date', 'time', 'particulars']);

        $roster = $this->roster($undertime, $leaves, $passSlips, $travelOrders, $meetings);

        $undertimeByDay = [];
        foreach ($undertime as $record) {
            $undertimeByDay[$record->employee_id][$record->requested_date?->toDateString()] ??= $record;
        }
        $leaveByDay = $this->index($leaves->map(fn ($leave) => [$leave, [$leave->employee_id]]), $days);
        $passSlipByDay = $this->index($passSlips->map(fn ($slip) => [$slip, $slip->employees->pluck('id')->all()]), $days);
        $travelByDay = $this->index($travelOrders->map(fn ($order) => [$order, $order->employees->pluck('id')->all()]), $days);
        $meetingsByDay = $meetings->groupBy(fn ($meeting) => $meeting->employee_id.'|'.$meeting->date->toDateString());

        $employees = $roster->map(function (Employee $employee) use ($days, $undertimeByDay, $leaveByDay, $passSlipByDay, $travelByDay, $meetingsByDay) {
            $row = [];
            foreach ($days as $date) {
                $row[$date] = $this->status($employee->id, $date, $undertimeByDay, $leaveByDay, $passSlipByDay, $travelByDay, $meetingsByDay);
            }

            return [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'designation' => $employee->designation,
                'days' => $row,
            ];
        })->values()->all();

        return ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $days, 'employees' => $employees];
    }

    private function status(int $id, string $date, array $undertime, array $leave, array $passSlip, array $travel, Collection $meetings): array
    {
        $day = fn (string $status, ?string $label = null, ?string $text = null, array $list = []) => ['status' => $status, 'label' => $label, 'text' => $text, 'meetings' => $list];

        if (CarbonImmutable::parse($date)->isWeekend()) {
            return $day('Off Day');
        }
        if ($record = $undertime[$id][$date] ?? null) {
            return $day('Undertime', null, $this->time($record->requested_time).' - '.$this->time($record->return_time));
        }
        if ($leave[$id][$date] ?? null) {
            return $day('On Leave', null, 'Leave');
        }
        if ($record = $passSlip[$id][$date] ?? null) {
            return $day('On Official Business', 'Pass Slip', "{$record->purpose} at {$record->location}");
        }
        if ($record = $travel[$id][$date] ?? null) {
            $purpose = is_array($record->purpose) ? implode(', ', $record->purpose) : $record->purpose;

            return $day('On Official Business', 'Travel Order', "{$purpose} at {$record->going_to}");
        }

        $list = ($meetings["{$id}|{$date}"] ?? collect())->map(fn ($meeting) => ['time' => $this->time($meeting->time), 'particulars' => $meeting->particulars])->values()->all();

        return $list ? $day('Present with Meetings', null, null, $list) : $day('Present');
    }

    /**
     * PBO staff, the Governor, and anyone existing records still name, narrowed to those with a record to show.
     */
    private function roster(Collection $undertime, Collection $leaves, Collection $passSlips, Collection $travelOrders, Collection $meetings): Collection
    {
        $withRecords = collect()
            ->merge($undertime->pluck('employee_id'))
            ->merge($leaves->pluck('employee_id'))
            ->merge($meetings->pluck('employee_id'))
            ->merge($passSlips->flatMap(fn ($slip) => $slip->employees->pluck('id')))
            ->merge($travelOrders->flatMap(fn ($order) => $order->employees->pluck('id')))
            ->unique()
            ->values();

        return Employee::whereIn('id', $withRecords)
            ->where(fn ($q) => $q->where(fn ($pbo) => $pbo->pboScope()->active())->orWhereIn('id', EmployeeReferences::referencedIds()))
            ->get(['id', 'employee_id', 'name', 'designation'])
            ->sort(function (Employee $a, Employee $b) {
                $priority = (int) in_array($b->designation, self::PRIORITY_DESIGNATIONS, true) <=> (int) in_array($a->designation, self::PRIORITY_DESIGNATIONS, true);

                return $priority ?: (strcasecmp($this->lastName($a->name), $this->lastName($b->name)) ?: strcasecmp($a->name, $b->name));
            })
            ->values();
    }

    /** [employee id => [date => first record covering that day]], for the days asked about only */
    private function index(Collection $records, array $days): array
    {
        $wanted = array_flip($days);
        $index = [];
        foreach ($records as [$record, $employeeIds]) {
            foreach ($record->inclusive_dates ?? [] as $entry) {
                foreach ($this->expand((string) $entry, $days[0], end($days)) as $date) {
                    if (isset($wanted[$date])) {
                        foreach ($employeeIds as $employeeId) {
                            $index[$employeeId][$date] ??= $record;
                        }
                    }
                }
            }
        }

        return $index;
    }

    /** The dates of a "Y-m-d" or "Y-m-d - Y-m-d" entry that fall within the window */
    private function expand(string $entry, string $windowStart, string $windowEnd): array
    {
        $parts = array_map('trim', explode(' - ', $entry));
        try {
            $start = CarbonImmutable::parse($parts[0]);
            $end = CarbonImmutable::parse($parts[1] ?? $parts[0]);
        } catch (\Throwable) {
            return [];
        }

        $dates = [];
        $day = $start->toDateString() < $windowStart ? CarbonImmutable::parse($windowStart) : $start;
        $last = $end->toDateString() > $windowEnd ? CarbonImmutable::parse($windowEnd) : $end;
        for (; $day->lte($last); $day = $day->addDay()) {
            $dates[] = $day->toDateString();
        }

        return $dates;
    }

    private function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [$name];
        $last = end($parts);
        if (count($parts) > 1 && in_array(rtrim(strtolower($last), '.'), self::NAME_SUFFIXES, true)) {
            $last = $parts[count($parts) - 2];
        }

        return $last;
    }

    /** "14:30" or "14:30:00" -> "02:30 PM", as the page shows it */
    private function time(?string $value): string
    {
        if (! $value || ! preg_match('/^(\d{1,2}):(\d{2})/', $value, $m)) {
            return (string) $value;
        }
        $hours = (int) $m[1];

        return sprintf('%02d:%s %s', $hours % 12 ?: 12, $m[2], $hours >= 12 ? 'PM' : 'AM');
    }
}
