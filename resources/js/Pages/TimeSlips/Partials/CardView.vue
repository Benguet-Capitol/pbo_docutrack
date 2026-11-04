<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="slip in records"
            :key="slip.id"
            accent="blue"
            icon="fas fa-clock"
            :title="slip.control_no"
            :date="formatDate(slip.date)"
        >
            <div :class="CARD_TAGS">
                <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ slip.requesting_employee?.name || 'N/A' }}</span>
            </div>
            <CardField label="Reason" icon="fas fa-comment-dots">
                <p class="line-clamp-3" :title="slip.reason">{{ slip.reason || '—' }}</p>
            </CardField>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-2']">
                <template v-for="period in periods(slip)" :key="period.label">
                    <CardField :label="`${period.label} In`" icon="fas fa-right-to-bracket" stat :value="period.in ? formatTime(period.in) : ''" />
                    <CardField :label="`${period.label} Out`" icon="fas fa-right-from-bracket" stat :value="period.out ? formatTime(period.out) : ''" />
                </template>
            </div>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                <RowIconButton v-if="canEditTimeSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                <RowIconButton v-if="canDeleteTimeSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
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
import type { TimeSlip } from '../Composables/useTimeSlipsData';

defineProps<{
    records: TimeSlip[];
    canEditTimeSlips: boolean;
    canDeleteTimeSlips: boolean;
}>();

defineEmits<{
    edit: [slip: TimeSlip];
    delete: [slip: TimeSlip];
    preview: [slip: TimeSlip];
}>();

const periods = (slip: TimeSlip) => [
    { label: 'Morning', in: slip.time_in_am, out: slip.time_out_am },
    { label: 'Afternoon', in: slip.time_in_pm, out: slip.time_out_pm },
];

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';

    try {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    } catch {
        return timeStr;
    }
};
</script>
