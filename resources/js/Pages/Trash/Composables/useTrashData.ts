import { ref, computed, watch } from 'vue';

export interface TrashItem {
    id: number;
    module: string;
    module_label: string;
    label: string;
    details: string;
    warning: string | null;
    deleted_at: string | null;
    deleted_by: string | null;
}

export interface TrashModule {
    key: string;
    label: string;
    count: number;
}

export interface TrashItemRef {
    module: string;
    id: number;
}

/** Unique key for an item across modules */
export const itemKey = (item: TrashItemRef) => `${item.module}:${item.id}`;

/**
 * useTrashData: Composable for loading, filtering and acting on trashed (soft-deleted) records
 */
export function useTrashData() {
    // ============== State ==============

    const items = ref<TrashItem[]>([]);
    const modules = ref<TrashModule[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const searchQuery = ref('');
    const moduleFilter = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(25);
    const selectedKeys = ref<Set<string>>(new Set());

    // ============== Computed ==============

    const totalCount = computed(() => modules.value.reduce((sum, m) => sum + m.count, 0));

    const filteredItems = computed(() => {
        const query = searchQuery.value.trim().toLowerCase();

        return items.value.filter((item) => {
            if (moduleFilter.value && item.module !== moduleFilter.value) return false;
            if (!query) return true;

            return [item.label, item.details, item.module_label, item.deleted_by]
                .some((value) => (value || '').toLowerCase().includes(query));
        });
    });

    const totalPages = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / itemsPerPage.value)));

    const paginatedItems = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        return filteredItems.value.slice(start, start + itemsPerPage.value);
    });

    const selectedItems = computed(() => items.value.filter((item) => selectedKeys.value.has(itemKey(item))));

    const allPageSelected = computed(() =>
        paginatedItems.value.length > 0 && paginatedItems.value.every((item) => selectedKeys.value.has(itemKey(item)))
    );

    watch([searchQuery, moduleFilter, itemsPerPage], () => {
        currentPage.value = 1;
    });

    // ============== API ==============

    const parseError = async (response: Response, fallback: string): Promise<string> => {
        try {
            const data = await response.json();
            return data.errors?.password?.[0] || data.error || data.message || fallback;
        } catch {
            return fallback;
        }
    };

    const fetchModules = async () => {
        const response = await fetch('/api/trash/modules', { headers: { Accept: 'application/json' } });
        if (response.ok) {
            modules.value = await response.json();
        }
    };

    const fetchItems = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/trash', { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error(await parseError(response, 'Failed to load trash'));
            }
            items.value = await response.json();
            await fetchModules();

            // Drop selections for items that no longer exist
            const existing = new Set(items.value.map(itemKey));
            selectedKeys.value = new Set([...selectedKeys.value].filter((key) => existing.has(key)));
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    const postAction = async (url: string, body: Record<string, unknown>, fallback: string) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error(await parseError(response, fallback));
        }

        return response.json();
    };

    const toRefs = (list: TrashItem[]): TrashItemRef[] => list.map(({ module, id }) => ({ module, id }));

    /** Restore items back to their modules */
    const restoreItems = async (list: TrashItem[]) => {
        const result = await postAction('/api/trash/restore', { items: toRefs(list) }, 'Failed to restore');
        await fetchItems();
        return result;
    };

    /** Permanently delete items (requires Administrator/Developer password) */
    const forceDeleteItems = async (list: TrashItem[], password: string) => {
        const result = await postAction('/api/trash/force-delete', { items: toRefs(list), password }, 'Failed to delete permanently');
        await fetchItems();
        return result;
    };

    // ============== Selection ==============

    const toggleSelect = (item: TrashItem) => {
        const next = new Set(selectedKeys.value);
        const key = itemKey(item);
        next.has(key) ? next.delete(key) : next.add(key);
        selectedKeys.value = next;
    };

    const toggleSelectPage = () => {
        const next = new Set(selectedKeys.value);
        const select = !allPageSelected.value;
        paginatedItems.value.forEach((item) => (select ? next.add(itemKey(item)) : next.delete(itemKey(item))));
        selectedKeys.value = next;
    };

    const clearSelection = () => {
        selectedKeys.value = new Set();
    };

    const changePage = (page: number) => {
        if (page >= 1 && page <= totalPages.value) {
            currentPage.value = page;
        }
    };

    fetchItems();

    return {
        items,
        modules,
        loading,
        error,
        searchQuery,
        moduleFilter,
        currentPage,
        itemsPerPage,
        selectedKeys,
        totalCount,
        filteredItems,
        totalPages,
        paginatedItems,
        selectedItems,
        allPageSelected,
        fetchItems,
        restoreItems,
        forceDeleteItems,
        toggleSelect,
        toggleSelectPage,
        clearSelection,
        changePage,
    };
}
