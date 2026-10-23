<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'min-w-[900px]']">
            <!-- Table Header: sortable columns -->
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.field" :class="LIST_HEAD">
                        <button @click="$emit('sort', column.field)" :class="LIST_SORT">
                            {{ column.label }}
                            <span v-if="sortBy === column.field">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th :class="LIST_HEAD">Office</th>
                    <th :class="[LIST_HEAD, ACTIONS_HEAD]">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(employee, index) in employees" :key="employee.id" :class="listRowClass(index)">
                    <td :class="[LIST_CELL, 'font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap']">{{ employee.employee_id }}</td>
                    <td :class="LIST_CELL">
                        <span :class="LIST_TITLE">{{ employee.name }}</span>
                        <span v-if="employee.separated" :class="[LIST_TAG, 'ml-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200']" :title="`No longer in HRIS since ${employee.separated_at}; hidden from pickers`">Separated</span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ employee.position_title || employee.designation || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ employee.parenthetical_title || '—' }}</td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap']">
                        <span v-if="employee.status" :class="['px-2 py-1 rounded-full text-xs font-semibold', STATUS_CLASS[employee.status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300']">{{ employee.status }}</span>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">
                        <span v-if="employee.office" :title="officeName(employee)">{{ officeAbbreviation(employee) }}</span>
                        <span v-else class="text-gray-400">—</span>
                        <span v-if="employee.detailed_to_pbo" :class="[LIST_TAG, 'ml-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200']" title="Works at the PBO; counts as PBO staff">Detailed to PBO</span>
                    </td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton v-if="canEditEmployees" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', employee)" />
                            <RowIconButton v-if="canDeleteEmployees" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', employee)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TAG, LIST_TITLE, listRowClass } from '@/utils/listTable';
import { STATUS_CLASS, officeAbbreviation, officeName } from '../Composables/employeeDisplay';
import type { Employee } from '../Composables/useEmployeeData';

defineProps<{
    employees: Employee[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditEmployees: boolean;
    canDeleteEmployees: boolean;
    getOfficeName?: (office: any, employee: Employee) => string;
}>();

defineEmits<{
    'sort': [field: string];
    'edit': [employee: Employee];
    'delete': [employee: Employee];
}>();

const columns = [
    { field: 'employee_id', label: 'Employee ID' },
    { field: 'name', label: 'Name' },
    { field: 'position_title', label: 'Position Title' },
    { field: 'parenthetical_title', label: 'Parenthetical Title' },
    { field: 'status', label: 'Status' },
];
</script>
