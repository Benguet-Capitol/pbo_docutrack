<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="record in records"
            :key="record.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
            :class="record.type === 'Tardiness' ? 'border-t-orange-500' : 'border-t-red-500'"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ record.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap" title="Date Filed">
                        <i class="far fa-calendar text-[10px]"></i> {{ formatDate(record.date_filed) }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                        {{ record.employee?.name || 'N/A' }}
                    </span>
                    <span :class="[
                        'px-2 py-1 rounded-full text-xs font-medium',
                        record.type === 'Tardiness'
                            ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'
                            : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                    ]">
                        {{ record.type }}
                    </span>
                </div>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-3 gap-2">
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</div>
                    <div class="text-gray-800 dark:text-gray-200">{{ formatDate(record.requested_date) || '—' }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Time</div>
                    <div class="text-gray-800 dark:text-gray-200">{{ formatTime(record.requested_time) || '—' }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Return</div>
                    <div class="text-gray-800 dark:text-gray-200">{{ record.return_time ? formatTime(record.return_time) : 'N/A' }}</div>
                </div>
                <div class="col-span-3 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="record.reason">{{ record.reason || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', record)" />
                <RowIconButton v-if="canEditTardiness" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                <RowIconButton v-if="canDeleteTardiness" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
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
