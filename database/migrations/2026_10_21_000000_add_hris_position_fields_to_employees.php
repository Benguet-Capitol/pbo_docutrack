<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employees follow pbo-registryV2's structure: HRIS position title, parenthetical title and status
 * (Permanent / Casual / COS). `designation` becomes the printed title derived from them
 * (App\Models\Employee::titleOf): a department head's parenthetical title, otherwise the position title.
 * Also adds the HRIS office → office map used by "Sync from HRIS".
 */
return new class extends Migration
{
    /** HRIS office description → office id (same ids as pbo-registryV2's hris_office_map) */
    private const HRIS_OFFICES = [
        'Office of the Governor' => 1,
        'Benguet Technical School' => 4,
        "Provincial Warden's Office" => 5,
        'Provincial Library' => 6,
        'Office of the Vice Governor' => 7,
        'Office of the Sangguniang Panlalawigan Members' => 8,
        'Office of the Secretary to the Sangguniang Panlalawigan' => 9,
        'Provincial Accounting Office' => 10,
        "Provincial Assessor's Office" => 11,
        'Provincial Budget Office' => 12,
        'Provincial General Services Office' => 13,
        'Provincial Human Resource Management and Development Office' => 14,
        'Provincial Legal Office' => 15,
        'Provincial Planning and Development Office' => 16,
        "Provincial Treasurer's Office" => 17,
        'PGO-Board of Tax' => 18,
        "PGO-Prosecutor's Office" => 20,
        'Provincial Disaster Risk Reduction Management Office' => 22,
        'Provincial Social Welfare and Development Office' => 23,
        'Northern Benguet District Hospital' => 24,
        'Atok District Hospital' => 25,
        'Dennis Molintas District Hospital' => 26,
        'Itogon District Hospital' => 27,
        'Kapangan District Hospital' => 28,
        'Provincial Health Office' => 29,
        'Provincial Tourism and Cultural Affairs Office' => 31,
        'Provincial Engineering Office' => 32,
        'Provincial Agriculture Office' => 33,
        'Provincial Environment and Natural Resources Office - LGU' => 34,
        'Provincial Veterinary Office' => 35,
        'Benguet General Hospital' => 37,
    ];

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position_title')->nullable()->after('name');
            $table->string('parenthetical_title')->nullable()->after('position_title');
            $table->string('status', 20)->nullable()->after('parenthetical_title')->index();
        });

        // Split existing designations: "Administrative Aide IV (Budgeting Aide) (Casual)" →
        // position "Administrative Aide IV", parenthetical "Budgeting Aide", status "Casual".
        foreach (DB::table('employees')->get(['id', 'designation']) as $employee) {
            $designation = trim((string) $employee->designation);
            $status = 'Permanent';
            if (preg_match('/\s*(?:\(|-)\s*(casual|contract of service|cos|job order)\s*\)?\s*$/i', $designation, $s)) {
                $status = mb_strtolower($s[1]) === 'casual' ? 'Casual' : 'COS';
                $designation = trim(substr($designation, 0, -strlen($s[0])));
            }
            [$position, $parenthetical] = preg_match('/^(.*?)\s*\(([^()]+)\)\s*$/', $designation, $m) && trim($m[1]) !== ''
                ? [trim($m[1]), trim($m[2])]
                : [$designation, null];

            DB::table('employees')->where('id', $employee->id)->update([
                'position_title' => $position !== '' ? $position : null,
                'parenthetical_title' => $parenthetical,
                'status' => $status,
                // Printed title: a department head's parenthetical title, e.g. "Provincial Budget Officer"
                'designation' => $parenthetical !== null && stripos($position, 'Department Head') !== false ? $parenthetical : ($position ?: $employee->designation),
            ]);
        }

        Schema::create('hris_office_map', function (Blueprint $table) {
            $table->id();
            $table->string('hris_office')->unique();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->timestamps();
        });

        $existing = DB::table('offices')->pluck('id')->all();
        $now = now();
        DB::table('hris_office_map')->insert(collect(self::HRIS_OFFICES)
            ->filter(fn ($officeId) => in_array($officeId, $existing))
            ->map(fn ($officeId, $hrisOffice) => ['hris_office' => $hrisOffice, 'office_id' => $officeId, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('hris_office_map');

        // Put the full position back into designation before the columns go
        foreach (DB::table('employees')->get(['id', 'position_title', 'parenthetical_title', 'status', 'designation']) as $employee) {
            if (!$employee->position_title) continue;
            $full = $employee->parenthetical_title ? "{$employee->position_title} ({$employee->parenthetical_title})" : $employee->position_title;
            if (in_array($employee->status, ['Casual', 'COS'], true)) $full .= " ({$employee->status})";
            DB::table('employees')->where('id', $employee->id)->update(['designation' => $full]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['position_title', 'parenthetical_title', 'status']);
        });
    }
};
