<template>
    <!-- Data Table: trashed records from all modules (same list style as Documents) -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'min-w-[900px]']">
            <thead>
                <tr>
                    <th :class="[LIST_HEAD, 'w-10']">
                        <input
                            type="checkbox"
                            :checked="allPageSelected"
                            @change="$emit('toggle-select-page')"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </th>
                    <th :class="[LIST_HEAD, 'w-44']">Module</th>
                    <th :class="LIST_HEAD">Record</th>
                    <th :class="[LIST_HEAD, 'w-40']">Deleted By</th>
                    <th :class="[LIST_HEAD, 'w-40']">Deleted At</th>
                    <th :class="[LIST_HEAD, ACTIONS_HEAD, 'w-28']">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(item, index) in items"
                    :key="itemKey(item)"
                    :class="selectedKeys.has(itemKey(item))
                        ? 'transition-colors [&>td]:border-t [&>td]:border-t-gray-200 dark:[&>td]:border-t-gray-700 bg-emerald-50 dark:bg-[#173a33]'
                        : listRowClass(index)"
                >
                    <td :class="LIST_CELL">
                        <input
                            type="checkbox"
                            :checked="selectedKeys.has(itemKey(item))"
                            @change="$emit('toggle-select', item)"
                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, 'rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200']">
                            {{ item.module_label }}
                        </span>
                    </td>
                    <td :class="LIST_CELL">
                        <p :class="LIST_TITLE">{{ item.label }}</p>
                        <p v-if="item.details" class="text-gray-600 dark:text-gray-400 mt-0.5 break-words">{{ item.details }}</p>
                        <p v-if="item.warning" class="text-amber-700 dark:text-amber-400 mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ item.warning }}
                        </p>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ item.deleted_by || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400 whitespace-nowrap']">{{ formatDateTime(item.deleted_at) }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="emerald" icon="fas fa-trash-restore" tooltip="Restore" @click.stop="$emit('restore', item)" />
                            <RowIconButton color="red" icon="fas fa-times-circle" tooltip="Delete Permanently" @click.stop="$emit('delete', item)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_TABLE, LIST_TAG, LIST_TITLE, listRowClass } from '@/utils/listTable';
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
    if (!value) return '—';
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
