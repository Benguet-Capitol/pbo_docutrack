<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "global_search.use": the navbar search, for every role (each result group still needs that module's View permission).
 * "connections.view": System → Connections (HRIS, the shared APIs, the server's setup), for Administrator and Developer.
 * Removes "system.status": the weekly backup and HRIS sync status moved from the Dashboard to Connections.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'global_search.use' => ['label' => 'Use the search in the top bar', 'roles' => null],
        'connections.view' => ['label' => 'See connections and server checks', 'roles' => ['Administrator', 'Developer']],
    ];

    private const REMOVED = 'system.status';
    private const REMOVED_ROLES = ['Administrator', 'Developer'];

    public function up(): void
    {
        $sort = (int) DB::table('permissions')->where('group', 'Administration')->max('sort_order');

        foreach (self::PERMISSIONS as $key => ['label' => $label, 'roles' => $roles]) {
            if (!DB::table('permissions')->where('key', $key)->exists()) {
                DB::table('permissions')->insert([
                    'key' => $key,
                    'label' => $label,
                    'group' => 'Administration',
                    'sort_order' => ++$sort,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $permission = DB::table('permissions')->where('key', $key)->value('id');
            $roleIds = $roles === null ? DB::table('roles')->pluck('id') : DB::table('roles')->whereIn('name', $roles)->pluck('id');
            DB::table('permission_role')->insertOrIgnore(
                $roleIds->map(fn ($role) => ['role_id' => $role, 'permission_id' => $permission])->all()
            );
        }

        $removed = DB::table('permissions')->where('key', self::REMOVED)->value('id');
        if ($removed) {
            DB::table('permission_role')->where('permission_id', $removed)->delete();
            DB::table('permissions')->where('id', $removed)->delete();
        }

        RoleService::clearCache();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('key', array_keys(self::PERMISSIONS))->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        // "system.status" back, as create_job_runs_table made it
        if (!DB::table('permissions')->where('key', self::REMOVED)->exists()) {
            $sort = (int) DB::table('permissions')->where('key', 'activity_logs.view')->value('sort_order');
            DB::table('permissions')->insert([
                'key' => self::REMOVED,
                'label' => 'See backup and HRIS sync status',
                'group' => 'Administration',
                'sort_order' => $sort + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $permission = DB::table('permissions')->where('key', self::REMOVED)->value('id');
        DB::table('permission_role')->insertOrIgnore(
            DB::table('roles')->whereIn('name', self::REMOVED_ROLES)->pluck('id')
                ->map(fn ($role) => ['role_id' => $role, 'permission_id' => $permission])->all()
        );

        RoleService::clearCache();
    }
};
