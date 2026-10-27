import { ref, watch } from 'vue';

export interface ActivityLog {
    id: number;
    user_id: number | null;
    user_name: string | null;
    usertype: string | null;
    module: string;
    action: string;
    subject_type: string | null;
    subject_id: number | null;
    subject_label: string | null;
    description: string;
    properties: {
        attributes?: Record<string, unknown>;
        old?: Record<string, unknown>;
        new?: Record<string, unknown>;
    } | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

export interface ActivityLogFilters {
    search: string;
    module: string;
    action: string;
    user_id: string;
    date_from: string;
    date_to: string;
}

export interface FilterOptions {
    modules: string[];
    actions: string[];
    users: { user_id: number; user_name: string }[];
}

/** Display labels and badge colors for each action */
export const ACTION_META: Record<string, { label: string; classes: string; icon: string }> = {
    created: { label: 'Created', icon: 'fas fa-plus', classes: 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/40' },
    updated: { label: 'Updated', icon: 'fas fa-pencil-alt', classes: 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/40' },
    deleted: { label: 'Moved to Trash', icon: 'fas fa-trash-alt', classes: 'text-orange-700 bg-orange-100 dark:text-orange-300 dark:bg-orange-900/40' },
    restored: { label: 'Restored', icon: 'fas fa-trash-restore', classes: 'text-teal-700 bg-teal-100 dark:text-teal-300 dark:bg-teal-900/40' },
    force_deleted: { label: 'Permanently Deleted', icon: 'fas fa-times-circle', classes: 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40' },
    routed: { label: 'Routed', icon: 'fas fa-route', classes: 'text-purple-700 bg-purple-100 dark:text-purple-300 dark:bg-purple-900/40' },
    login: { label: 'Login', icon: 'fas fa-sign-in-alt', classes: 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-700' },
    logout: { label: 'Logout', icon: 'fas fa-sign-out-alt', classes: 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-700' },
    login_failed: { label: 'Failed Sign-in', icon: 'fas fa-user-times', classes: 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/40' },
    login_locked: { label: 'Locked Out', icon: 'fas fa-user-lock', classes: 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40' },
    api_access: { label: 'API Read', icon: 'fas fa-plug', classes: 'text-sky-700 bg-sky-100 dark:text-sky-300 dark:bg-sky-900/40' },
    api_denied: { label: 'API Refused', icon: 'fas fa-ban', classes: 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40' },
};

export const actionMeta = (action: string) =>
    ACTION_META[action] ?? { label: action, icon: 'fas fa-circle', classes: 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-700' };

const emptyFilters = (): ActivityLogFilters => ({
    search: '',
    module: '',
    action: '',
    user_id: '',
    date_from: '',
    date_to: '',
});

/**
 * useActivityLogData: Composable for loading the server-paginated activity log
 */
export function useActivityLogData() {
    // ============== State ==============

    const logs = ref<ActivityLog[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const filters = ref<ActivityLogFilters>(emptyFilters());
    const filterOptions = ref<FilterOptions>({ modules: [], actions: [], users: [] });
    const currentPage = ref(1);
    const itemsPerPage = ref(25);
    const totalPages = ref(1);
    const totalCount = ref(0);

    let requestId = 0;

    // ============== API ==============

    const fetchLogs = async () => {
        const thisRequest = ++requestId;
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams({
                page: String(currentPage.value),
                per_page: String(itemsPerPage.value),
            });
            Object.entries(filters.value).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            const response = await fetch(`/api/activity-logs?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || data.message || 'Failed to load activity logs');
            }

            const data = await response.json();

            // Ignore stale responses when filters change quickly
            if (thisRequest !== requestId) return;

            logs.value = data.data;
            totalPages.value = Math.max(1, data.last_page);
            totalCount.value = data.total;
        } catch (e) {
            if (thisRequest === requestId) {
                error.value = e instanceof Error ? e.message : 'An error occurred';
            }
        } finally {
            if (thisRequest === requestId) {
                loading.value = false;
            }
        }
    };

    const fetchFilterOptions = async () => {
        try {
            const response = await fetch('/api/activity-logs/filters', { headers: { Accept: 'application/json' } });
            if (response.ok) {
                filterOptions.value = await response.json();
            }
        } catch {
            // Filters are optional; the list still works without them
        }
    };

    const refresh = () => {
        fetchFilterOptions();
        fetchLogs();
    };

    // ============== Watchers ==============

    let searchTimer: ReturnType<typeof setTimeout> | undefined;

    watch(
        () => filters.value.search,
        () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentPage.value = 1;
                fetchLogs();
            }, 350);
        }
    );

    watch(
        () => [filters.value.module, filters.value.action, filters.value.user_id, filters.value.date_from, filters.value.date_to, itemsPerPage.value],
        () => {
            currentPage.value = 1;
            fetchLogs();
        }
    );

    const changePage = (page: number) => {
        if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
            currentPage.value = page;
            fetchLogs();
        }
    };

    const resetFilters = () => {
        filters.value = emptyFilters();
    };

    refresh();

    return {
        logs,
        loading,
        error,
        filters,
        filterOptions,
        currentPage,
        itemsPerPage,
        totalPages,
        totalCount,
        fetchLogs,
        refresh,
        changePage,
        resetFilters,
    };
}
