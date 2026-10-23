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
                <col class="w-24">
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
                <tr v-for="(record, index) in records" :key="record.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ record.control_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(record.date_filed) }}</td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ record.employee?.name || 'N/A' }}</span>
                    </td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap']">
                        <span
                            :class="[LIST_TAG, record.type === 'Tardiness'
                                ? 'rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'
                                : 'rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300']"
                        >{{ record.type }}</span>
                    </td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap text-gray-700 dark:text-gray-300']">{{ listDate(record.requested_date) }}</td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap font-medium text-gray-800 dark:text-gray-200']">{{ formatTime(record.requested_time) }}</td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap font-medium text-gray-800 dark:text-gray-200']">{{ formatTime(record.return_time) }}</td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']" style="white-space: pre-wrap;">{{ record.reason || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', record)" />
                            <RowIconButton v-if="canEditTardiness" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                            <RowIconButton v-if="canDeleteTardiness" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
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
import type { TardinessRecord } from '../Composables/useTardinessData';

defineProps<{
    records: TardinessRecord[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditTardiness: boolean;
    canDeleteTardiness: boolean;
}>();

defineEmits<{
    edit: [record: TardinessRecord];
    delete: [record: TardinessRecord];
    preview: [record: TardinessRecord];
    sort: [field: string];
}>();

const COLUMNS: ListColumn[] = [
    { label: 'Control No', sort: 'control_no' },
    { label: 'Date Filed' },
    { label: 'Employee' },
    { label: 'Type', sort: 'type' },
    { label: 'Requested Date', sort: 'requested_date' },
    { label: 'Requested Time' },
    { label: 'Return Time' },
    { label: 'Reason', sort: 'reason' },
];

const formatTime = (timeStr: string | null | undefined): string => {
    if (!timeStr) return '—';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};
</script>
