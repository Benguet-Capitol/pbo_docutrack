<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HrisEmployeeSync;
use App\Support\EmployeeReferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Employees, structured like pbo-registryV2 (position title, parenthetical title, status), with "Sync from HRIS".
 */
class EmployeeController extends Controller
{
    /**
     * Employees with their office. ?scope=pbo returns the Provincial Budget Office staff plus the
     * Provincial Governor (module dropdowns), plus anyone existing records still name (e.g. staff since moved to
     * another office by the HRIS sync) so those records keep showing and printing them; the dropdowns offer only
     * PBO staff. Without it, everyone (Users, Travel Orders search).
     *
     * With ?page= (the Employees page): one page at a time, searched and sorted here (search, sort, order, per_page),
     * as { data, current_page, last_page, per_page, total, from, to }.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('page')) {
            return $this->page($request);
        }

        $query = Employee::with('office')->orderBy('name');
        $withSeparated = $request->boolean('with_separated');
        $referenced = $request->query('scope') === 'pbo' || !$withSeparated ? EmployeeReferences::referencedIds() : [];
        if ($request->query('scope') === 'pbo') {
            $query->where(fn ($q) => $q->where(fn ($pbo) => $pbo->pboScope()->active())->orWhereIn('id', $referenced));
        } elseif (!$withSeparated) {
            // Employees who left (separated in HRIS) only where existing records still name them
            $query->where(fn ($q) => $q->active()->orWhereIn('id', $referenced));
        }

        return response()->json($query->get()->map($this->presenter()));
    }

    /** Sortable columns of the Employees page */
    private const SORTS = ['id', 'employee_id', 'name', 'position_title', 'parenthetical_title', 'status', 'designation'];

    /** One page of the Employees page (separated employees included, as the page shows them) */
    private function page(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
        $search = trim((string) ($validated['search'] ?? ''));

        $page = Employee::with('office')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(fn ($q) => $q->where('employee_id', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('position_title', 'like', $like)
                    ->orWhere('parenthetical_title', 'like', $like)
                    ->orWhere('designation', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('office', fn ($office) => $office->where('office_name', 'like', $like)->orWhere('office_abbreviation', 'like', $like)));
            })
            ->orderBy($validated['sort'] ?? 'id', $validated['order'] ?? 'asc')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json($page->through($this->presenter()));
    }

    /** An employee as the pages use it, with only the office fields they read */
    private function presenter(): \Closure
    {
        // Only the office fields the pages use (the full office twice per employee made the list ~2 MB)
        $office = fn ($office) => $office ? $office->only(['id', 'office_abbreviation', 'office_name']) : null;
        // Employees detailed to the PBO work there: forms print that office (others print their own, work_office null)
        $pboOffice = $office(\App\Models\Office::withTrashed()->find(Employee::PBO_OFFICE_ID));

        return fn (Employee $employee) => [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'name' => $employee->name,
            'position_title' => $employee->position_title,
            'parenthetical_title' => $employee->parenthetical_title,
            'status' => $employee->status,
            'designation' => $employee->designation,
            'full_position' => $employee->full_position,
            'office_id' => $employee->office_id,
            'detailed_to_pbo' => $employee->detailed_to_pbo,
            /** No longer in HRIS: kept for old records, never offered in pickers */
            'separated' => $employee->separated_at !== null,
            'separated_at' => $employee->separated_at?->format('Y-m-d'),
            'office' => $office($employee->office),
            'work_office' => $employee->detailed_to_pbo && (int) $employee->office_id !== Employee::PBO_OFFICE_ID ? $pboOffice : null,
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $employee = Employee::create($this->validated($request));

        return response()->json($employee->load('office'), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        $employee->update($this->validated($request, $employee));

        return response()->json($employee->load('office'));
    }

    /**
     * Blocked while records (including ones in the Trash) still use the employee.
     */
    public function destroy($id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        if ($error = EmployeeReferences::deleteBlocker($employee)) {
            return response()->json(['error' => $error], 422);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }

    /**
     * HRIS employee lookup for the Create/Edit Employee forms (server-side, so the HRIS key stays in .env).
     */
    public function hrisEmployees(HrisEmployeeSync $sync): JsonResponse
    {
        try {
            return response()->json(['data' => $sync->lookup()]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    /**
     * Sync from HRIS button (the same sync also runs monthly: employees:sync-hris)
     */
    public function syncHris(HrisEmployeeSync $sync): JsonResponse
    {
        try {
            $result = $sync->sync();
            \App\Support\JobRuns::record('hris_sync', true, $result['message'] ?? 'Synced from HRIS');

            return response()->json($result);
        } catch (RuntimeException $e) {
            \App\Support\JobRuns::record('hris_sync', false, 'Sync from HRIS failed: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_id')->whereNull('deleted_at')->ignore($employee?->id)],
            'name' => 'required|string|max:255',
            'position_title' => 'required|string|max:255',
            'parenthetical_title' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(Employee::STATUSES)],
            'office' => 'required|integer|exists:offices,id',
            'detailed_to_pbo' => 'sometimes|boolean',
        ]);
        $validated['office_id'] = $validated['office'];
        unset($validated['office']);

        return $validated;
    }
}
