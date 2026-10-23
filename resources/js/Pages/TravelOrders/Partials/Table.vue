<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-14">
                <col class="w-12">
                <col class="w-28">
                <col class="w-28">
                <col class="w-28">
                <col class="w-40">
                <col class="w-12">
                <col class="w-16">
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
                <tr v-for="(order, index) in records" :key="order.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ order.control_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(order.date) }}</td>
                    <td :class="LIST_CELL">
                        <div class="flex flex-wrap gap-1">
                            <span v-for="emp in order.employees" :key="emp.id" :class="[LIST_TAG, 'rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200']">{{ emp.name }}</span>
                        </div>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']">{{ order.going_to || '—' }}</td>
                    <td :class="LIST_CELL">
                        <div v-if="order.inclusive_dates && order.inclusive_dates.length > 0" class="flex flex-wrap gap-1">
                            <span v-for="(date, idx) in order.inclusive_dates" :key="idx" :class="[LIST_TAG, DATE_TAG]">{{ formatInclusiveDateForTable(date) }}</span>
                        </div>
                        <span v-else-if="order.from_date && order.to_date" class="whitespace-nowrap text-gray-700 dark:text-gray-300">
                            {{ order.from_date === order.to_date ? listDate(order.from_date) : `${listDate(order.from_date)} - ${listDate(order.to_date)}` }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="LIST_CELL">
                        <div v-if="order.purpose && order.purpose.length" class="flex flex-wrap gap-1">
                            <span v-for="p in order.purpose" :key="p" class="inline-block px-2 py-1 rounded text-xs bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 break-words">{{ p }}</span>
                        </div>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ order.vehicle || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('preview', order)" />
                            <RowIconButton v-if="canEditTravelOrders" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', order)" />
                            <RowIconButton v-if="canDeleteTravelOrders" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', order)" />
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
import type { TravelOrder } from '../Composables/useTravelOrdersData';

defineProps<{
    records: TravelOrder[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditTravelOrders: boolean;
    canDeleteTravelOrders: boolean;
}>();

defineEmits<{
    edit: [order: TravelOrder];
    delete: [order: TravelOrder];
    preview: [order: TravelOrder];
    sort: [field: string];
}>();

const COLUMNS: ListColumn[] = [
    { label: 'Control No', sort: 'control_no' },
    { label: 'Date', sort: 'date' },
    { label: 'Employees', sort: 'employees' },
    { label: 'Going To', sort: 'going_to' },
    { label: 'Inclusive Dates' },
    { label: 'Purpose' },
    { label: 'Vehicle' },
];

const formatInclusiveDateForTable = (dateEntry: string): string => {
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
