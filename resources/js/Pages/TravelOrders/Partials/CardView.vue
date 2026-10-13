<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="order in records"
            :key="order.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ order.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <i class="far fa-calendar text-[10px]"></i> {{ formatDateForDisplay(order.date) }}
                    </span>
                </div>
                <div class="mt-2 flex items-start gap-1.5 text-gray-800 dark:text-gray-200">
                    <i class="fas fa-map-marker-alt text-red-500 mt-0.5"></i>
                    <span class="font-semibold break-words">{{ order.going_to || '—' }}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                    <span v-for="emp in order.employees" :key="emp.id" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-semibold">
                        {{ emp.name }}
                    </span>
                </div>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Inclusive Dates</div>
                    <div v-if="order.inclusive_dates && order.inclusive_dates.length > 0" class="mt-0.5 flex flex-wrap gap-1">
                        <span v-for="(date, idx) in order.inclusive_dates" :key="idx" class="inline-block px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-semibold">
                            {{ formatInclusiveDate(date) }}
                        </span>
                    </div>
                    <div v-else class="text-gray-800 dark:text-gray-200">
                        <template v-if="order.from_date && order.to_date">
                            <template v-if="order.from_date === order.to_date">{{ formatDateForDisplay(order.from_date) }}</template>
                            <template v-else>{{ formatDateForDisplay(order.from_date) }} - {{ formatDateForDisplay(order.to_date) }}</template>
                        </template>
                        <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                    </div>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Purpose</div>
                    <div v-if="order.purpose && order.purpose.length > 0" class="mt-0.5 flex flex-wrap gap-1">
                        <span v-for="p in order.purpose" :key="p" class="inline-block px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs">
                            {{ p }}
                        </span>
                    </div>
                    <div v-else class="text-gray-400 dark:text-gray-500">—</div>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Vehicle</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words">{{ order.vehicle || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', order)" />
                <RowIconButton v-if="canEditTravelOrders" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', order)" />
                <RowIconButton v-if="canDeleteTravelOrders" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', order)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import type { TravelOrder } from '../Composables/useTravelOrdersData';

defineProps<{
    records: TravelOrder[];
    canEditTravelOrders: boolean;
    canDeleteTravelOrders: boolean;
}>();

defineEmits<{
    edit: [order: TravelOrder];
    delete: [order: TravelOrder];
    preview: [order: TravelOrder];
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
