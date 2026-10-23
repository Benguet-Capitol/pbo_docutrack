<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="LIST_TABLE">
            <colgroup>
                <col class="w-24">
                <col class="w-28">
                <col class="w-32">
                <col class="w-32">
                <col class="w-20">
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
                <tr v-for="(leave, index) in records" :key="leave.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ leave.control_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(leave.date_of_filing) }}</td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ leave.employee?.name || 'N/A' }}</span>
                    </td>
                    <td :class="LIST_CELL">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="entry in (leave.entries?.length ? leave.entries : [leave])"
                                :key="entry.id"
                                :class="[LIST_TAG, 'whitespace-nowrap rounded bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200']"
                            >
                                {{ entry.type_of_leave }}<span v-if="(leave.entries?.length ?? 0) > 1" class="font-normal opacity-80">· {{ entry.number_of_working_days_applied_for }}d</span>
                            </span>
                        </div>
                    </td>
                    <td :class="[LIST_CELL, 'text-center whitespace-nowrap']">
                        <span class="text-gray-700 dark:text-gray-300">{{ leave.number_of_working_days_applied_for }}</span>
                        <span v-if="leave.is_half_day" :class="[LIST_TAG, 'ml-1 rounded bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200']">{{ leave.half_day_period }}</span>
                    </td>
                    <td :class="LIST_CELL">
                        <div v-if="leave.inclusive_dates && leave.inclusive_dates.length > 0" class="flex flex-wrap gap-1">
                            <span v-for="(date, idx) in leave.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDate(date) }}</span>
                        </div>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="View / Print" @click.stop="$emit('preview', leave)" />
                            <RowIconButton v-if="canEditLeaves" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', leave)" />
                            <RowIconButton v-if="canDeleteLeaves" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', leave)" />
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
import type { Leave } from '../Composables/useLeavesData';

defineProps<{
    records: Leave[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditLeaves: boolean;
    canDeleteLeaves: boolean;
}>();

defineEmits<{
    preview: [leave: Leave];
    edit: [leave: Leave];
    delete: [leave: Leave];
    sort: [field: string];
}>();

const COLUMNS: ListColumn[] = [
    { label: 'Control No', sort: 'id' },
    { label: 'Filing Date', sort: 'date_of_filing' },
    { label: 'Employee', sort: 'employee_name' },
    { label: 'Leave Type', sort: 'type_of_leave' },
    { label: 'Days Applied', class: 'text-center' },
    { label: 'Inclusive Dates' },
];

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
