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

        <div :class="CARD_GRID">
            <RecordCard
                v-for="item in items"
                :key="itemKey(item)"
                :accent="selectedKeys.has(itemKey(item)) ? 'emerald' : 'gray'"
                icon="fas fa-trash-alt"
                :title="item.module_label"
                :selected="selectedKeys.has(itemKey(item))"
            >
                <template #leading>
                    <input
                        type="checkbox"
                        :checked="selectedKeys.has(itemKey(item))"
                        @change="$emit('toggle-select', item)"
                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                    />
                </template>
                <p :class="CARD_SUBJECT">{{ item.label }}</p>
                <p v-if="item.details" class="text-gray-600 line-clamp-2 dark:text-gray-400">{{ item.details }}</p>
                <p v-if="item.warning" class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ item.warning }}
                </p>

                <template #meta>
                    <p class="truncate font-semibold text-gray-800 dark:text-gray-200">{{ item.deleted_by || '-' }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ formatDateTime(item.deleted_at) }}</p>
                </template>
                <template #actions>
                    <RowIconButton color="emerald" icon="fas fa-trash-restore" tooltip="Restore" @click.stop="$emit('restore', item)" />
                    <RowIconButton color="red" icon="fas fa-times-circle" tooltip="Delete Permanently" @click.stop="$emit('delete', item)" />
                </template>
            </RecordCard>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_SUBJECT } from '@/utils/cardView';
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
