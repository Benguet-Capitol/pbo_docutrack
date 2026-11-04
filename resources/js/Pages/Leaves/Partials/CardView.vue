<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="leave in records"
            :key="leave.id"
            accent="purple"
            icon="fas fa-calendar-check"
            :title="leave.control_no"
            :date="formatDateForDisplay(leave.date_of_filing)"
            date-label="Filing Date"
        >
            <div :class="CARD_TAGS">
                <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ leave.employee.name }}</span>
            </div>
            <div :class="CARD_TAGS">
                <span
                    v-for="entry in (leave.entries?.length ? leave.entries : [leave])"
                    :key="entry.id"
                    :class="[LIST_TAG, 'rounded bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200']"
                >{{ entry.type_of_leave }}</span>
            </div>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-3']">
                <CardField label="Days" icon="fas fa-hashtag" stat>
                    {{ leave.number_of_working_days_applied_for }}
                    <span v-if="leave.is_half_day" class="ml-1 inline-block rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">{{ leave.half_day_period }}</span>
                </CardField>
                <CardField class="col-span-2" label="Inclusive Dates" icon="far fa-calendar-alt">
                    <div v-if="leave.inclusive_dates && leave.inclusive_dates.length > 0" :class="CARD_TAGS">
                        <span v-for="(date, idx) in leave.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDate(date) }}</span>
                    </div>
                    <template v-else>—</template>
                </CardField>
            </div>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View / Print" @click.stop="$emit('preview', leave)" />
                <RowIconButton v-if="canEditLeaves" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', leave)" />
                <RowIconButton v-if="canDeleteLeaves" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', leave)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_STATS, CARD_TAGS } from '@/utils/cardView';
import { DATE_TAG, EMPLOYEE_TAG, LIST_TAG } from '@/utils/listTable';
import type { Leave } from '../Composables/useLeavesData';

defineProps<{
    records: Leave[];
    canEditLeaves: boolean;
    canDeleteLeaves: boolean;
}>();

defineEmits<{
    preview: [leave: Leave];
    edit: [leave: Leave];
    delete: [leave: Leave];
}>();

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDate = (entry: string): string => {
    if (!entry) return '';

    const formatDateWithoutYear = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    if (entry.includes(' - ')) {
        const [startDate, endDate] = entry.split(' - ');
        return `${formatDateWithoutYear(startDate.trim())} - ${formatDateWithoutYear(endDate.trim())}`;
    } else {
        return formatDateWithoutYear(entry);
    }
};
</script>
