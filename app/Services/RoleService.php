<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Role-based permission checks. Roles and their permissions are stored in the database
 * and managed from the Roles & Permissions module; users reference their role by name (users.usertype).
 */
class RoleService
{
    private const CACHE_KEY = 'role_permissions';

    /** Per-request copy of the role => permission keys map */
    private static ?array $map = null;

    /**
     * Role name => permission keys. A system role (Developer) always gets every permission.
     */
    public static function rolePermissionMap(): array
    {
        return self::$map ??= Cache::rememberForever(self::CACHE_KEY, function () {
            $allKeys = Permission::orderBy('sort_order')->pluck('key')->all();

            return Role::with('permissions:id,key')->get()
                ->mapWithKeys(fn (Role $role) => [
                    $role->name => $role->is_system ? $allKeys : $role->permissions->pluck('key')->all(),
                ])
                ->all();
        });
    }

    /**
     * Forget cached permissions after roles or their permissions change.
     */
    public static function clearCache(): void
    {
        self::$map = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        return in_array($permission, self::getUserPermissions($user), true);
    }

    /**
     * Get user permissions
     */
    public static function getUserPermissions(User $user): array
    {
        return self::rolePermissionMap()[$user->usertype] ?? [];
    }

    /**
     * Check if user can create documents
     */
    public static function canCreateDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.create');
    }

    /**
     * Check if user can edit documents
     */
    public static function canEditDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.edit');
    }

    /**
     * Check if user can forward documents
     */
    public static function canForwardDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.forward');
    }

    /**
     * Check if user can finalize documents
     */
    public static function canFinalizeDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.finalize');
    }

    /**
     * Check if user can receive a specific document (mirrors canReceiveDocument in Documents.vue):
     * - forwarded directly to the user, or
     * - forwarded to an office/municipality and the user may receive those or is the one who forwarded it
     */
    public static function canReceiveSpecificDocument(User $user, $document): bool
    {
        if ($document->status !== 'forwarded' || !self::hasPermission($user, 'documents.receive')) {
            return false;
        }

        $latestTransaction = $document->transactions()->latest('created_at')->latest('id')->first();
        if (!$latestTransaction) {
            return false;
        }

        if ($latestTransaction->forwarded_to_user_id === $user->id) {
            return true;
        }

        if ($latestTransaction->forwarded_to_office_id || $latestTransaction->forwarded_to_municipality_id) {
            return self::hasPermission($user, 'documents.receive_office')
                || $latestTransaction->user_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user can view records of the "Administrative" record type.
     */
    public static function canViewAdministrativeRecords(User $user): bool
    {
        return self::hasPermission($user, 'records.view.administrative');
    }
}
