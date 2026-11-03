<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reviewer, Receiving and Supervisor can also create and edit leaves. Without "leaves.view_all",
 * that is only the leaves of the employee linked to their account.
 */
return new class extends Migration
{
    private const KEYS = ['leaves.create', 'leaves.edit'];
    private const ROLES = ['Reviewer', 'Receiving', 'Supervisor'];

    public function up(): void
    {
        $permissions = DB::table('permissions')->whereIn('key', self::KEYS)->pluck('id');

        foreach (DB::table('roles')->whereIn('name', self::ROLES)->pluck('id') as $role) {
            DB::table('permission_role')->insertOrIgnore(
                $permissions->map(fn ($permission) => ['role_id' => $role, 'permission_id' => $permission])->all()
            );
        }

        RoleService::clearCache();
    }

    public function down(): void
    {
        DB::table('permission_role')
            ->whereIn('role_id', DB::table('roles')->whereIn('name', self::ROLES)->pluck('id'))
            ->whereIn('permission_id', DB::table('permissions')->whereIn('key', self::KEYS)->pluck('id'))
            ->delete();

        RoleService::clearCache();
    }
};
