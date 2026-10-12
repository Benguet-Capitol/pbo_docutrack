<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminOrDeveloper;
use App\Models\ActivityLog;
use App\Models\CertificateOfAppearance;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Municipality;
use App\Models\NoticeOfMeeting;
use App\Models\Office;
use App\Models\PassSlip;
use App\Models\Record;
use App\Models\Tardiness;
use App\Models\TimeSlip;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Trash: lists soft-deleted records of every module, restores them,
 * and permanently deletes them after re-confirming an Administrator/Developer password.
 */
class TrashController extends Controller
{
    /**
     * Soft-deletable modules: key => [label, model class, relations to eager load].
     */
    private const MODULES = [
        'documents' => ['Documents', Document::class, []],
        'records' => ['Records', Record::class, []],
        'leaves' => ['Leaves', Leave::class, ['employee']],
        'travel_orders' => ['Travel Orders', TravelOrder::class, ['employees']],
        'pass_slips' => ['Pass Slips', PassSlip::class, ['employees']],
        'time_slips' => ['Time Slips', TimeSlip::class, ['requestingEmployee']],
        'tardiness' => ['Tardiness/Undertime', Tardiness::class, ['employee']],
        'certificate_of_appearances' => ['Certificates of Appearance', CertificateOfAppearance::class, []],
        'notice_of_meetings' => ['Notices of Meeting', NoticeOfMeeting::class, ['employee']],
        'users' => ['Users', User::class, []],
        'employees' => ['Employees', Employee::class, ['office']],
        'offices' => ['Offices', Office::class, []],
        'municipalities' => ['Municipalities', Municipality::class, []],
    ];

    /**
     * List available modules with the number of trashed items in each.
     */
    public function modules(): JsonResponse
    {
        $modules = [];
        foreach (self::MODULES as $key => [$label, $class]) {
            $modules[] = [
                'key' => $key,
                'label' => $label,
                'count' => $class::onlyTrashed()->count(),
            ];
        }

        return response()->json($modules);
    }

    /**
     * List trashed items, optionally filtered by module.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $moduleFilter = $request->query('module');
            $items = collect();

            foreach (self::MODULES as $key => [$label, $class, $with]) {
                if ($moduleFilter && $moduleFilter !== $key) {
                    continue;
                }

                $trashed = $class::onlyTrashed()->with($with)->get();
                if ($trashed->isEmpty()) {
                    continue;
                }

                $deletedBy = $this->deletedByLookup($class, $trashed->pluck('id')->all());

                foreach ($trashed as $model) {
                    $log = $deletedBy[$model->id] ?? null;
                    $items->push([
                        'id' => $model->id,
                        'module' => $key,
                        'module_label' => $label,
                        'label' => $model->getActivityLabel(),
                        'details' => $this->details($key, $model),
                        'warning' => $this->cascadeWarning($key, $model),
                        'deleted_at' => $log?->created_at?->format('Y-m-d H:i:s') ?? $model->deleted_at?->format('Y-m-d H:i:s'),
                        'deleted_by' => $log?->user_name,
                    ]);
                }
            }

            return response()->json($items->sortByDesc('deleted_at')->values());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Control numbers held by trashed records of a module, so the create forms
     * do not propose a number that is still reserved by an item in the Trash.
     */
    public function controlNumbers(string $module): JsonResponse
    {
        $columns = [
            'leaves' => [Leave::class, 'control_no'],
            'travel_orders' => [TravelOrder::class, 'control_no'],
            'pass_slips' => [PassSlip::class, 'control_no'],
            'time_slips' => [TimeSlip::class, 'control_no'],
            'tardiness' => [Tardiness::class, 'control_no'],
            'certificate_of_appearances' => [CertificateOfAppearance::class, 'control_no'],
        ];

        if (!isset($columns[$module])) {
            return response()->json(['error' => 'Unknown module'], 404);
        }

        [$class, $column] = $columns[$module];

        return response()->json($class::onlyTrashed()->pluck($column)->filter()->values());
    }

    /**
     * Restore one or more trashed items.
     */
    public function restore(Request $request): JsonResponse
    {
        $validated = $this->validateItems($request);

        try {
            $restored = DB::transaction(function () use ($validated) {
                $count = 0;
                foreach ($this->resolveItems($validated['items']) as $model) {
                    $model->restore();
                    $count++;
                }
                return $count;
            });

            return response()->json([
                'message' => $restored === 1 ? '1 item restored successfully' : "{$restored} items restored successfully",
                'count' => $restored,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Permanently delete one or more trashed items. Requires the current
     * Administrator/Developer user's password.
     */
    public function forceDelete(Request $request): JsonResponse
    {
        $validated = $this->validateItems($request, [
            'password' => 'required|string',
        ]);

        if (!$this->passwordMatches($request->user(), $validated['password'])) {
            return response()->json([
                'message' => 'Incorrect password.',
                'errors' => ['password' => ['The password is incorrect.']],
            ], 422);
        }

        try {
            $deleted = DB::transaction(function () use ($validated) {
                $count = 0;
                foreach ($this->resolveItems($validated['items']) as $model) {
                    $model->forceDelete();
                    $count++;
                }
                return $count;
            });

            return response()->json([
                'message' => $deleted === 1 ? '1 item permanently deleted' : "{$deleted} items permanently deleted",
                'count' => $deleted,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Unable to permanently delete: the item is still referenced by other records.',
            ], 409);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * The password must belong to the signed-in Administrator/Developer.
     */
    private function passwordMatches(?User $user, string $password): bool
    {
        if (!$user || !in_array($user->usertype, EnsureAdminOrDeveloper::ROLES, true)) {
            return false;
        }

        try {
            return Hash::check($password, $user->password);
        } catch (\RuntimeException $e) {
            // Stored hash uses an unsupported algorithm (e.g. synced from the registry)
            return false;
        }
    }

    private function validateItems(Request $request, array $extraRules = []): array
    {
        return $request->validate(array_merge([
            'items' => 'required|array|min:1',
            'items.*.module' => 'required|string|in:' . implode(',', array_keys(self::MODULES)),
            'items.*.id' => 'required|integer',
        ], $extraRules));
    }

    /**
     * Load the trashed models for the requested items (fails if any is not in the Trash).
     *
     * @return Model[]
     */
    private function resolveItems(array $items): array
    {
        $models = [];
        foreach ($items as $item) {
            $class = self::MODULES[$item['module']][1];
            $models[] = $class::onlyTrashed()->findOrFail($item['id']);
        }
        return $models;
    }

    /**
     * Map of subject id => latest "deleted" activity log entry.
     */
    private function deletedByLookup(string $class, array $ids): array
    {
        return ActivityLog::where('subject_type', (new $class)->getMorphClass())
            ->where('action', 'deleted')
            ->whereIn('subject_id', $ids)
            ->orderByDesc('id')
            ->get()
            ->unique('subject_id')
            ->keyBy('subject_id')
            ->all();
    }

    /**
     * Short description of the trashed item for the Trash table.
     */
    private function details(string $module, Model $model): string
    {
        $employeeNames = fn ($employees) => $employees?->pluck('name')->filter()->implode(', ');

        $parts = match ($module) {
            'documents' => [$model->document_type, $model->particulars, $model->source ? "from {$model->source}" : null],
            'records' => [$model->record_type, $model->title],
            'leaves' => [$model->employee?->name, $model->type_of_leave, $model->date_of_filing?->format('Y-m-d')],
            'travel_orders' => [$employeeNames($model->employees), $model->going_to],
            'pass_slips' => [$employeeNames($model->employees), $model->location, $model->date?->format('Y-m-d')],
            'time_slips' => [$model->requestingEmployee?->name, $model->date?->format('Y-m-d')],
            'tardiness' => [$model->employee?->name, $model->type, $model->requested_date?->format('Y-m-d')],
            'certificate_of_appearances' => [$model->name, $model->office, $model->purpose],
            'notice_of_meetings' => [$model->employee?->name, $model->time],
            'users' => [$model->usertype],
            'employees' => [$model->employee_id, $model->designation, $model->office?->office_abbreviation],
            'offices' => [$model->office_abbreviation],
            'municipalities' => [$model->code, $model->city_class],
            default => [],
        };

        return collect($parts)->filter(fn ($part) => filled($part))->map(fn ($part) => is_array($part) ? implode(', ', $part) : (string) $part)->implode(' • ');
    }

    /**
     * Warn about records the database will cascade-delete along with this item.
     */
    private function cascadeWarning(string $module, Model $model): ?string
    {
        $dependents = match ($module) {
            'employees' => [
                'leave(s)' => Leave::withTrashed()->where('employee_id', $model->id)->count(),
                'time slip(s)' => TimeSlip::withTrashed()->where('requesting_employee_id', $model->id)->count(),
                'tardiness/undertime record(s)' => Tardiness::withTrashed()->where('employee_id', $model->id)->count(),
                'notice(s) of meeting' => NoticeOfMeeting::withTrashed()->where('employee_id', $model->id)->count(),
                'pass slip assignment(s)' => DB::table('pass_slip_employee')->where('employee_id', $model->id)->count(),
                'travel order assignment(s)' => DB::table('travel_order_employee')->where('employee_id', $model->id)->count(),
            ],
            'users' => [
                'document transaction(s)' => DB::table('document_transactions')->where('user_id', $model->id)->count(),
            ],
            'documents' => [
                'routing transaction(s)' => DB::table('document_transactions')->where('document_id', $model->id)->count(),
                'checklist record(s)' => DB::table('document_checklist_records')->where('document_id', $model->id)->count(),
            ],
            default => [],
        };

        $parts = collect($dependents)->filter()->map(fn ($count, $name) => "{$count} {$name}");

        return $parts->isEmpty() ? null : 'Permanently deleting this will also delete ' . $parts->implode(', ') . '.';
    }
}
