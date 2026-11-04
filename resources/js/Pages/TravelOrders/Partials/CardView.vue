<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="order in records"
            :key="order.id"
            accent="blue"
            icon="fas fa-route"
            :title="order.control_no"
            :date="formatDateForDisplay(order.date)"
        >
            <p :class="CARD_SUBJECT"><i class="fas fa-map-marker-alt mr-1.5 text-red-500"></i>{{ order.going_to || '—' }}</p>
            <div :class="CARD_TAGS">
                <span v-for="emp in order.employees" :key="emp.id" :class="[LIST_TAG, 'rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200']">{{ emp.name }}</span>
            </div>
            <CardField label="Inclusive Dates" icon="far fa-calendar-alt">
                <div v-if="order.inclusive_dates && order.inclusive_dates.length > 0" :class="CARD_TAGS">
                    <span v-for="(date, idx) in order.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDate(date) }}</span>
                </div>
                <template v-else-if="order.from_date && order.to_date">
                    <template v-if="order.from_date === order.to_date">{{ formatDateForDisplay(order.from_date) }}</template>
                    <template v-else>{{ formatDateForDisplay(order.from_date) }} - {{ formatDateForDisplay(order.to_date) }}</template>
                </template>
                <template v-else>—</template>
            </CardField>
            <CardField label="Purpose" icon="fas fa-bullseye">
                <div v-if="order.purpose && order.purpose.length > 0" :class="CARD_TAGS">
                    <span v-for="p in order.purpose" :key="p" class="inline-block break-words rounded bg-emerald-100 px-2 py-1 text-xs text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">{{ p }}</span>
                </div>
                <template v-else>—</template>
            </CardField>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-2']">
                <CardField label="Vehicle" icon="fas fa-car">
                    {{ order.vehicle || '—' }}<template v-if="order.plate_number"> - {{ order.plate_number }}</template>
                </CardField>
                <CardField label="Driver" icon="fas fa-id-card" :value="order.driver" />
            </div>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', order)" />
                <RowIconButton v-if="hasTripTicket(order)" color="emerald" icon="fas fa-car" tooltip="Trip Ticket" @click.stop="$emit('trip-ticket', order)" />
                <RowIconButton v-if="canEditTravelOrders" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', order)" />
                <RowIconButton v-if="canDeleteTravelOrders" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', order)" />
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
import type { TravelOrder } from '../Composables/useTravelOrdersData';
import { hasTripTicket } from '../Composables/tripTicket';

defineProps<{
    records: TravelOrder[];
    canEditTravelOrders: boolean;
    canDeleteTravelOrders: boolean;
}>();

defineEmits<{
    edit: [order: TravelOrder];
    delete: [order: TravelOrder];
    preview: [order: TravelOrder];
    'trip-ticket': [order: TravelOrder];
}>();

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
