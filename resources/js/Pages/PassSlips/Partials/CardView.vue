<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="slip in records"
            :key="slip.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ slip.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <i class="far fa-calendar text-[10px]"></i> {{ formatDate(slip.date) }}
                    </span>
                </div>
                <div class="mt-2 flex gap-1 flex-wrap">
                    <span v-for="emp in slip.employees" :key="emp.id" class="inline-block px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-semibold">
                        {{ emp.name }}
                    </span>
                </div>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Inclusive Dates</div>
                    <div v-if="slip.inclusive_dates && slip.inclusive_dates.length > 0" class="mt-0.5 flex flex-wrap gap-1">
                        <span v-for="(date, idx) in slip.inclusive_dates" :key="idx" class="inline-block px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-semibold">
                            {{ formatInclusiveDate(date) }}
                        </span>
                    </div>
                    <div v-else class="text-gray-400 dark:text-gray-500">—</div>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Requested Time</div>
                    <div class="text-gray-800 dark:text-gray-200">{{ formatTime(slip.requested_time) || '—' }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Expected Return</div>
                    <div class="text-gray-800 dark:text-gray-200">{{ formatTime(slip.expected_return_time) || '—' }}</div>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Location</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words">{{ slip.location || '—' }}</div>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Purpose</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="slip.purpose">{{ slip.purpose || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                <RowIconButton v-if="canEditPassSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                <RowIconButton v-if="canDeletePassSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
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
