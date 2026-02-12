<?php

namespace App\Services;

use App\Models\User;

class RoleService
{
    /**
     * Define role permissions
     */
    public const ROLES = [
        'Developer' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.finalize',
            'documents.view.all',
            'documents.delete',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
        'Administrator' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.finalize',
            'documents.receive',
            'documents.view.all',
            'documents.delete',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
        'Receiving' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
        'Reviewer' => [
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
        'Supervisor' => [
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.finalize',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
        'Administrative' => [
            'documents.finalize',
            'documents.receive',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
        ],
    ];

    /**
     * Check if user has permission
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        if (!isset(self::ROLES[$user->usertype])) {
            return false;
        }

        return in_array($permission, self::ROLES[$user->usertype]);
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
     * Check if user can receive documents
     */
    public static function canReceiveDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.receive');
    }

    /**
     * Check if user can finalize documents
     */
    public static function canFinalizeDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.finalize');
    }

    /**
     * Get user permissions
     */
    public static function getUserPermissions(User $user): array
    {
        return self::ROLES[$user->usertype] ?? [];
    }

    /**
     * Get all permissions for a role
     */
    public static function getRolePermissions(string $role): array
    {
        return self::ROLES[$role] ?? [];
    }

    /**
     * Check if user can delete documents
     */
    public static function canDeleteDocument(User $user): bool
    {
        return self::hasPermission($user, 'documents.delete');
    }

    /**
     * Check if user can view a specific document
     * Developer and Administrator can view all documents
     * Receiving, Reviewer, Supervisor, Administrative can only view documents:
     * - Where the document's user_id matches their ID (document is assigned/under them)
     * - OR forwarded to them directly (as a user, not to office/municipality)
     */
    public static function canViewDocument(User $user, $document): bool
    {
        // Can view all documents
        if (self::hasPermission($user, 'documents.view.all')) {
            return true;
        }

        // Can only view assigned documents
        if (self::hasPermission($user, 'documents.view.assigned')) {
            // Can view documents that are assigned to them (document.user_id matches)
            if ($document->user_id === $user->id) {
                return true;
            }

            // Can view documents forwarded to them as a user
            // Check the latest document transaction to see if forwarded to this user
            if ($document->latest_transaction && 
                $document->latest_transaction->forwarded_to_user_id === $user->id) {
                return true;
            }

            // Alternative: check via relationship if latest_transaction not eager loaded
            $latestTransaction = $document->transactions()->latest('created_at')->first();
            if ($latestTransaction && 
                $latestTransaction->forwarded_to_user_id === $user->id) {
                return true;
            }

            return false;
        }

        return false;
    }
}
