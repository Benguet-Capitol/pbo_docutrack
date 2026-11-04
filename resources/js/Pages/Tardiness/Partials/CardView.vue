<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="record in records"
            :key="record.id"
            :accent="record.type === 'Tardiness' ? 'orange' : 'red'"
            icon="fas fa-hourglass-half"
            :title="record.control_no"
            :date="formatDate(record.date_filed)"
            date-label="Date Filed"
        >
            <template #aside>
                <span :class="[
                    LIST_TAG, 'rounded',
                    record.type === 'Tardiness'
                        ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'
                        : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                ]">{{ record.type }}</span>
            </template>
            <div :class="CARD_TAGS">
                <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ record.employee?.name || 'N/A' }}</span>
            </div>
            <CardField label="Reason" icon="fas fa-comment-dots">
                <p class="line-clamp-3" :title="record.reason">{{ record.reason || '—' }}</p>
            </CardField>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-3']">
                <CardField label="Date" icon="far fa-calendar" :value="formatDate(record.requested_date)" />
                <CardField label="Time" icon="far fa-clock" stat :value="formatTime(record.requested_time)" />
                <CardField label="Return" icon="fas fa-rotate-left" stat :value="record.return_time ? formatTime(record.return_time) : 'N/A'" />
            </div>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', record)" />
                <RowIconButton v-if="canEditTardiness" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                <RowIconButton v-if="canDeleteTardiness" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_STATS, CARD_TAGS } from '@/utils/cardView';
import { EMPLOYEE_TAG, LIST_TAG } from '@/utils/listTable';
import type { TardinessRecord } from '../Composables/useTardinessData';

defineProps<{
    records: TardinessRecord[];
    canEditTardiness: boolean;
    canDeleteTardiness: boolean;
}>();

defineEmits<{
    edit: [record: TardinessRecord];
    delete: [record: TardinessRecord];
    preview: [record: TardinessRecord];
}>();

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};
</script>
