<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-20">
                <col class="w-20">
                <col class="w-24">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-40">
                <col class="w-20">
            </colgroup>
            <thead>
                <tr>
                    <th v-for="column in COLUMNS" :key="column.label" :class="[LIST_HEAD, column.class]">
                        <button v-if="column.sort" @click="$emit('sort', column.sort)" :class="LIST_SORT">
                            {{ column.label }}
                            <span v-if="sortBy === column.sort">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                        <template v-else>{{ column.label }}</template>
                    </th>
                    <th :class="[LIST_HEAD, ACTIONS_HEAD]">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(slip, index) in records" :key="slip.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ slip.control_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(slip.date) }}</td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ slip.requesting_employee?.name || 'N/A' }}</span>
                    </td>
                    <td v-for="field in TIME_FIELDS" :key="field" :class="[LIST_CELL, 'text-center whitespace-nowrap']">
                        <span v-if="slip[field]" :class="[LIST_TAG, 'whitespace-nowrap rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300']">{{ formatTime(slip[field]) }}</span>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']" style="white-space: pre-wrap;">{{ slip.reason || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                            <RowIconButton v-if="canEditTimeSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                            <RowIconButton v-if="canDeleteTimeSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import {
    ACTIONS_CELL, ACTIONS_HEAD, EMPLOYEE_TAG, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TAG, LIST_TITLE,
    listDate, listRowClass, type ListColumn,
} from '@/utils/listTable';
import type { TimeSlip } from '../Composables/useTimeSlipsData';

defineProps<{
    records: TimeSlip[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditTimeSlips: boolean;
    canDeleteTimeSlips: boolean;
}>();

defineEmits<{
    edit: [slip: TimeSlip];
    delete: [slip: TimeSlip];
    preview: [slip: TimeSlip];
    sort: [field: string];
}>();

const COLUMNS: ListColumn[] = [
    { label: 'Control No', sort: 'control_no' },
    { label: 'Date', sort: 'date' },
    { label: 'Employee' },
    { label: 'In AM', class: 'text-center' },
    { label: 'Out AM', class: 'text-center' },
    { label: 'In PM', class: 'text-center' },
    { label: 'Out PM', class: 'text-center' },
    { label: 'Reason', sort: 'reason' },
];

const TIME_FIELDS = ['time_in_am', 'time_out_am', 'time_in_pm', 'time_out_pm'] as const;

const formatTime = (timeStr: string | null | undefined): string => {
    if (!timeStr) return '—';

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
