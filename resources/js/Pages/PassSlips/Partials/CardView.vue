<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="slip in records"
            :key="slip.id"
            accent="emerald"
            icon="fas fa-door-open"
            :title="slip.control_no"
            :date="formatDate(slip.date)"
        >
            <p :class="CARD_SUBJECT"><i class="fas fa-map-marker-alt mr-1.5 text-red-500"></i>{{ slip.location || '—' }}</p>
            <div :class="CARD_TAGS">
                <span v-for="emp in slip.employees" :key="emp.id" :class="[LIST_TAG, 'rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300']">{{ emp.name }}</span>
            </div>
            <CardField label="Inclusive Dates" icon="far fa-calendar-alt">
                <div v-if="slip.inclusive_dates && slip.inclusive_dates.length > 0" :class="CARD_TAGS">
                    <span v-for="(date, idx) in slip.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDate(date) }}</span>
                </div>
                <template v-else>—</template>
            </CardField>
            <CardField label="Purpose" icon="fas fa-bullseye">
                <p class="line-clamp-3" :title="slip.purpose">{{ slip.purpose || '—' }}</p>
            </CardField>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-2']">
                <CardField label="Requested Time" icon="far fa-clock" stat :value="formatTime(slip.requested_time)" />
                <CardField label="Expected Return" icon="fas fa-rotate-left" stat :value="formatTime(slip.expected_return_time)" />
            </div>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                <RowIconButton v-if="canEditPassSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                <RowIconButton v-if="canDeletePassSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_STATS, CARD_SUBJECT, CARD_TAGS } from '@/utils/cardView';
import { DATE_TAG, LIST_TAG } from '@/utils/listTable';
import type { PassSlip } from '../Composables/usePassSlipsData';

defineProps<{
    records: PassSlip[];
    canEditPassSlips: boolean;
    canDeletePassSlips: boolean;
}>();

defineEmits<{
    edit: [slip: PassSlip];
    delete: [slip: PassSlip];
    preview: [slip: PassSlip];
}>();

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';

    // If it doesn't contain a colon, it's not a time value (could be ASAP, NWD, NOM, Memo, etc.)
    if (!timeStr.includes(':')) {
        return timeStr;
    }

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

const formatInclusiveDate = (dateEntry: string): string => {
    if (!dateEntry) return '';
    if (dateEntry.includes(' - ')) {
        const [startStr, endStr] = dateEntry.split(' - ');
        const start = new Date(startStr.trim()).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const end = new Date(endStr.trim()).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return `${start} - ${end}`;
    } else {
        return new Date(dateEntry.trim()).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
};
</script>
