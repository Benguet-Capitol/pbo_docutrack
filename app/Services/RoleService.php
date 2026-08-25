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
            'divisions.edit',
            'plantillas.edit',
            'leaves.view',
            'leaves.create',
            'leaves.edit',
            'leaves.delete',
            'pass_slips.view',
            'pass_slips.create',
            'pass_slips.edit',
            'pass_slips.delete',
            'time_slips.view',
            'time_slips.create',
            'time_slips.edit',
            'time_slips.delete',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'travel_orders.view',
            'travel_orders.create',
            'travel_orders.edit',
            'travel_orders.delete',
            'tardiness.view',
            'tardiness.create',
            'tardiness.edit',
            'tardiness.delete',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'records.view.administrative',
            'hr_summary.view',
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
            'divisions.edit',
            'plantillas.edit',
            'leaves.view',
            'leaves.create',
            'leaves.edit',
            'leaves.delete',
            'pass_slips.view',
            'pass_slips.create',
            'pass_slips.edit',
            'pass_slips.delete',
            'time_slips.view',
            'time_slips.create',
            'time_slips.edit',
            'time_slips.delete',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'travel_orders.view',
            'travel_orders.create',
            'travel_orders.edit',
            'travel_orders.delete',
            'tardiness.view',
            'tardiness.create',
            'tardiness.edit',
            'tardiness.delete',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'records.view.administrative',
            'hr_summary.view',
        ],
        'Receiving' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.finalize',
            'documents.view.assigned',
            'documents.view.pending',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'leaves.view',
            'pass_slips.view',
            'pass_slips.create',
            'time_slips.view',
            'time_slips.create',
            'travel_orders.view',
            'tardiness.view',
            'tardiness.create',
        ],
        'Reviewer' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'leaves.view',
            'pass_slips.view',
            'pass_slips.create',
            'time_slips.view',
            'time_slips.create',
            'travel_orders.view',
            'tardiness.view',
            'tardiness.create',
        ],
        'Supervisor' => [
            'documents.create',
            'documents.edit',
            'documents.forward',
            'documents.receive',
            'documents.finalize',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'leaves.view',
            'pass_slips.view',
            'pass_slips.create',
            'time_slips.view',
            'time_slips.create',
            'travel_orders.view',
            'tardiness.view',
            'tardiness.create',
        ],
        'Administrative' => [
            'documents.create',
            'documents.receive',
            'documents.forward',
            'documents.view.assigned',
            'offices.view',
            'offices.edit',
            'municipalities.view',
            'municipalities.edit',
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',
            'divisions.edit',
            'plantillas.edit',
            'records.view',
            'records.create',
            'records.edit',
            'records.delete',
            'leaves.view',
            'leaves.create',
            'leaves.edit',
            'leaves.delete',
            'pass_slips.view',
            'pass_slips.create',
            'pass_slips.edit',
            'pass_slips.delete',
            'time_slips.view',
            'time_slips.create',
            'time_slips.edit',
            'time_slips.delete',
            'certificate_of_appearances.view',
            'certificate_of_appearances.create',
            'certificate_of_appearances.edit',
            'certificate_of_appearances.delete',
            'travel_orders.view',
            'travel_orders.create',
            'travel_orders.edit',
            'travel_orders.delete',
            'tardiness.view',
            'tardiness.create',
            'tardiness.edit',
            'tardiness.delete',
            'hr_summary.view',
            'records.view.administrative',
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
     * Check if user can receive a specific document
     * For documents forwarded to offices/municipalities: Administrator, Developer, Receiving roles or the forwarder can receive
     * For documents forwarded to users: standard receive permission applies
     */
    public static function canReceiveSpecificDocument(User $user, $document): bool
    {
        // Must have receive permission
        if (!self::hasPermission($user, 'documents.receive')) {
            return false;
        }

        // Get latest transaction
        $latestTransaction = $document->latest_transaction ?? 
                            ($document->transactions()->latest('created_at')->first() ?? null);

        // If forwarded to office or municipality
        if ($latestTransaction && 
            ($latestTransaction->forwarded_to_office_id || $latestTransaction->forwarded_to_municipality_id)) {
            // Administrator, Developer, Receiving roles or the user who forwarded it can receive
            return in_array($user->usertype, ['Administrator', 'Developer', 'Receiving']) 
                || $latestTransaction->user_id === $user->id;
        }

        // Otherwise: standard rule - can receive if they didn't forward it
        return $latestTransaction->user_id !== $user->id;
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
     * Check if user can view leaves
     */
    public static function canViewLeaves(User $user): bool
    {
        return self::hasPermission($user, 'leaves.view');
    }

    /**
     * Check if user can create leaves
     */
    public static function canCreateLeave(User $user): bool
    {
        return self::hasPermission($user, 'leaves.create');
    }

    /**
     * Check if user can edit leaves
     */
    public static function canEditLeave(User $user): bool
    {
        return self::hasPermission($user, 'leaves.edit');
    }

    /**
     * Check if user can delete leaves
     */
    public static function canDeleteLeave(User $user): bool
    {
        return self::hasPermission($user, 'leaves.delete');
    }

    /**
     * Check if user can view pass slips
     */
    public static function canViewPassSlips(User $user): bool
    {
        return self::hasPermission($user, 'pass_slips.view');
    }

    /**
     * Check if user can create pass slips
     */
    public static function canCreatePassSlip(User $user): bool
    {
        return self::hasPermission($user, 'pass_slips.create');
    }

    /**
     * Check if user can edit pass slips
     */
    public static function canEditPassSlip(User $user): bool
    {
        return self::hasPermission($user, 'pass_slips.edit');
    }

    /**
     * Check if user can delete pass slips
     */
    public static function canDeletePassSlip(User $user): bool
    {
        return self::hasPermission($user, 'pass_slips.delete');
    }

    /**
     * Check if user can view time slips
     */
    public static function canViewTimeSlips(User $user): bool
    {
        return self::hasPermission($user, 'time_slips.view');
    }

    /**
     * Check if user can create time slips
     */
    public static function canCreateTimeSlip(User $user): bool
    {
        return self::hasPermission($user, 'time_slips.create');
    }

    /**
     * Check if user can edit time slips
     */
    public static function canEditTimeSlip(User $user): bool
    {
        return self::hasPermission($user, 'time_slips.edit');
    }

    /**
     * Check if user can delete time slips
     */
    public static function canDeleteTimeSlip(User $user): bool
    {
        return self::hasPermission($user, 'time_slips.delete');
    }

    /**
     * Check if user can view travel orders
     */
    public static function canViewTravelOrders(User $user): bool
    {
        return self::hasPermission($user, 'travel_orders.view');
    }

    /**
     * Check if user can create travel orders
     */
    public static function canCreateTravelOrder(User $user): bool
    {
        return self::hasPermission($user, 'travel_orders.create');
    }

    /**
     * Check if user can edit travel orders
     */
    public static function canEditTravelOrder(User $user): bool
    {
        return self::hasPermission($user, 'travel_orders.edit');
    }

    /**
     * Check if user can delete travel orders
     */
    public static function canDeleteTravelOrder(User $user): bool
    {
        return self::hasPermission($user, 'travel_orders.delete');
    }

    /**
     * Check if user can view tardiness/undertime records
     */
    public static function canViewTardiness(User $user): bool
    {
        return self::hasPermission($user, 'tardiness.view');
    }

    /**
     * Check if user can create tardiness/undertime records
     */
    public static function canCreateTardiness(User $user): bool
    {
        return self::hasPermission($user, 'tardiness.create');
    }

    /**
     * Check if user can edit tardiness/undertime records
     */
    public static function canEditTardiness(User $user): bool
    {
        return self::hasPermission($user, 'tardiness.edit');
    }

    /**
     * Check if user can delete tardiness/undertime records
     */
    public static function canDeleteTardiness(User $user): bool
    {
        return self::hasPermission($user, 'tardiness.delete');
    }

    /**
     * Check if user can view HR summary
     */
    public static function canViewHRSummary(User $user): bool
    {
        return self::hasPermission($user, 'hr_summary.view');
    }

    /**
     * Check if user can view records
     */
    public static function canViewRecords(User $user): bool
    {
        return self::hasPermission($user, 'records.view');
    }

    /**
     * Check if user can create records
     */
    public static function canCreateRecord(User $user): bool
    {
        return self::hasPermission($user, 'records.create');
    }

    /**
     * Check if user can edit records
     */
    public static function canEditRecord(User $user): bool
    {
        return self::hasPermission($user, 'records.edit');
    }

    /**
     * Check if user can delete records
     */
    public static function canDeleteRecord(User $user): bool
    {
        return self::hasPermission($user, 'records.delete');
    }

    /**
     * Check if user can view records of the "Administrative" record type.
     * Restricted to the Administrative role only.
     */
    public static function canViewAdministrativeRecords(User $user): bool
    {
        return self::hasPermission($user, 'records.view.administrative');
    }

    /**
     * Check if user can view a specific document
     * Developer and Administrator can view all documents
     * Receiving, Reviewer, Supervisor, Administrative can only view documents:
     * - Where the document's user_id matches their ID (document is assigned/under them)
     * - OR forwarded to them directly (as a user, not to office/municipality)
     * - OR (only for Receiving role) forwarded to an office/municipality
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

            // Only Receiving role can view documents forwarded to offices or municipalities
            if ($user->usertype === 'Receiving' && $latestTransaction && 
                ($latestTransaction->forwarded_to_office_id || $latestTransaction->forwarded_to_municipality_id)) {
                return true;
            }

            return false;
        }

        return false;
    }
}
