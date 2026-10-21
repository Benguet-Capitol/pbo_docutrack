<template>
    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[900px]">
            <!-- Table Header: sortable columns -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th v-for="column in columns" :key="column.field" class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', column.field)" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            {{ column.label }}
                            <span v-if="sortBy === column.field" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Office</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="employee in employees"
                    :key="employee.id"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                >
                    <td class="px-4 py-2 text-xs font-mono text-gray-900 dark:text-gray-100">{{ employee.employee_id }}</td>
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">{{ employee.name }}</td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ employee.position_title || employee.designation }}</td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="employee.parenthetical_title">{{ employee.parenthetical_title }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <span v-if="employee.status" :class="['px-2 py-0.5 rounded-full text-[11px] font-semibold', STATUS_CLASS[employee.status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300']">{{ employee.status }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="employee.office" :title="officeName(employee)">{{ officeAbbreviation(employee) }}</span><span v-if="employee.detailed_to_pbo" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200" title="Works at the PBO; counts as PBO staff">Detailed to PBO</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
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
