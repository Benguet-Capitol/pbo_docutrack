<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Leave applications (CS Form No. 6). An application has one control number and can cover several leave types,
 * each with its own dates: every type is its own row (so yearly limits, the Dashboard and reports count per type),
 * and the rows share the control number, employee, filing date and signatories.
 */
class LeaveController extends Controller
{
    /** Fields of one leave type in an application */
    private const ENTRY_FIELDS = [
        'type_of_leave', 'number_of_working_days_applied_for', 'is_half_day', 'half_day_period', 'inclusive_dates',
        'off_days', 'within_philippines', 'purpose', 'in_hospital', 'illness', 'completion_type', 'other_type',
    ];

    /**
     * Download the application as a filled CS Form No. 6 (Application for Leave) Excel file.
     */
    public function exportForm(Leave $leave, \App\Services\LeaveFormExporter $exporter)
    {
        $path = $exporter->export($leave);

        return response()->download($path, $exporter->filename($leave), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Every leave row; rows with the same control number are one application.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // The Dashboard and Locator Chart read this list too: salaries only go to those who can open Leaves
            $withSalary = $request->user() && \App\Services\RoleService::hasPermission($request->user(), 'leaves.view');

            $leaves = Leave::with('employee')
                ->latest('id')
                ->get()
                ->map(fn (Leave $leave) => [
                    'id' => $leave->id,
                    'control_no' => $leave->control_no,
                    'employee_id' => $leave->employee_id,
                    'employee' => $leave->employee,
                    'date_of_filing' => $leave->date_of_filing,
                    'salary' => $withSalary ? $leave->salary : null,
                ] + $leave->only(self::ENTRY_FIELDS) + $leave->only($this->signatoryFields()) + $leave->only(['recommendation', 'disapproval_reason']) + [
                    'created_at' => $leave->created_at,
                    'updated_at' => $leave->updated_at,
                ]);

            return response()->json($leaves);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create an application: one row per leave type.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validated($request);
            $this->ensureControlNoFree($validated['control_no']);
            $this->ensureCompleteEntries($validated);
            $this->ensureNoOverlaps($validated);
            $this->ensureWithinYearlyLimits($validated);

            $rows = DB::transaction(fn () => collect($validated['entries'])
                ->map(fn (array $entry) => Leave::create($this->rowValues($validated, $entry))));

            return response()->json([
                'message' => 'Leave created successfully',
                'data' => $rows->first()->load('employee'),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the application the leave belongs to: entries with an id are updated, new ones added, and the
     * application's other rows removed.
     */
    public function update(Request $request, Leave $leave): JsonResponse
    {
        try {
            $current = $this->application($leave);
            $validated = $this->validated($request);
            if ($validated['control_no'] !== $leave->control_no) {
                $this->ensureControlNoFree($validated['control_no']);
            }
            $this->ensureCompleteEntries($validated);
            $this->ensureNoOverlaps($validated, $current->pluck('id')->all());
            $this->ensureWithinYearlyLimits($validated, $current->pluck('id')->all());

            DB::transaction(function () use ($validated, $current) {
                $kept = [];
                foreach ($validated['entries'] as $entry) {
                    $row = isset($entry['id']) ? $current->firstWhere('id', (int) $entry['id']) : null;
                    if ($row) {
                        $row->update($this->rowValues($validated, $entry));
                        $kept[] = $row->id;
                    } else {
                        $kept[] = Leave::create($this->rowValues($validated, $entry))->id;
                    }
                }
                // A leave type taken out of the application is removed outright (not sent to the Trash)
                $current->whereNotIn('id', $kept)->each->forceDelete();
            });

            return response()->json([
                'message' => 'Leave updated successfully',
                'data' => Leave::with('employee')->where('control_no', $validated['control_no'])->orderBy('id')->first(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Move the whole application (every leave type in it) to the Trash.
     */
    public function destroy(Leave $leave): JsonResponse
    {
        try {
            $controlNo = $leave->control_no;
            DB::transaction(fn () => $this->application($leave)->each->delete());

            return response()->json([
                'message' => "Leave {$controlNo} deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * The employee's current monthly salary (from HRIS), to fill "5. SALARY" on a new leave.
     */
    public function employeeSalary(\App\Models\Employee $employee): JsonResponse
    {
        return response()->json(['salary' => $employee->monthly_salary]);
    }

    /** The leave's application: every live row with its control number */
    private function application(Leave $leave): Collection
    {
        return Leave::where('control_no', $leave->control_no)->orderBy('id')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'control_no' => 'required|string|max:255',
            'employee_id' => 'required|integer|exists:employees,id',
            'date_of_filing' => 'required|date',
            'salary' => 'nullable|numeric|min:0|max:9999999',
            'entries' => 'required|array|min:1',
            'entries.*.id' => 'nullable|integer',
            'entries.*.type_of_leave' => 'required|string|distinct',
            'entries.*.number_of_working_days_applied_for' => 'required|numeric|min:0.5',
            'entries.*.is_half_day' => 'nullable|boolean',
            'entries.*.half_day_period' => 'nullable|string|in:AM,PM',
            'entries.*.inclusive_dates' => 'required|array|min:1',
            'entries.*.off_days' => 'nullable|array',
            'entries.*.within_philippines' => 'nullable|boolean',
            'entries.*.purpose' => 'nullable|string|max:500',
            'entries.*.in_hospital' => 'nullable|boolean',
            'entries.*.illness' => 'nullable|string|max:500',
            'entries.*.completion_type' => 'nullable|string',
            'entries.*.other_type' => 'nullable|string',
            'recommendation' => 'nullable|in:approval,disapproval',
            'disapproval_reason' => 'nullable|required_if:recommendation,disapproval|string|max:500',
        ] + $this->signatoryRules(), [
            'disapproval_reason.required_if' => 'Enter the reason for disapproval.',
            'entries.required' => 'Add at least one leave type.',
            'entries.*.type_of_leave.distinct' => 'Each leave type can only be added once per application.',
            'entries.*.inclusive_dates.required' => 'Each leave type needs its inclusive dates.',
        ]);
    }

    /**
     * Each leave type is complete: a half day says AM or PM, "Others" says which (monetization / terminal leave),
     * and a date range doesn't end before it starts.
     */
    private function ensureCompleteEntries(array $validated): void
    {
        foreach ($validated['entries'] as $index => $entry) {
            $type = $entry['type_of_leave'];
            if (!empty($entry['is_half_day']) && empty($entry['half_day_period'])) {
                throw ValidationException::withMessages(["entries.{$index}.half_day_period" => "{$type}: choose AM or PM for the half day."]);
            }
            if ($type === 'Others' && empty($entry['other_type'])) {
                throw ValidationException::withMessages(["entries.{$index}.other_type" => 'Others: choose Monetization of Leave Credits or Terminal Leave.']);
            }
            foreach ($entry['inclusive_dates'] as $dates) {
                $parts = array_map('trim', explode(' - ', (string) $dates));
                if (count($parts) === 2 && strcmp($parts[1], $parts[0]) < 0) {
                    throw ValidationException::withMessages(["entries.{$index}.inclusive_dates" => "{$type}: the date range {$dates} ends before it starts."]);
                }
            }
        }
    }

    /**
     * No day filed twice: not by two leave types of this application, nor by another leave of the employee
     * (disapproved ones aside). Half days on different halves (AM and PM) of the same day are fine.
     */
    private function ensureNoOverlaps(array $validated, array $excludeIds = []): void
    {
        $taken = Leave::daysOnLeave((int) $validated['employee_id'], $excludeIds);
        $label = fn (string $date) => Carbon::parse($date)->format('F j, Y');
        foreach ($validated['entries'] as $index => $entry) {
            $type = $entry['type_of_leave'];
            $half = !empty($entry['is_half_day']) ? ($entry['half_day_period'] ?? null) : null;
            foreach (Leave::workingDates($entry['inclusive_dates'], $entry['off_days'] ?? null) as $date) {
                foreach ($taken[$date] ?? [] as [$controlNo, $otherType, $otherHalf]) {
                    if (Leave::halvesClash($half, $otherHalf)) {
                        $where = $controlNo === $validated['control_no'] ? "{$otherType} in this application" : "{$otherType} ({$controlNo})";
                        throw ValidationException::withMessages(["entries.{$index}.inclusive_dates" => "{$type}: already on leave on {$label($date)}, filed as {$where}."]);
                    }
                }
                $taken[$date][] = [$validated['control_no'], $type, $half];
            }
        }
    }

    /** A control number belongs to one application; trashed applications keep theirs reserved */
    private function ensureControlNoFree(string $controlNo): void
    {
        if (Leave::withTrashed()->where('control_no', $controlNo)->exists()) {
            throw ValidationException::withMessages(['control_no' => "Control number {$controlNo} is already used."]);
        }
    }

    /**
     * Yearly limits (Mandatory/Forced, Special Privilege, Wellness): what the employee already used that year
     * (minus this application's own rows on update) plus what this application asks for.
     */
    private function ensureWithinYearlyLimits(array $validated, array $excludeIds = []): void
    {
        $year = Carbon::parse($validated['date_of_filing'])->year;
        $format = fn (float $days) => rtrim(rtrim(number_format($days, 1), '0'), '.');

        foreach ($validated['entries'] as $entry) {
            $type = $entry['type_of_leave'];
            $limit = Leave::YEARLY_LIMITS[$type] ?? null;
            if ($limit === null) continue;

            $used = Leave::usedDays((int) $validated['employee_id'], $type, $year, $excludeIds);
            $requested = Leave::daysCounted((bool) ($entry['is_half_day'] ?? false), $entry['number_of_working_days_applied_for']);
            if ($used + $requested <= $limit) continue;

            $remaining = max(0, $limit - $used);
            throw ValidationException::withMessages(['type_of_leave' => $remaining <= 0
                ? "{$type} is already exhausted for {$year}: all {$limit} days have been used."
                : "{$type} exceeds the yearly limit for {$year}: only {$format($remaining)} of {$limit} days remaining, but {$format($requested)} requested."]);
        }
    }

    /** Row values: the application's shared fields plus one leave type's details (only those that apply to it) */
    private function rowValues(array $validated, array $entry): array
    {
        $type = $entry['type_of_leave'];
        $location = in_array($type, ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'], true);
        $halfDay = (bool) ($entry['is_half_day'] ?? false);

        return [
            'control_no' => $validated['control_no'],
            'employee_id' => $validated['employee_id'],
            'date_of_filing' => $validated['date_of_filing'],
            'salary' => $validated['salary'] ?? null,
            'type_of_leave' => $type,
            'number_of_working_days_applied_for' => $entry['number_of_working_days_applied_for'],
            'is_half_day' => $halfDay,
            'half_day_period' => $halfDay ? ($entry['half_day_period'] ?? null) : null,
            'inclusive_dates' => $entry['inclusive_dates'],
            'off_days' => ($entry['off_days'] ?? null) ?: null,
            'within_philippines' => $location ? ($entry['within_philippines'] ?? null) : null,
            'purpose' => $location ? ($entry['purpose'] ?? null) : null,
            'in_hospital' => $type === 'Sick Leave' ? ($entry['in_hospital'] ?? null) : null,
            'illness' => in_array($type, ['Sick Leave', 'Special Leave Benefits for Women'], true) ? ($entry['illness'] ?? null) : null,
            'completion_type' => $type === 'Study Leave' ? ($entry['completion_type'] ?? null) : null,
            'other_type' => $type === 'Others' ? ($entry['other_type'] ?? null) : null,
            'recommendation' => $validated['recommendation'] ?? null,
            'disapproval_reason' => ($validated['recommendation'] ?? null) === 'disapproval' ? ($validated['disapproval_reason'] ?? null) : null,
        ] + $this->signatoryValues($validated);
    }

    private function signatoryFields(): array
    {
        return collect(array_keys(Leave::SIGNATORIES))->flatMap(fn ($s) => ["{$s}_employee_id", "{$s}_title"])->all();
    }

    /** 7.A / 7.B / authorized official: an employee and one of the officer's titles (officer, OIC or Acting) */
    private function signatoryRules(): array
    {
        $rules = [];
        foreach (array_keys(Leave::SIGNATORIES) as $signatory) {
            $rules["{$signatory}_employee_id"] = 'nullable|integer|exists:employees,id';
            $rules["{$signatory}_title"] = ['nullable', 'string', Rule::in(Leave::signatoryTitles($signatory))];
        }

        return $rules;
    }

    private function signatoryValues(array $validated): array
    {
        $values = [];
        foreach (array_keys(Leave::SIGNATORIES) as $signatory) {
            $values["{$signatory}_employee_id"] = $validated["{$signatory}_employee_id"] ?? null;
            $values["{$signatory}_title"] = $validated["{$signatory}_title"] ?? null;
        }

        return $values;
    }
}
