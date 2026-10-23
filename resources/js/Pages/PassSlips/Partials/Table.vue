<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-20">
                <col class="w-16">
                <col class="w-28">
                <col class="w-24">
                <col class="w-20">
                <col class="w-40">
                <col class="w-40">
                <col class="w-20">
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
                        <div class="flex flex-wrap gap-1">
                            <span v-for="emp in slip.employees" :key="emp.id" :class="[LIST_TAG, 'rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300']">{{ emp.name }}</span>
                        </div>
                    </td>
                    <td :class="LIST_CELL">
                        <div v-if="slip.inclusive_dates && slip.inclusive_dates.length > 0" class="flex flex-wrap gap-1">
                            <span v-for="(date, idx) in slip.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDate(date) }}</span>
                        </div>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap font-medium text-gray-800 dark:text-gray-200']">{{ formatTime(slip.requested_time) }}</td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']" style="white-space: pre-wrap;">{{ slip.purpose || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300 break-words']">{{ slip.location || '—' }}</td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap font-medium text-gray-800 dark:text-gray-200']">{{ formatTime(slip.expected_return_time) }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', slip)" />
                            <RowIconButton v-if="canEditPassSlips" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', slip)" />
                            <RowIconButton v-if="canDeletePassSlips" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', slip)" />
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
    ACTIONS_CELL, ACTIONS_HEAD, DATE_TAG, EMPLOYEE_TAG, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TAG, LIST_TITLE,
    listDate, listRowClass, type ListColumn,
} from '@/utils/listTable';
import type { PassSlip } from '../Composables/usePassSlipsData';

defineProps<{
    records: PassSlip[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditPassSlips: boolean;
    canDeletePassSlips: boolean;
}>();

defineEmits<{
    edit: [slip: PassSlip];
    delete: [slip: PassSlip];
    preview: [slip: PassSlip];
    sort: [field: string];
}>();

const COLUMNS: ListColumn[] = [
    { label: 'Control No', sort: 'control_no' },
    { label: 'Date', sort: 'date' },
    { label: 'Employees' },
    { label: 'Inclusive Dates' },
    { label: 'Leave Time', sort: 'requested_time' },
    { label: 'Purpose', sort: 'purpose' },
    { label: 'Location', sort: 'location' },
    { label: 'Return Time' },
];

const formatTime = (timeStr: string | null | undefined): string => {
    if (!timeStr) return '—';

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
        const startDate = new Date(startStr.trim());
        const endDate = new Date(endStr.trim());
        const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return `${start} - ${end}`;
    } else {
        const date = new Date(dateEntry.trim());
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
};
</script>
