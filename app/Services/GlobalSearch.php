<?php

namespace App\Services;

use App\Models\CertificateOfAppearance;
use App\Models\Document;
use App\Models\Leave;
use App\Models\PassSlip;
use App\Models\Record;
use App\Models\Tardiness;
use App\Models\TimeSlip;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The navbar search (as in pbo-registryV2): control numbers, names, places and titles across the DocuTrack and Admin modules.
 * Not the Auxiliary or System modules, and never Trash. Each group needs that module's View permission, as its page does, and
 * keeps the page's own limits: Leaves are the user's own without "leaves.view_all", Administrative records need their permission.
 * A result links to its module's list, filtered to the record (?search=) in its year (?year=) or Records tab (?tab=).
 */
class GlobalSearch
{
    public const PER_GROUP = 5;

    public static function run(User $user, string $query): array
    {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $can = fn (string $permission) => RoleService::hasPermission($user, $permission);
        $byEmployee = fn (string $relation) => fn (Builder $query) => $query->whereHas($relation, fn ($e) => $e->where('name', 'like', $like));
        $groups = [];

        if ($can('documents.view')) {
            $groups[] = self::group('Documents', 'fa-folder-open', Document::query()
                ->where(fn ($w) => $w->where('tracking_no', 'like', $like)->orWhere('particulars', 'like', $like)->orWhere('source', 'like', $like))
                ->latest('date')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (Document $d) => [
                    'title' => "{$d->tracking_no} · " . Str::limit((string) $d->particulars, 70),
                    'subtitle' => self::join([$d->document_type, $d->source, self::date($d->date)]),
                    'url' => route('documents.index', ['search' => $d->tracking_no, 'year' => self::year($d->date)]),
                ]));
        }

        if ($can('records.view')) {
            $groups[] = self::group('Records', 'fa-archive', Record::query()
                ->when(!RoleService::canViewAdministrativeRecords($user), fn ($w) => $w->where('record_type', '!=', 'Administrative'))
                ->where(fn ($w) => $w->where('record_no', 'like', $like)->orWhere('title', 'like', $like))
                ->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (Record $r) => [
                    'title' => "{$r->record_no} · " . Str::limit((string) $r->title, 70),
                    'subtitle' => self::join([$r->record_type, $r->record_subtype]),
                    'url' => route('records.index', ['search' => $r->record_no, 'tab' => $r->record_type]),
                ]));
        }

        if ($can('leaves.view')) {
            // One result per application (its rows share a control number); the user's own without "View all"
            $groups[] = self::group('Leaves', 'fa-calendar-check', Leave::with('employee:id,name')
                ->when(!$can('leaves.view_all'), fn ($w) => $w->where('employee_id', $user->employee_id ?? 0))
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere($byEmployee('employee')))
                ->latest('id')->limit(self::PER_GROUP * 4)->get()->unique('control_no')->take(self::PER_GROUP)
                ->map(fn (Leave $l) => [
                    'title' => "{$l->control_no} · {$l->employee?->name}",
                    'subtitle' => self::join([$l->type_of_leave, 'Filed ' . self::date($l->date_of_filing)]),
                    'url' => route('leaves.index', ['search' => $l->control_no, 'year' => self::year($l->date_of_filing)]),
                ]));
        }

        if ($can('travel_orders.view')) {
            $groups[] = self::group('Travel Orders', 'fa-route', TravelOrder::with('employees:id,name')
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere('going_to', 'like', $like)->orWhere($byEmployee('employees')))
                ->latest('date')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (TravelOrder $t) => [
                    'title' => "{$t->control_no} · {$t->going_to}",
                    'subtitle' => self::join([self::names($t->employees), self::date($t->date)]),
                    'url' => route('travel-orders.index', ['search' => $t->control_no, 'year' => self::year($t->date)]),
                ]));
        }

        if ($can('pass_slips.view')) {
            $groups[] = self::group('Pass Slips', 'fa-door-open', PassSlip::with('employees:id,name')
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere('location', 'like', $like)->orWhere($byEmployee('employees')))
                ->latest('date')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (PassSlip $p) => [
                    'title' => "{$p->control_no} · {$p->location}",
                    'subtitle' => self::join([self::names($p->employees), self::date($p->date)]),
                    'url' => route('pass-slips.index', ['search' => $p->control_no, 'year' => self::year($p->date)]),
                ]));
        }

        if ($can('time_slips.view')) {
            $groups[] = self::group('Time Slips', 'fa-clock', TimeSlip::with('requestingEmployee:id,name')
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere($byEmployee('requestingEmployee')))
                ->latest('date')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (TimeSlip $t) => [
                    'title' => "{$t->control_no} · {$t->requestingEmployee?->name}",
                    'subtitle' => self::join([Str::limit((string) $t->reason, 60), self::date($t->date)]),
                    'url' => route('time-slips.index', ['search' => $t->control_no, 'year' => self::year($t->date)]),
                ]));
        }

        if ($can('tardiness.view')) {
            $groups[] = self::group('Tardiness / Undertime', 'fa-hourglass-half', Tardiness::with('employee:id,name')
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere($byEmployee('employee')))
                ->latest('date_filed')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (Tardiness $t) => [
                    'title' => "{$t->control_no} · {$t->employee?->name}",
                    'subtitle' => self::join([$t->type, self::date($t->requested_date)]),
                    'url' => route('tardiness.index', ['search' => $t->control_no, 'year' => self::year($t->date_filed)]),
                ]));
        }

        if ($can('certificate_of_appearances.view')) {
            $groups[] = self::group('Certificates of Appearance', 'fa-certificate', CertificateOfAppearance::query()
                ->where(fn ($w) => $w->where('control_no', 'like', $like)->orWhere('name', 'like', $like)->orWhere('office', 'like', $like))
                ->latest('date')->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (CertificateOfAppearance $c) => [
                    'title' => "{$c->control_no} · {$c->name}",
                    'subtitle' => self::join([$c->office, self::date($c->date)]),
                    'url' => route('certificates-of-appearance.index', ['search' => $c->control_no, 'year' => self::year($c->date)]),
                ]));
        }

        return array_values(array_filter($groups, fn ($group) => $group['items']));
    }

    private static function group(string $label, string $icon, Collection $items): array
    {
        return ['label' => $label, 'icon' => $icon, 'items' => $items->values()->all()];
    }

    private static function join(array $parts): string
    {
        return implode(' · ', array_filter($parts, fn ($part) => $part !== null && $part !== ''));
    }

    private static function names(Collection $employees): string
    {
        return Str::limit($employees->pluck('name')->implode(', '), 60);
    }

    /** "2026-10-23" (a date cast, or the column's own text) */
    private static function ymd($value): ?string
    {
        if (!$value) {
            return null;
        }

        return substr(is_string($value) ? $value : $value->format('Y-m-d'), 0, 10);
    }

    private static function date($value): ?string
    {
        $ymd = self::ymd($value);

        return $ymd ? date('M j, Y', strtotime($ymd)) : null;
    }

    private static function year($value): ?int
    {
        $ymd = self::ymd($value);

        return $ymd ? (int) substr($ymd, 0, 4) : null;
    }
}
