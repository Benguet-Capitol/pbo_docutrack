<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Leaves are personal data (Data Privacy Act): "leaves.view_all" lets a role see every employee's leaves. Without it,
 * a user sees and handles only the leaves of the employee linked to their account (users.employee_id).
 * Given to Administrative, Administrator and Developer; tick it for other roles on the Roles & Permissions page.
 */
return new class extends Migration
{
    private const KEY = 'leaves.view_all';
    private const ROLES = ['Administrative', 'Administrator', 'Developer'];

    public function up(): void
    {
        if (!DB::table('permissions')->where('key', self::KEY)->exists()) {
            $sort = (int) DB::table('permissions')->where('key', 'leaves.view')->value('sort_order');
            DB::table('permissions')->insert([
                'key' => self::KEY,
                'label' => "View all employees' leaves",
                'group' => 'Leaves',
                'sort_order' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permission = DB::table('permissions')->where('key', self::KEY)->value('id');
        DB::table('permission_role')->insertOrIgnore(
            DB::table('roles')->whereIn('name', self::ROLES)->pluck('id')
                ->map(fn ($role) => ['role_id' => $role, 'permission_id' => $permission])->all()
        );

        // Every role sees the Dashboard's HR summary: its leave figures cover only the user's own leaves unless they
        // have "leaves.view_all" (travel orders, pass slips, tardiness and time slips show everyone, as those pages do)
        $hrSummary = DB::table('permissions')->where('key', 'hr_summary.view')->value('id');
        if ($hrSummary) {
            DB::table('permission_role')->insertOrIgnore(
                DB::table('roles')->pluck('id')->map(fn ($role) => ['role_id' => $role, 'permission_id' => $hrSummary])->all()
            );
        }

        RoleService::clearCache();
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('key', self::KEY)->value('id');
        if ($permission) {
            DB::table('permission_role')->where('permission_id', $permission)->delete();
            DB::table('permissions')->where('id', $permission)->delete();
        }
        RoleService::clearCache();
    }
};
