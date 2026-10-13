<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="slip in records"
            :key="slip.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ slip.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <i class="far fa-calendar text-[10px]"></i> {{ formatDate(slip.date) }}
                    </span>
                </div>
                <span class="mt-2 inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                    {{ slip.requesting_employee?.name || 'N/A' }}
                </span>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                <div v-for="period in periods(slip)" :key="period.label" class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ period.label }}</div>
                    <div class="flex items-center gap-1 text-gray-800 dark:text-gray-200">
                        <span class="text-gray-500 dark:text-gray-400">In</span> {{ period.in ? formatTime(period.in) : '—' }}
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <span class="text-gray-500 dark:text-gray-400">Out</span> {{ period.out ? formatTime(period.out) : '—' }}
                    </div>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="slip.reason">{{ slip.reason || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                <RowIconButton v-if="canEditTimeSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                <RowIconButton v-if="canDeleteTimeSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
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
