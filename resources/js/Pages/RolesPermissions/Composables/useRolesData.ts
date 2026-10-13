import { ref, computed } from 'vue';

export interface Role {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
    users_count: number;
    permissions: string[];
}

export interface PermissionItem {
    key: string;
    label: string;
    group: string;
}

export interface PermissionGroup {
    group: string;
    permissions: PermissionItem[];
}

export interface RolePayload {
    name: string;
    description: string;
    permissions: string[];
}

/**
 * useRolesData: loads roles and the permission catalog, and saves/deletes roles.
 */
export function useRolesData() {
    const roles = ref<Role[]>([]);
    const permissionGroups = ref<PermissionGroup[]>([]);
    const loading = ref(true);
    const error = ref<string | null>(null);
    const searchQuery = ref('');

    const totalPermissions = computed(() => permissionGroups.value.reduce((sum, g) => sum + g.permissions.length, 0));

    const filteredRoles = computed(() => {
        const query = searchQuery.value.trim().toLowerCase();
        if (!query) return roles.value;
        return roles.value.filter((role) =>
            role.name.toLowerCase().includes(query) || (role.description || '').toLowerCase().includes(query)
        );
    });

    const headers = () => ({
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    });

    /** Turns a failed response into a readable message (validation errors first) */
    const errorMessage = async (response: Response, fallback: string): Promise<string> => {
        const data = await response.json().catch(() => ({}));
        const firstValidationError = data.errors ? (Object.values(data.errors)[0] as string[] | undefined)?.[0] : null;
        return firstValidationError || data.error || data.message || fallback;
    };

    const fetchRoles = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/roles', { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(await errorMessage(response, 'Failed to load roles'));
            const data = await response.json();
            roles.value = data.roles;
            permissionGroups.value = data.permission_groups;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    /** saveRole: creates a role, or updates it when an id is given. Throws with a readable message on failure. */
    const saveRole = async (payload: RolePayload, id?: number): Promise<string> => {
        const response = await fetch(id ? `/api/roles/${id}` : '/api/roles', {
            method: id ? 'PUT' : 'POST',
            headers: headers(),
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error(await errorMessage(response, 'Failed to save role'));
        const data = await response.json();
        await fetchRoles();
        return data.message;
    };

    const deleteRole = async (id: number): Promise<string> => {
        const response = await fetch(`/api/roles/${id}`, { method: 'DELETE', headers: headers() });
        if (!response.ok) throw new Error(await errorMessage(response, 'Failed to delete role'));
        const data = await response.json();
        await fetchRoles();
        return data.message;
    };

    return {
        roles,
        permissionGroups,
        loading,
        error,
        searchQuery,
        totalPermissions,
        filteredRoles,
        fetchRoles,
        saveRole,
        deleteRole,
    };
}
