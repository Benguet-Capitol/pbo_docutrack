<template>
    <!-- Data Table: activity log entries (same list style as Documents) -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'min-w-[900px]']">
            <thead>
                <tr>
                    <th :class="[LIST_HEAD, 'w-40']">Date &amp; Time</th>
                    <th :class="[LIST_HEAD, 'w-44']">User</th>
                    <th :class="[LIST_HEAD, 'w-40']">Module</th>
                    <th :class="[LIST_HEAD, 'w-40']">Action</th>
                    <th :class="LIST_HEAD">Description</th>
                    <th :class="[LIST_HEAD, ACTIONS_HEAD, 'w-20']">Details</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(log, index) in logs" :key="log.id" :class="listRowClass(index)">
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400 whitespace-nowrap']">{{ formatDateTime(log.created_at) }}</td>
                    <td :class="LIST_CELL">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ log.user_name || 'System' }}</p>
                        <p v-if="log.usertype" :class="LIST_META">{{ log.usertype }}</p>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ log.module }}</td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, 'whitespace-nowrap rounded-full font-medium', actionMeta(log.action).classes]">
                            <i :class="actionMeta(log.action).icon"></i>
                            {{ actionMeta(log.action).label }}
                        </span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']">{{ log.description }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <RowIconButton color="blue" icon="fas fa-eye" tooltip="View Details" @click="$emit('view', log)" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_META, LIST_TABLE, LIST_TAG, listRowClass } from '@/utils/listTable';
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
