<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\EmployeeReferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Employees from HRIS: the Sync from HRIS button and the monthly scheduled sync (employees:sync-hris).
 */
class HrisEmployeeSync
{
    public function __construct(private HrisClient $hris)
    {
    }

    /**
     * Latest HRIS record of each person, shaped for the Create/Edit Employee lookup.
     *
     * @throws RuntimeException when HRIS can't be reached
     */
    public function lookup(): Collection
    {
        $offices = $this->hrisOfficeMap();

        return $this->latestRecords($this->hris->employees())->map(fn ($e) => [
            'employee_id_number' => trim((string) ($e['employee_id_number'] ?? '')),
            'name' => HrisClient::displayName($e),
            'position_title' => $e['position_title'] ?? null,
            'type' => $e['type'] ?? null,
            'status' => Employee::statusFromHris($e['type'] ?? null),
            'office_desc' => $e['office_desc'] ?? null,
            'office_id' => $offices[mb_strtolower(trim((string) ($e['office_desc'] ?? '')))] ?? null,
        ])->values();
    }

    /**
     * Adds HRIS employees (permanent, casual, COS) not yet here and updates the ones already here; deleted ones are
     * left alone. HRIS gives a person a new employee ID when they move (casual 713… → permanent 013…), so an ID not
     * found here goes to that person's earlier record (same name, an old ID HRIS no longer lists) instead of a new one.
     * A duplicate made before (a record under the new ID beside the old one) is merged into the old record, with
     * every leave, slip and signature re-pointed to it.
     *
     * @throws RuntimeException when HRIS can't be reached
     */
    public function sync(bool $scheduled = false): array
    {
        $records = $this->latestRecords($this->hris->employees());

        $offices = $this->hrisOfficeMap();
        // A live record wins over a deleted one with the same ID (keyBy keeps the last)
        $existing = Employee::withTrashed()->orderByRaw('deleted_at IS NULL')->get()->keyBy(fn ($e) => trim((string) $e->employee_id));
        $norm = fn ($name) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));
        $feedIds = $records->map(fn ($e) => trim((string) ($e['employee_id_number'] ?? '')))->filter()->flip();
        $feedNames = $records->countBy(fn ($e) => $norm(HrisClient::displayName($e)));
        // Earlier records of a person: live, same name, an ID HRIS no longer lists
        $gone = Employee::get()->filter(fn ($e) => !isset($feedIds[trim((string) $e->employee_id)]))->groupBy(fn ($e) => $norm($e->name));
        $earlier = fn ($name, ?int $except = null) => ($feedNames[$norm($name)] ?? 0) === 1
            ? ($gone[$norm($name)] ?? collect())->reject(fn ($e) => $e->id === $except)->values()
            : collect();

        $now = now();
        $rows = [];
        $changes = [];
        $merges = [];
        $skipped = ['type' => 0, 'office' => 0, 'incomplete' => 0, 'deleted' => 0];
        $added = array_fill_keys(Employee::STATUSES, 0);
        $unchanged = 0;
        $reassigned = 0;

        foreach ($records as $e) {
            $status = Employee::statusFromHris($e['type'] ?? null);
            $id = trim((string) ($e['employee_id_number'] ?? ''));
            [$position, $parenthetical] = Employee::splitPosition($e['position_title'] ?? null);
            $name = HrisClient::displayName($e);
            $office = $offices[mb_strtolower(trim((string) ($e['office_desc'] ?? '')))] ?? null;
            $values = [
                'name' => $name,
                'position_title' => $position,
                'parenthetical_title' => $parenthetical,
                'status' => $status,
                'designation' => Employee::titleOf($position, $parenthetical),
            ];
            // Monthly salary (HRIS sends e.g. "₱17,506.00"); a record without one keeps the employee's
            if (($salary = Employee::salaryFromHris($e['salary'] ?? null)) !== null) {
                $values['monthly_salary'] = $salary;
            }

            if (!$status) {
                $skipped['type']++;
            } elseif ($id === '' || $position === '' || !$name) {
                $skipped['incomplete']++;
            } elseif ($current = $existing->get($id)) {
                if (!$current->exists) continue;
                if ($current->trashed()) {
                    $skipped['deleted']++;
                    continue;
                }
                // An HRIS office with no mapping keeps the employee's office
                $values['office_id'] = $office ?? $current->office_id;
                if (($old = $earlier($name, $current->id))->count() === 1) {
                    $merges[] = [$old->first(), $current, $values + ['employee_id' => $id]];
                    continue;
                }
                $diff = array_filter($values, fn ($v, $k) => (string) $v !== (string) $current->{$k}, ARRAY_FILTER_USE_BOTH);
                $diff ? $changes[$current->id] = [$current, $diff] : $unchanged++;
            } elseif (($old = $earlier($name))->count() === 1) {
                // Same person under a new HRIS ID (e.g. casual → permanent): their earlier record is updated, ID included
                $old = $old->first();
                $values['office_id'] = $office ?? $old->office_id;
                $diff = array_filter($values + ['employee_id' => $id], fn ($v, $k) => (string) $v !== (string) $old->{$k}, ARRAY_FILTER_USE_BOTH);
                $changes[$old->id] = [$old, $diff];
                $existing[$id] = $old;
                $reassigned++;
            } elseif (!$office) {
                $skipped['office']++;
            } else {
                // Every inserted row needs the same columns (a bulk insert), so a missing salary is an explicit null
                $rows[] = ['employee_id' => $id] + $values + ['monthly_salary' => null, 'office_id' => $office, 'created_at' => $now, 'updated_at' => $now];
                $existing[$id] = new Employee();
                $added[$status]++;
            }
        }

        DB::transaction(function () use ($rows, $changes, $merges, $now) {
            collect($rows)->chunk(500)->each(fn ($chunk) => Employee::insert($chunk->all()));
            foreach ($changes as $employeeId => [, $diff]) {
                Employee::whereKey($employeeId)->update($diff + ['updated_at' => $now]);
            }
            foreach ($merges as [$keep, $duplicate, $values]) {
                EmployeeReferences::repoint($duplicate->id, $keep->id);
                $duplicate->forceDelete();
                Employee::whereKey($keep->id)->update($values + ['updated_at' => $now]);
            }
        });

        // Employees no longer in HRIS (resigned, retired...) are marked separated; those back in HRIS are restored.
        // Only HRIS-numbered employees (all digits) are considered, so records added by hand are left alone, and a
        // feed that came back nearly empty marks no one.
        $ids = $feedIds->keys()->map(fn ($id) => (string) $id)->all();
        $returned = Employee::whereNotNull('separated_at')->whereIn('employee_id', $ids)->update(['separated_at' => null]);
        $separated = 0;
        if (count($ids) >= 100) {
            $separated = Employee::active()
                ->whereNotIn('employee_id', $ids)
                ->get(['id', 'employee_id'])
                ->filter(fn ($e) => preg_match('/^\d{6,}$/', trim((string) $e->employee_id)))
                ->pipe(fn ($gone) => $gone->isEmpty() ? 0 : Employee::whereIn('id', $gone->pluck('id'))->update(['separated_at' => $now]));
        }

        $summary = count($rows) . ' added (' . collect($added)->map(fn ($n, $s) => "{$n} {$s}")->implode(', ') . '), '
            . count($changes) . ' updated' . ($reassigned ? " ({$reassigned} under a new HRIS ID)" : '') . ", {$unchanged} already up to date"
            . ($merges ? ', ' . count($merges) . ' duplicate(s) merged' : '')
            . ($skipped['deleted'] ? ", {$skipped['deleted']} deleted employee(s) left as they are" : '')
            . ($skipped['type'] ? ", {$skipped['type']} of another employment type left out" : '')
            . ($skipped['incomplete'] ? ", {$skipped['incomplete']} with no position title or name in HRIS left out" : '')
            . ($skipped['office'] ? ", {$skipped['office']} in HRIS offices with no office mapping left out" : '')
            . ($separated ? ", {$separated} no longer in HRIS marked separated" : '')
            . ($returned ? ", {$returned} back in HRIS" : '');

        ActivityLogService::log('updated', 'Employees', ($scheduled ? 'Monthly sync' : 'Synced employees') . " from HRIS: {$summary}", properties: [
            'added' => $added,
            'skipped' => $skipped,
            'updated' => collect($changes)->map(fn ($c) => ['employee' => $c[0]->name] + collect($c[1])->map(fn ($v, $k) => $k === 'monthly_salary' ? 'updated' : ['from' => $c[0]->{$k}, 'to' => $v])->all())->values()->all(),
            'merged' => collect($merges)->map(fn ($m) => ['employee' => $m[0]->name, 'kept' => "#{$m[0]->id} ({$m[0]->employee_id})", 'removed' => "#{$m[1]->id} ({$m[1]->employee_id})"])->all(),
        ]);

        return [
            'message' => "Sync from HRIS: {$summary}.",
            'added' => count($rows),
            'updated' => count($changes),
            'unchanged' => $unchanged,
            'merged' => count($merges),
            'separated' => $separated,
            'returned' => $returned,
            'skipped' => $skipped,
        ];
    }

    /** HRIS office description (lowercase) → office id */
    private function hrisOfficeMap(): Collection
    {
        return DB::table('hris_office_map')->pluck('office_id', 'hris_office')
            ->mapWithKeys(fn ($officeId, $name) => [mb_strtolower(trim($name)) => $officeId]);
    }

    /**
     * HRIS can list a person several times (one record per appointment): keep each person's latest record
     * (latest effectivity date, then latest record id).
     */
    private function latestRecords(Collection $records): Collection
    {
        $effective = fn ($e) => preg_match('/^\d{4}-\d{2}-\d{2}/', (string) ($e['effectivity'] ?? '')) ? substr($e['effectivity'], 0, 10) : '';
        $unnamed = 0;

        return $records
            ->filter(fn ($e) => is_array($e))
            ->groupBy(fn ($e) => (string) (trim((string) ($e['employee_id_number'] ?? '')) ?: ($e['id'] ?? null) ?: 'unnamed-' . (++$unnamed)))
            ->map(fn ($group) => $group->sortBy([
                fn ($a, $b) => strcmp($effective($a), $effective($b)),
                fn ($a, $b) => ($a['record_id'] ?? 0) <=> ($b['record_id'] ?? 0),
            ])->last())
            ->values();
    }
}
