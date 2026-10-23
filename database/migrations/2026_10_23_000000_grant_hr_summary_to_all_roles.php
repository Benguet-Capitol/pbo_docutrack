<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every role sees the Dashboard's HR summary (still revocable per role in Roles & Permissions).
 * Safe to run more than once: existing grants are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('permissions')->where('key', 'hr_summary.view')->value('id');
        if (!$permission) {
            return;
        }

        DB::table('permission_role')->insertOrIgnore(
            DB::table('roles')->pluck('id')->map(fn ($role) => ['role_id' => $role, 'permission_id' => $permission])->all()
        );
        RoleService::clearCache();
    }

    public function down(): void
    {
        // Which roles had the permission before isn't known; grants are left as they are
    }
};
