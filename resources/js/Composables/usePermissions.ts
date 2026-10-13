import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * usePermissions: the signed-in user's permissions, shared by HandleInertiaRequests from the
 * Roles & Permissions module. UI-only: the server checks the same permissions on every write.
 */
export function usePermissions() {
    const page = usePage();
    const permissions = computed<string[]>(() => ((page.props.auth as any)?.permissions as string[]) || []);

    /** can: true when the user's role has the permission */
    const can = (permission: string): boolean => permissions.value.includes(permission);

    return { permissions, can };
}
