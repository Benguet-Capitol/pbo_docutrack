<template>
    <!-- Data Table: trashed records from all modules -->
    <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[900px]">
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input
                            type="checkbox"
                            :checked="allPageSelected"
                            @change="$emit('toggle-select-page')"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-44">Module</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Record</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-40">Deleted By</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-40">Deleted At</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="item in items"
                    :key="itemKey(item)"
                    :class="[
                        'transition-colors duration-150',
                        selectedKeys.has(itemKey(item))
                            ? 'bg-emerald-50 dark:bg-emerald-900/20'
                            : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700',
                    ]"
                >
                    <td class="px-4 py-2">
                        <input
                            type="checkbox"
                            :checked="selectedKeys.has(itemKey(item))"
                            @change="$emit('toggle-select', item)"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <span class="inline-flex items-center px-2 py-1 rounded-full font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700">
                            {{ item.module_label }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ item.label }}</p>
                        <p v-if="item.details" class="text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">{{ item.details }}</p>
                        <p v-if="item.warning" class="text-amber-700 dark:text-amber-400 mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ item.warning }}
                        </p>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                        <span v-if="item.deleted_by">{{ item.deleted_by }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ formatDateTime(item.deleted_at) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Restore Button -->
                            <button
                                @click.stop="$emit('restore', item)"
                                class="relative p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-trash-restore"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Restore</span>
                            </button>
                            <!-- Permanent Delete Button -->
                            <button
                                @click.stop="$emit('delete', item)"
                                class="relative p-2 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-times-circle"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Delete Permanently</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { itemKey } from '../Composables/useTrashData';
import type { TrashItem } from '../Composables/useTrashData';

defineProps<{
    items: TrashItem[];
    selectedKeys: Set<string>;
    allPageSelected: boolean;
}>();

defineEmits<{
    'toggle-select': [item: TrashItem];
    'toggle-select-page': [];
    'restore': [item: TrashItem];
    'delete': [item: TrashItem];
}>();

const formatDateTime = (value: string | null) => {
    if (!value) return '-';
    const date = new Date(value.replace(' ', 'T'));
    if (isNaN(date.getTime())) return value;
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};
</script>
