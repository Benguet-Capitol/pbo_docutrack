<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Roles & Permissions module: manage roles and which permissions each role has.
 * The permission catalog itself is fixed (defined by migrations).
 */
class RoleController extends Controller
{
    /**
     * Roles with their permission keys and user counts, plus the permission catalog grouped by module.
     */
    public function index(): JsonResponse
    {
        $allKeys = Permission::orderBy('sort_order')->pluck('key');

        $roles = Role::with('permissions:id,key')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'users_count' => $role->users_count,
                'permissions' => $role->is_system ? $allKeys->all() : $role->permissions->pluck('key')->values()->all(),
            ]);

        $permissionGroups = Permission::orderBy('sort_order')
            ->get(['key', 'label', 'group'])
            ->groupBy('group')
            ->map(fn ($permissions, $group) => ['group' => $group, 'permissions' => $permissions->values()])
            ->values();

        return response()->json([
            'roles' => $roles,
            'permission_groups' => $permissionGroups,
        ]);
    }

    /**
     * Role names only, for the role dropdown in the Users module.
     */
    public function options(): JsonResponse
    {
        return response()->json(Role::orderBy('name')->pluck('name'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRole($request);

        $role = DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
            $this->syncPermissions($role, $validated['permissions'] ?? []);

            return $role;
        });

        RoleService::clearCache();

        return response()->json(['message' => "Role \"{$role->name}\" created."], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['error' => 'The Developer role is a system role and cannot be changed.'], 422);
        }

        $validated = $this->validateRole($request, $role);
        $permissions = $validated['permissions'] ?? [];

        // Keep the signed-in user from locking themselves out of this module
        if ($request->user()->usertype === $role->name && !in_array('roles.manage', $permissions, true)) {
            return response()->json(['errors' => ['permissions' => ['You cannot remove "Manage roles and permissions" from your own role.']]], 422);
        }

        DB::transaction(function () use ($role, $validated, $permissions) {
            $oldName = $role->name;

            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Users reference their role by name
            if ($oldName !== $role->name) {
                User::withTrashed()->where('usertype', $oldName)->update(['usertype' => $role->name]);
            }

            $this->syncPermissions($role, $permissions);
        });

        RoleService::clearCache();

        return response()->json(['message' => "Role \"{$role->name}\" updated."]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['error' => 'The Developer role is a system role and cannot be deleted.'], 422);
        }

        $usersCount = User::withTrashed()->where('usertype', $role->name)->count();
        if ($usersCount > 0) {
            return response()->json(['error' => "This role is assigned to {$usersCount} user(s). Assign them another role first."], 422);
        }

        $role->delete();
        RoleService::clearCache();

        return response()->json(['message' => "Role \"{$role->name}\" deleted."]);
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'key')],
        ]);
    }

    /**
     * Replace the role's permissions and log what was granted or revoked.
     */
    private function syncPermissions(Role $role, array $keys): void
    {
        $ids = Permission::whereIn('key', $keys)->pluck('id', 'key');
        $result = $role->permissions()->sync($ids->values()->all());

        $keyById = Permission::whereIn('id', array_merge($result['attached'], $result['detached']))->pluck('key', 'id');
        $granted = collect($result['attached'])->map(fn ($id) => $keyById[$id])->values()->all();
        $revoked = collect($result['detached'])->map(fn ($id) => $keyById[$id])->values()->all();

        if ($granted || $revoked) {
            ActivityLogService::log(
                'updated',
                'Roles',
                "Updated permissions of role {$role->name}",
                subject: $role,
                subjectLabel: $role->name,
                properties: ['granted' => $granted, 'revoked' => $revoked],
            );
        }
    }
}
