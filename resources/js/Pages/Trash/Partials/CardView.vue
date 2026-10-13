<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div>
        <!-- Stands in for the table's header checkbox -->
        <label class="inline-flex items-center gap-2 mb-3 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none">
            <input
                type="checkbox"
                :checked="allPageSelected"
                @change="$emit('toggle-select-page')"
                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
            />
            Select all on this page
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="item in items"
                :key="itemKey(item)"
                :class="[
                    'border border-t-4 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col',
                    selectedKeys.has(itemKey(item))
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 border-t-emerald-500'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 border-t-gray-400',
                ]"
            >
                <div class="px-4 pt-3 flex items-start gap-3">
                    <input
                        type="checkbox"
                        :checked="selectedKeys.has(itemKey(item))"
                        @change="$emit('toggle-select', item)"
                        class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                    />
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700">
                            {{ item.module_label }}
                        </span>
                        <p class="mt-2 font-semibold text-gray-900 dark:text-gray-100 break-words">{{ item.label }}</p>
                        <p v-if="item.details" class="text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">{{ item.details }}</p>
                        <p v-if="item.warning" class="text-amber-700 dark:text-amber-400 mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ item.warning }}
                        </p>
                    </div>
                </div>

                <div class="flex-1"></div>
                <div class="mt-3 flex items-center justify-between gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                    <div class="min-w-0 leading-tight">
                        <p class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ item.deleted_by || '-' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ formatDateTime(item.deleted_at) }}</p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <RowIconButton color="emerald" icon="fas fa-trash-restore" tooltip="Restore" @click.stop="$emit('restore', item)" />
                        <RowIconButton color="red" icon="fas fa-times-circle" tooltip="Delete Permanently" @click.stop="$emit('delete', item)" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
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
