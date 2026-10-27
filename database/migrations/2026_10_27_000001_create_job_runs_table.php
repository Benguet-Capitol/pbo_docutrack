<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Background jobs report how they went (nightly backup, HRIS sync), so a failure shows on the Dashboard instead of
 * only in a log file. "system.status" lets a role see that status (Administrator and Developer).
 */
return new class extends Migration
{
    private const KEY = 'system.status';
    private const ROLES = ['Administrator', 'Developer'];

    public function up(): void
    {
        if (!Schema::hasTable('job_runs')) {
            Schema::create('job_runs', function (Blueprint $table) {
                $table->id();
                $table->string('job', 50)->index();
                $table->boolean('succeeded');
                $table->text('message')->nullable();
                $table->timestamp('ran_at')->index();
            });
        }

        if (!DB::table('permissions')->where('key', self::KEY)->exists()) {
            $sort = (int) DB::table('permissions')->where('key', 'activity_logs.view')->value('sort_order');
            DB::table('permissions')->insert([
                'key' => self::KEY,
                'label' => 'See backup and HRIS sync status',
                'group' => 'Administration',
                'sort_order' => $sort + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permission = DB::table('permissions')->where('key', self::KEY)->value('id');
        DB::table('permission_role')->insertOrIgnore(
            DB::table('roles')->whereIn('name', self::ROLES)->pluck('id')
                ->map(fn ($role) => ['role_id' => $role, 'permission_id' => $permission])->all()
        );

        RoleService::clearCache();
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('key', self::KEY)->value('id');
        if ($permission) {
            DB::table('permission_role')->where('permission_id', $permission)->delete();
            DB::table('permissions')->where('id', $permission)->delete();
        }
        Schema::dropIfExists('job_runs');
        RoleService::clearCache();
    }
};
