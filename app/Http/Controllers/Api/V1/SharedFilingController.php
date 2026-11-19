<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PassSlipController;
use App\Http\Controllers\Api\TardinessController;
use App\Http\Controllers\Api\TimeSlipController;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PassSlip;
use App\Models\Tardiness;
use App\Models\TimeSlip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Shared Filing API for other PBO systems, e.g. pbo-registryV2's profile: PBO staff file and edit their own leaves, pass
 * slips, time slips and tardiness/undertime. The employee is found by their HRIS employee ID; the client sends only the
 * signed-in user's own ID. Requests carry a Filing API key (api.key:filing), separate from the read-only keys.
 *
 * The save goes through the module's own controller, so its checks apply (overlapping leaves, yearly limits, times);
 * this API fixes what staff may not choose: the employee and the control number (next free one, as the forms make it).
 * Slips take a supervisor (PBO staff) and an approving PBO signatory, as on the forms; a leave's signatories are left to HR, and
 * its recommendation (7.B) is For approval unless the form says For disapproval with a reason. The employee's own records stay
 * editable here, except a pass slip naming others too. See docs/filing-api.md.
 */
class SharedFilingController extends Controller
{
    /** Leave types staff may file themselves (Others, i.e. monetization and terminal leave, goes through HR) */
    public const LEAVE_TYPES = [
        'Vacation Leave', 'Mandatory/Forced Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave', 'Special Privilege Leave',
        'Solo Parent Leave', 'Study Leave', '10-Day VAWC Leave', 'Rehabilitation Leave', 'Special Leave Benefits for Women',
        'Special Emergency (Calamity) Leave', 'Adoption Leave', 'Wellness Leave',
    ];

    /** The approving signatory of slips, as in useSignatory.ts */
    public const SIGNATORY_TITLES = ['Provincial Budget Officer', 'OIC, Provincial Budget Officer', 'Acting Provincial Budget Officer'];

    private const LEAVE_FIELDS = ['type_of_leave', 'number_of_working_days_applied_for', 'is_half_day', 'half_day_period', 'inclusive_dates', 'off_days',
        'within_philippines', 'purpose', 'in_hospital', 'illness', 'completion_type'];

    /** Each slip's own fields, and which of them names its supervisor (recommending approval / certified by) */
    private const SLIPS = [
        'pass-slips' => ['fields' => ['date', 'inclusive_dates', 'requested_time', 'purpose', 'location', 'expected_return_time', 'remarks', 'vehicle'], 'supervisor' => 'recommending_approval_employee_id'],
        'time-slips' => ['fields' => ['date', 'time_in_am', 'time_out_am', 'time_in_pm', 'time_out_pm', 'reason'], 'supervisor' => 'certified_by_employee_id'],
        'tardiness' => ['fields' => ['type', 'requested_date', 'requested_time', 'reason', 'return_time'], 'supervisor' => 'supervisor_employee_id'],
    ];

    private const SIGNATORY_FIELDS = ['approved_by_employee_id', 'approved_by_title'];

    /** GET /api/v1/filing/employees/{employeeId}?year=2026: the employee's own records of the year, each with whether it can still be edited */
    public function index(Request $request, string $employeeId): JsonResponse
    {
        $year = (int) ($request->validate(['year' => 'nullable|integer|min:2000|max:2100'])['year'] ?? now()->year);

        return response()->json(['data' => $this->records($this->employee($employeeId), $year)]);
    }

    /** The employee's records of the year with the form's lists, as the API returns them; also DocuTrack's own Profile (ProfileController) */
    public function records(Employee $employee, int $year): array
    {
        $slip = fn (Model $record, string $module) => ['id' => $record->id, 'control_no' => $record->control_no]
            + Arr::only($record->toArray(), [...self::SLIPS[$module]['fields'], self::SLIPS[$module]['supervisor'], ...self::SIGNATORY_FIELDS]);

        $leaves = Leave::where('employee_id', $employee->id)->whereYear('date_of_filing', $year)->orderByDesc('date_of_filing')->orderBy('id')->get()
            ->groupBy('control_no')->map(fn ($rows, $controlNo) => [
                'control_no' => $controlNo,
                'date_of_filing' => $rows->first()->date_of_filing?->format('Y-m-d'),
                'recommendation' => $rows->first()->recommendation,
                'disapproval_reason' => $rows->first()->disapproval_reason,
                'editable' => true,
                'entries' => $rows->map(fn (Leave $leave) => ['id' => $leave->id] + Arr::only($leave->toArray(), self::LEAVE_FIELDS))->values(),
            ])->values();

        $passSlips = PassSlip::with('employees:employees.id')->whereHas('employees', fn ($q) => $q->where('employees.id', $employee->id))
            ->whereYear('date', $year)->latest('date')->get()
            ->map(fn (PassSlip $record) => $slip($record, 'pass-slips') + ['editable' => $this->namesOnly($record, $employee)]);

        $timeSlips = TimeSlip::where('requesting_employee_id', $employee->id)->whereYear('date', $year)->latest('date')->get()
            ->map(fn (TimeSlip $record) => $slip($record, 'time-slips') + ['editable' => true]);

        $tardiness = Tardiness::where('employee_id', $employee->id)->whereYear('requested_date', $year)->latest('requested_date')->get()
            ->map(fn (Tardiness $record) => $slip($record, 'tardiness') + ['date_filed' => $record->date_filed?->format('Y-m-d'), 'editable' => true]);

        $people = fn ($query) => $query->active()->where('id', '!=', $employee->id)->orderBy('name')->get(['id', 'name', 'designation'])
            ->map(fn (Employee $e) => ['id' => $e->id, 'name' => $e->name, 'designation' => $e->designation])->values();

        return [
            'year' => $year,
            'leave_types' => self::LEAVE_TYPES,
            'signatories' => [
                'supervisors' => $people(Employee::pboScope()),
                'approvers' => Employee::pboStaff()->active()->orderBy('name')->get(['id', 'name'])->map->only(['id', 'name'])->values(),
                'titles' => self::SIGNATORY_TITLES,
                'budget_officer_id' => Employee::official(Employee::TITLE_PBO)?->id,
                // The Provincial Governor recommends / approves the Provincial Budget Officer's own slips, as on the forms
                'governor_approves' => $this->isBudgetOfficer($employee),
            ],
            // For the form to warn as the Leaves page does before saving (the save checks the same): days already on leave, and yearly-limit use
            'leave_check' => [
                'days' => (object) Leave::daysOnLeave($employee->id),
                'limits' => Leave::YEARLY_LIMITS,
                'limited' => Leave::where('employee_id', $employee->id)->whereIn('type_of_leave', array_keys(Leave::YEARLY_LIMITS))->get()
                    ->map(fn (Leave $leave) => ['control_no' => $leave->control_no, 'type' => $leave->type_of_leave, 'year' => $leave->date_of_filing?->year,
                        'days' => Leave::daysCounted((bool) $leave->is_half_day, $leave->number_of_working_days_applied_for)])->values(),
            ],
            'leaves' => $leaves,
            'pass_slips' => $passSlips,
            'time_slips' => $timeSlips,
            'tardiness' => $tardiness,
        ];
    }

    /** POST /api/v1/filing/employees/{employeeId}/{module}: a new record for the employee */
    public function store(Request $request, string $employeeId, string $module): JsonResponse
    {
        $employee = $this->employee($employeeId);

        return $this->file($employee, $module, null, $request->all(), $this->actAs($request, $employee));
    }

    /** PUT /api/v1/filing/employees/{employeeId}/{module}/{id}: the employee's own record (a leave by its control number) */
    public function update(Request $request, string $employeeId, string $module, string $id): JsonResponse
    {
        $employee = $this->employee($employeeId);

        return $this->file($employee, $module, $id, $request->all(), $this->actAs($request, $employee));
    }

    /**
     * Files a new record for the employee ($id null) or edits their own, saved by $actor: the Filing API's stand-in user, or
     * the signed-in user on DocuTrack's own Profile. Refusals come back as 422/404/409 JSON responses or exceptions.
     */
    public function file(Employee $employee, string $module, ?string $id, array $input, User $actor): JsonResponse
    {
        return $id === null ? $this->create($employee, $module, $input, $actor) : $this->change($employee, $module, $id, $input, $actor);
    }

    private function create(Employee $employee, string $module, array $input, User $actor): JsonResponse
    {
        // One at a time per module, so two filings can't take the same control number
        return Cache::lock("filing-api:{$module}", 15)->block(10, fn () => match ($module) {
            'leaves' => app(LeaveController::class)->store($this->request([
                'control_no' => $this->nextControlNo(Leave::class, 'L', now()->toDateString()),
                'employee_id' => $employee->id,
                'date_of_filing' => now()->toDateString(),
                'salary' => $employee->monthly_salary,
                'entries' => $this->leaveEntries($input),
            ] + $this->recommendation($input), $actor)),
            'pass-slips' => app(PassSlipController::class)->store($this->request([
                'control_no' => $this->nextControlNo(PassSlip::class, 'PS', (string) ($input['date'] ?? now()->toDateString())),
                'employee_ids' => [$employee->id],
            ] + $this->slipValues('pass-slips', $input, $employee), $actor)),
            'time-slips' => app(TimeSlipController::class)->store($this->request([
                'control_no' => $this->nextControlNo(TimeSlip::class, 'TS', (string) ($input['date'] ?? now()->toDateString())),
                'requesting_employee_id' => $employee->id,
            ] + $this->slipValues('time-slips', $input, $employee), $actor)),
            'tardiness' => app(TardinessController::class)->store($this->request([
                'control_no' => $this->nextControlNo(Tardiness::class, 'TU', now()->toDateString()),
                'date_filed' => now()->toDateString(),
                'employee_id' => $employee->id,
            ] + $this->slipValues('tardiness', $input, $employee), $actor)),
        });
    }

    private function change(Employee $employee, string $module, string $id, array $input, User $actor): JsonResponse
    {
        if ($module === 'leaves') {
            $rows = Leave::where('employee_id', $employee->id)->where('control_no', $id)->orderBy('id')->get();
            abort_if($rows->isEmpty(), 404, 'Leave not found.');
            $leave = $rows->first();
            // An entry keeps its row only when the row is this application's; the leave controller removes the rows left out
            $entries = collect($this->leaveEntries($input))->map(fn ($entry) => $rows->contains('id', (int) ($entry['id'] ?? 0)) ? $entry : Arr::except($entry, 'id'))->all();

            return app(LeaveController::class)->update($this->request([
                'control_no' => $leave->control_no,
                'employee_id' => $employee->id,
                'date_of_filing' => $leave->date_of_filing?->format('Y-m-d'),
                'salary' => $leave->salary,
                'entries' => $entries,
            ] + $this->recommendation($input) + Arr::only($leave->toArray(), array_merge(...array_map(fn ($s) => ["{$s}_employee_id", "{$s}_title"], array_keys(Leave::SIGNATORIES)))), $actor), $leave);
        }

        $record = match ($module) {
            'pass-slips' => PassSlip::with('employees:employees.id')->whereHas('employees', fn ($q) => $q->where('employees.id', $employee->id))->findOrFail($id),
            'time-slips' => TimeSlip::where('requesting_employee_id', $employee->id)->findOrFail($id),
            'tardiness' => Tardiness::where('employee_id', $employee->id)->findOrFail($id),
        };
        if ($record instanceof PassSlip && !$this->namesOnly($record, $employee)) {
            return $this->locked("Pass slip {$record->control_no} names other employees too");
        }
        $values = ['control_no' => $record->control_no] + $this->slipValues($module, $input, $employee);

        return match ($module) {
            'pass-slips' => app(PassSlipController::class)->update($this->request($values + ['employee_ids' => [$employee->id]], $actor), $record),
            'time-slips' => app(TimeSlipController::class)->update($this->request($values + ['requesting_employee_id' => $employee->id], $actor), $record),
            'tardiness' => app(TardinessController::class)->update($this->request($values + ['date_filed' => $record->date_filed?->format('Y-m-d'), 'employee_id' => $employee->id], $actor), $record->id),
        };
    }

    /** GET /api/v1/filing/employees/{employeeId}/{module}/{id}/print: a 30-minute signed link (path only) to the record's print preview, for the employee's own record */
    public function printUrl(string $employeeId, string $module, string $id): JsonResponse
    {
        return response()->json(['data' => ['path' => $this->printPath($this->employee($employeeId), $module, $id)]]);
    }

    /** The signed print-preview link (path only) of the employee's own record; a leave by its control number */
    public function printPath(Employee $employee, string $module, string $id): string
    {
        $record = match ($module) {
            'leaves' => Leave::where('employee_id', $employee->id)->where('control_no', $id)->orderBy('id')->firstOrFail(),
            'pass-slips' => PassSlip::whereHas('employees', fn ($q) => $q->where('employees.id', $employee->id))->findOrFail($id),
            'time-slips' => TimeSlip::where('requesting_employee_id', $employee->id)->findOrFail($id),
            'tardiness' => Tardiness::where('employee_id', $employee->id)->findOrFail($id),
        };

        return \Illuminate\Support\Facades\URL::temporarySignedRoute('filing.print', now()->addMinutes(30), ['module' => $module, 'id' => $record->id], absolute: false);
    }

    /** The employee who may file here: PBO staff (its own and those detailed to it), still with the Province; null otherwise */
    public static function filer(?int $employeeKey): ?Employee
    {
        return $employeeKey ? Employee::pboStaff()->active()->find($employeeKey) : null;
    }

    /** PBO staff only (its own and those detailed to it), still with the Province */
    private function employee(string $employeeId): Employee
    {
        $employee = Employee::pboStaff()->active()->where('employee_id', $employeeId)->first();
        abort_unless($employee, 404, 'No PBO employee with that employee ID.');

        return $employee;
    }

    /** The saves run as the employee without DocuTrack permissions, so the module controllers allow only their own records; logs name who filed and from where */
    private function actAs(Request $request, Employee $employee): User
    {
        $name = trim((string) $request->header('X-Acting-User')) ?: $employee->name;
        $user = (new User)->forceFill(['name' => "{$name} (PBO Registry)", 'employee_id' => $employee->id, 'usertype' => null]);
        Auth::setUser($user);

        return $user;
    }

    private function request(array $data, User $actor): Request
    {
        $request = Request::create('/', 'POST', $data);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $actor);

        return $request;
    }

    /** 7.B Recommendation: For approval unless For disapproval is chosen; the leave controller requires the reason then */
    private function recommendation(array $input): array
    {
        $disapproval = ($input['recommendation'] ?? null) === 'disapproval';

        return ['recommendation' => $disapproval ? 'disapproval' : 'approval', 'disapproval_reason' => $disapproval ? ($input['disapproval_reason'] ?? null) : null];
    }

    /** One entry per leave type, each with its own dates and off days (weekday names; none = Saturdays and Sundays) */
    private function leaveEntries(array $input): array
    {
        $entries = array_values(array_filter((array) ($input['entries'] ?? []), 'is_array'));
        foreach ($entries as $index => $entry) {
            if (!in_array($entry['type_of_leave'] ?? null, self::LEAVE_TYPES, true)) {
                $this->refuse("entries.{$index}.type_of_leave", 'Choose a leave type for each entry.');
            }
        }

        return array_map(fn ($entry) => Arr::only($entry, ['id', ...self::LEAVE_FIELDS]), $entries);
    }

    /**
     * A slip's own fields with its signatories: a supervisor from PBO staff (or none, to go straight to the approver) and an
     * approving Provincial Budget Officer (OIC / Acting). The Provincial Budget Officer's own pass slip and tardiness go to the
     * Provincial Governor, as on the forms.
     */
    private function slipValues(string $module, array $input, Employee $employee): array
    {
        $supervisor = self::SLIPS[$module]['supervisor'];
        $values = Arr::only($input, self::SLIPS[$module]['fields']);
        $chosen = Arr::only($input, [$supervisor, ...self::SIGNATORY_FIELDS]) + [$supervisor => null, 'approved_by_employee_id' => null, 'approved_by_title' => null];

        if ($chosen[$supervisor] !== null && !Employee::pboScope()->active()->where('id', '!=', $employee->id)->whereKey($chosen[$supervisor])->exists()) {
            $this->refuse($supervisor, 'Choose your supervisor from the Provincial Budget Office staff.');
        }
        if ($chosen['approved_by_employee_id'] !== null && !Employee::pboStaff()->active()->whereKey($chosen['approved_by_employee_id'])->exists()) {
            $this->refuse('approved_by_employee_id', 'Choose the approving officer from the Provincial Budget Office staff.');
        }
        if ($chosen['approved_by_title'] !== null && !in_array($chosen['approved_by_title'], self::SIGNATORY_TITLES, true)) {
            $this->refuse('approved_by_title', 'Choose the approving officer\'s title.');
        }
        if ($module !== 'time-slips' && $this->isBudgetOfficer($employee)) {
            $chosen = [$supervisor => Employee::official(Employee::TITLE_GOVERNOR)?->id, 'approved_by_employee_id' => null, 'approved_by_title' => null];
        }
        if ($module === 'pass-slips') {
            // The Locator Chart reads the days from inclusive dates: the slip's own date when none are given
            $values['vehicle'] ??= 'RP Vehicle';
            $values['inclusive_dates'] = array_values(array_filter((array) ($values['inclusive_dates'] ?? []))) ?: array_filter([$values['date'] ?? null]);
        }

        return $values + $chosen;
    }

    /** A pass slip naming the employee alone; a group slip is changed in DocuTrack */
    private function namesOnly(PassSlip $slip, Employee $employee): bool
    {
        return $slip->employees->pluck('id')->all() === [$employee->id];
    }

    private function isBudgetOfficer(Employee $employee): bool
    {
        return trim((string) $employee->parenthetical_title) === Employee::TITLE_PBO || trim((string) $employee->designation) === Employee::TITLE_PBO;
    }

    /** PREFIX-YYYY-MM-NNNN, the series continuing through the year; trashed records keep their numbers (as the forms do) */
    private function nextControlNo(string $model, string $prefix, string $date): string
    {
        $when = \Carbon\Carbon::parse($date);
        /** @var Model $model */
        $max = $model::withTrashed()->where('control_no', 'like', "{$prefix}-{$when->year}-%")->pluck('control_no')
            ->map(fn ($no) => (int) last(explode('-', (string) $no)))->max() ?? 0;

        return sprintf('%s-%d-%s-%04d', $prefix, $when->year, $when->format('m'), $max + 1);
    }

    private function refuse(string $field, string $message): never
    {
        abort(response()->json(['errors' => [$field => [$message]]], 422));
    }

    private function locked(string $why): JsonResponse
    {
        return response()->json(['error' => "{$why}, so it can no longer be edited here. Change it in PBO|DocuTrack."], 409);
    }
}
