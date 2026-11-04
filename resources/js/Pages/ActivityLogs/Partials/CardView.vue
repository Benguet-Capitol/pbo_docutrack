<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="log in logs"
            :key="log.id"
            :icon="actionMeta(log.action).icon"
            :icon-class="actionMeta(log.action).classes"
            :title="actionMeta(log.action).label"
        >
            <template #subtitle>{{ log.module }}</template>
            <p class="break-words text-gray-800 line-clamp-4 dark:text-gray-200" :title="log.description">{{ log.description }}</p>

            <template #meta>
                <p class="truncate font-semibold text-gray-800 dark:text-gray-200">{{ log.user_name || 'System' }}</p>
                <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">
                    <template v-if="log.usertype">{{ log.usertype }} · </template>{{ formatDateTime(log.created_at) }}
                </p>
            </template>
            <template #actions>
                <RowIconButton color="blue" icon="fas fa-eye" tooltip="View Details" @click="$emit('view', log)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID } from '@/utils/cardView';
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
