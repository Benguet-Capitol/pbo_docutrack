<template>
    <!-- Data Table: activity log entries -->
    <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[900px]">
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-40">Date &amp; Time</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-44">User</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-40">Module</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 w-40">Action</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Description</th>
                    <th class="px-4 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center w-20">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="log in logs"
                    :key="log.id"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                >
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ formatDateTime(log.created_at) }}
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ log.user_name || 'System' }}</p>
                        <p v-if="log.usertype" class="text-gray-500 dark:text-gray-400">{{ log.usertype }}</p>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ log.module }}</td>
                    <td class="px-4 py-2 text-xs">
                        <span :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-full font-medium whitespace-nowrap', actionMeta(log.action).classes]">
                            <i :class="actionMeta(log.action).icon"></i>
                            {{ actionMeta(log.action).label }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-800 dark:text-gray-200">
                        {{ log.description }}
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <button
                            @click="$emit('view', log)"
                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                        >
                            <i class="fas fa-eye"></i>
                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">View Details</span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
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
        second: '2-digit',
    });
};
</script>
