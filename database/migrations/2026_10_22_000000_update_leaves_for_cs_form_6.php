<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leaves as CS Form No. 6 (Revised 2020) applications:
 * - several leave types per application: one row per type, the rows sharing the control number (no longer unique;
 *   LeaveController keeps it unique per application);
 * - "5. SALARY": the monthly salary as of filing, saved on the leave; employees keep their current one (HRIS sync);
 * - 7.B recommendation ("approval" / "disapproval" with the reason; null while pending);
 * - signatories: 7.A certification of leave credits (PHRMDO), 7.B recommendation (Provincial Budget Officer) and
 *   the authorized official, each with its title (the officer, OIC or Acting); null = the default signatory.
 * And for employees and users:
 * - employees.separated_at: set by the HRIS sync when the employee is no longer in HRIS (resigned, retired...);
 *   they are hidden from pickers but stay on their records;
 * - users.employee_id: the user's employee record (before, matched by name), filled in here by exact name.
 */
return new class extends Migration
{
    private const SIGNATORIES = ['certified_by', 'recommended_by', 'approved_by'];

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('monthly_salary', 12, 2)->nullable()->after('status');
            $table->timestamp('separated_at')->nullable()->after('detailed_to_pbo')->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('fk_office_id')->constrained('employees')->nullOnDelete();
        });
        self::linkUsersToEmployees();

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropUnique(['control_no']);
            $table->index('control_no');

            $table->decimal('salary', 12, 2)->nullable()->after('date_of_filing');

            $table->string('recommendation', 20)->nullable()->after('other_type');
            $table->string('disapproval_reason', 500)->nullable()->after('recommendation');

            $after = 'disapproval_reason';
            foreach (self::SIGNATORIES as $signatory) {
                $table->foreignId("{$signatory}_employee_id")->nullable()->after($after)->constrained('employees')->nullOnDelete();
                $table->string("{$signatory}_title")->nullable()->after("{$signatory}_employee_id");
                $after = "{$signatory}_title";
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            foreach (self::SIGNATORIES as $signatory) {
                $table->dropConstrainedForeignId("{$signatory}_employee_id");
                $table->dropColumn("{$signatory}_title");
            }
            $table->dropColumn(['recommendation', 'disapproval_reason', 'salary']);

            $table->dropIndex(['control_no']);
            $table->unique('control_no');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['separated_at']);
            $table->dropColumn(['monthly_salary', 'separated_at']);
        });
    }

    /** Each user's employee: the one live employee with exactly the user's name */
    public static function linkUsersToEmployees(): void
    {
        $byName = \Illuminate\Support\Facades\DB::table('employees')->whereNull('deleted_at')->get(['id', 'name'])->groupBy('name');
        foreach (\Illuminate\Support\Facades\DB::table('users')->whereNull('employee_id')->get(['id', 'name']) as $user) {
            if (($matches = $byName->get($user->name)) && $matches->count() === 1) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['employee_id' => $matches->first()->id]);
            }
        }
    }
};
