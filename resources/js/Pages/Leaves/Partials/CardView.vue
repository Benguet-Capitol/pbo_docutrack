<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="leave in records"
            :key="leave.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-purple-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ leave.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap" title="Filing Date">
                        <i class="far fa-calendar text-[10px]"></i> {{ formatDateForDisplay(leave.date_of_filing) }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                        {{ leave.employee.name }}
                    </span>
                    <span
                        v-for="entry in (leave.entries?.length ? leave.entries : [leave])"
                        :key="entry.id"
                        class="inline-block px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-semibold"
                    >
                        {{ entry.type_of_leave }}
                    </span>
                </div>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-3 gap-2">
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Days</div>
                    <div class="text-gray-800 dark:text-gray-200 font-semibold">{{ leave.number_of_working_days_applied_for }}</div>
                    <span v-if="leave.is_half_day" class="mt-1 inline-block px-1.5 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded text-[10px] font-medium">
                        {{ leave.half_day_period }}
                    </span>
                </div>
                <div class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Inclusive Dates</div>
                    <div v-if="leave.inclusive_dates && leave.inclusive_dates.length > 0" class="mt-0.5 flex flex-wrap gap-1">
                        <span v-for="(date, idx) in leave.inclusive_dates" :key="idx" class="inline-block px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-semibold">
                            {{ formatInclusiveDate(date) }}
                        </span>
                    </div>
                    <div v-else class="text-gray-400 dark:text-gray-500">—</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View / Print" @click.stop="$emit('preview', leave)" />
                <RowIconButton v-if="canEditLeaves" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', leave)" />
                <RowIconButton v-if="canDeleteLeaves" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', leave)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
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
