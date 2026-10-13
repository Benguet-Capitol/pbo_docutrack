<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="log in logs"
            :key="log.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <!-- What happened -->
            <div class="px-4 pt-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-full font-medium whitespace-nowrap', actionMeta(log.action).classes]">
                        <i :class="actionMeta(log.action).icon"></i>
                        {{ actionMeta(log.action).label }}
                    </span>
                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">{{ log.module }}</span>
                </div>
                <p class="mt-2 text-gray-800 dark:text-gray-200 break-words line-clamp-4" :title="log.description">{{ log.description }}</p>
            </div>

            <div class="flex-1"></div>
            <!-- Who and when -->
            <div class="mt-3 flex items-center justify-between gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <div class="min-w-0 leading-tight">
                    <p class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ log.user_name || 'System' }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                        <template v-if="log.usertype">{{ log.usertype }} · </template>{{ formatDateTime(log.created_at) }}
                    </p>
                </div>
                <RowIconButton color="blue" icon="fas fa-eye" tooltip="View Details" class="flex-shrink-0" @click="$emit('view', log)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { actionMeta } from '../Composables/useActivityLogData';
import type { ActivityLog } from '../Composables/useActivityLogData';

defineProps<{
    logs: ActivityLog[];
}>();

defineEmits<{
    'view': [log: ActivityLog];
}>();

const formatDateTime = (value: string) => {
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
