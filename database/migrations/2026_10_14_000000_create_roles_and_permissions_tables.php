<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves roles and their permissions from code (the old RoleService::ROLES constant) into the database
 * so they can be managed from the Roles & Permissions module. Users keep referencing their role by name
 * (users.usertype). Seeded with each role's current access, so nothing changes for users when this runs.
 */
return new class extends Migration
{
    /** Permission catalog: group => [key => label]. Every key is enforced by a route, controller or page. */
    private const PERMISSIONS = [
        'Documents' => [
            'documents.view' => 'View documents',
            'documents.create' => 'Create documents',
            'documents.edit' => 'Edit documents',
            'documents.edit_date' => "Change a document's date",
            'documents.forward' => 'Forward documents',
            'documents.receive' => 'Receive documents forwarded to them',
            'documents.receive_office' => 'Receive documents forwarded to an office or municipality',
            'documents.finalize' => 'End document transactions',
            'documents.delete' => 'Delete documents',
        ],
        'Records' => [
            'records.view' => 'View records',
            'records.view.administrative' => 'View Administrative records',
            'records.create' => 'Create records',
            'records.edit' => 'Edit records',
            'records.delete' => 'Delete records',
        ],
        'Locator Chart' => [
            'locator_chart.view' => 'View the locator chart',
            'notice_of_meetings.manage' => 'Create, edit and delete notices of meetings',
        ],
        'Leaves' => [
            'leaves.view' => 'View leaves',
            'leaves.create' => 'Create leaves',
            'leaves.edit' => 'Edit leaves',
            'leaves.delete' => 'Delete leaves',
        ],
        'Travel Orders' => [
            'travel_orders.view' => 'View travel orders',
            'travel_orders.create' => 'Create travel orders',
            'travel_orders.edit' => 'Edit travel orders',
            'travel_orders.delete' => 'Delete travel orders',
        ],
        'Pass Slips' => [
            'pass_slips.view' => 'View pass slips',
            'pass_slips.create' => 'Create pass slips',
            'pass_slips.edit' => 'Edit pass slips',
            'pass_slips.delete' => 'Delete pass slips',
        ],
        'Time Slips' => [
            'time_slips.view' => 'View time slips',
            'time_slips.create' => 'Create time slips',
            'time_slips.edit' => 'Edit time slips',
            'time_slips.delete' => 'Delete time slips',
        ],
        'Tardiness / Undertime' => [
            'tardiness.view' => 'View tardiness and undertime',
            'tardiness.create' => 'Create tardiness and undertime',
            'tardiness.edit' => 'Edit tardiness and undertime',
            'tardiness.delete' => 'Delete tardiness and undertime',
        ],
        'Certificates of Appearance' => [
            'certificate_of_appearances.view' => 'View certificates of appearance',
            'certificate_of_appearances.create' => 'Create certificates of appearance',
            'certificate_of_appearances.edit' => 'Edit certificates of appearance',
            'certificate_of_appearances.delete' => 'Delete certificates of appearance',
        ],
        'Dashboard' => [
            'hr_summary.view' => 'View the HR summary',
        ],
        'Employees' => [
            'employees.view' => 'View employees',
            'employees.create' => 'Create employees',
            'employees.edit' => 'Edit employees',
            'employees.delete' => 'Delete employees',
        ],
        'Offices' => [
            'offices.view' => 'View offices',
            'offices.create' => 'Create offices',
            'offices.edit' => 'Edit offices',
            'offices.delete' => 'Delete offices',
        ],
        'Municipalities' => [
            'municipalities.view' => 'View municipalities',
            'municipalities.create' => 'Create municipalities',
            'municipalities.edit' => 'Edit municipalities',
            'municipalities.delete' => 'Delete municipalities',
        ],
        'Administration' => [
            'users.manage' => 'Manage users and reset passwords',
            'roles.manage' => 'Manage roles and permissions',
            'trash.manage' => 'Restore and permanently delete items in the Trash',
            'activity_logs.view' => 'View the activity log',
        ],
    ];

    /** Each role's access at the time of this migration. Developer is a system role that always has every permission. */
    private const ROLES = [
            'Developer' => [
                'documents.create', 'documents.edit', 'documents.forward', 'documents.receive',
                'documents.finalize', 'documents.delete', 'offices.view', 'offices.create',
                'offices.edit', 'offices.delete', 'municipalities.view', 'municipalities.create',
                'municipalities.edit', 'municipalities.delete', 'employees.view', 'employees.create',
                'employees.edit', 'employees.delete', 'leaves.view', 'leaves.create',
                'leaves.edit', 'leaves.delete', 'pass_slips.view', 'pass_slips.create',
                'pass_slips.edit', 'pass_slips.delete', 'time_slips.view', 'time_slips.create',
                'time_slips.edit', 'time_slips.delete', 'certificate_of_appearances.view', 'certificate_of_appearances.create',
                'certificate_of_appearances.edit', 'certificate_of_appearances.delete', 'travel_orders.view', 'travel_orders.create',
                'travel_orders.edit', 'travel_orders.delete', 'tardiness.view', 'tardiness.create',
                'tardiness.edit', 'tardiness.delete', 'records.view', 'records.create',
                'records.edit', 'records.delete', 'records.view.administrative', 'hr_summary.view',
                'documents.view', 'locator_chart.view', 'notice_of_meetings.manage', 'documents.edit_date',
                'users.manage', 'roles.manage', 'trash.manage', 'activity_logs.view',
                'documents.receive_office',
            ],
            'Administrator' => [
                'documents.create', 'documents.edit', 'documents.forward', 'documents.finalize',
                'documents.receive', 'documents.delete', 'offices.view', 'offices.create',
                'offices.edit', 'offices.delete', 'municipalities.view', 'municipalities.create',
                'municipalities.edit', 'municipalities.delete', 'employees.view', 'employees.create',
                'employees.edit', 'employees.delete', 'leaves.view', 'leaves.create',
                'leaves.edit', 'leaves.delete', 'pass_slips.view', 'pass_slips.create',
                'pass_slips.edit', 'pass_slips.delete', 'time_slips.view', 'time_slips.create',
                'time_slips.edit', 'time_slips.delete', 'certificate_of_appearances.view', 'certificate_of_appearances.create',
                'certificate_of_appearances.edit', 'certificate_of_appearances.delete', 'travel_orders.view', 'travel_orders.create',
                'travel_orders.edit', 'travel_orders.delete', 'tardiness.view', 'tardiness.create',
                'tardiness.edit', 'tardiness.delete', 'records.view', 'records.create',
                'records.edit', 'records.delete', 'records.view.administrative', 'hr_summary.view',
                'documents.view', 'locator_chart.view', 'notice_of_meetings.manage', 'documents.edit_date',
                'users.manage', 'roles.manage', 'trash.manage', 'activity_logs.view',
                'documents.receive_office',
            ],
            'Receiving' => [
                'documents.create', 'documents.edit', 'documents.forward', 'documents.receive',
                'documents.finalize', 'offices.view', 'offices.edit', 'municipalities.view',
                'municipalities.edit', 'certificate_of_appearances.view', 'certificate_of_appearances.create', 'certificate_of_appearances.edit',
                'certificate_of_appearances.delete', 'records.view', 'records.create', 'records.edit',
                'records.delete', 'leaves.view', 'pass_slips.view', 'pass_slips.create',
                'time_slips.view', 'time_slips.create', 'travel_orders.view', 'tardiness.view',
                'tardiness.create', 'documents.view', 'locator_chart.view', 'notice_of_meetings.manage',
                'documents.receive_office',
            ],
            'Reviewer' => [
                'documents.create', 'documents.edit', 'documents.forward', 'documents.receive',
                'offices.view', 'offices.edit', 'municipalities.view', 'municipalities.edit',
                'certificate_of_appearances.view', 'certificate_of_appearances.create', 'certificate_of_appearances.edit', 'certificate_of_appearances.delete',
                'records.view', 'records.create', 'records.edit', 'records.delete',
                'leaves.view', 'pass_slips.view', 'pass_slips.create', 'time_slips.view',
                'time_slips.create', 'travel_orders.view', 'tardiness.view', 'tardiness.create',
                'documents.view', 'locator_chart.view', 'notice_of_meetings.manage', 'documents.receive_office',
            ],
            'Supervisor' => [
                'documents.create', 'documents.edit', 'documents.forward', 'documents.receive',
                'documents.finalize', 'offices.view', 'offices.edit', 'municipalities.view',
                'municipalities.edit', 'certificate_of_appearances.view', 'certificate_of_appearances.create', 'certificate_of_appearances.edit',
                'certificate_of_appearances.delete', 'records.view', 'records.create', 'records.edit',
                'records.delete', 'leaves.view', 'pass_slips.view', 'pass_slips.create',
                'time_slips.view', 'time_slips.create', 'travel_orders.view', 'tardiness.view',
                'tardiness.create', 'documents.view', 'locator_chart.view', 'notice_of_meetings.manage',
                'documents.receive_office',
            ],
            'Administrative' => [
                'documents.create', 'documents.receive', 'documents.forward', 'offices.view',
                'offices.edit', 'municipalities.view', 'municipalities.edit', 'employees.view',
                'employees.create', 'employees.edit', 'employees.delete', 'records.view',
                'records.create', 'records.edit', 'records.delete', 'leaves.view',
                'leaves.create', 'leaves.edit', 'leaves.delete', 'pass_slips.view',
                'pass_slips.create', 'pass_slips.edit', 'pass_slips.delete', 'time_slips.view',
                'time_slips.create', 'time_slips.edit', 'time_slips.delete', 'certificate_of_appearances.view',
                'certificate_of_appearances.create', 'certificate_of_appearances.edit', 'certificate_of_appearances.delete', 'travel_orders.view',
                'travel_orders.create', 'travel_orders.edit', 'travel_orders.delete', 'tardiness.view',
                'tardiness.create', 'tardiness.edit', 'tardiness.delete', 'hr_summary.view',
                'records.view.administrative', 'documents.view', 'locator_chart.view', 'notice_of_meetings.manage',
            ],
    ];

    private const DESCRIPTIONS = [
        'Developer' => 'System role. Always has every permission and cannot be edited or deleted.',
        'Administrator' => 'Full access to all modules and administration.',
        'Administrative' => 'HR and administrative staff: employees, leaves, slips and travel orders.',
        'Receiving' => 'Receives and routes incoming documents.',
        'Reviewer' => 'Reviews documents assigned to them.',
        'Supervisor' => 'Reviews and ends document transactions.',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $now = now();

        $sort = 0;
        $permissionIds = [];
        foreach (self::PERMISSIONS as $group => $permissions) {
            foreach ($permissions as $key => $label) {
                $permissionIds[$key] = DB::table('permissions')->insertGetId([
                    'key' => $key,
                    'label' => $label,
                    'group' => $group,
                    'sort_order' => ++$sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (self::ROLES as $name => $permissions) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $name,
                'description' => self::DESCRIPTIONS[$name] ?? null,
                'is_system' => $name === 'Developer',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('permission_role')->insert(array_map(
                fn (string $key) => ['role_id' => $roleId, 'permission_id' => $permissionIds[$key]],
                array_values(array_intersect(array_unique($permissions), array_keys($permissionIds)))
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
