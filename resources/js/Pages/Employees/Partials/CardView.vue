<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="employee in employees"
            :key="employee.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-center flex-shrink-0 text-sm font-bold">
                    {{ initials(employee.name) }}
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight break-words">{{ employee.name }}<span v-if="employee.separated" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200" :title="`No longer in HRIS since ${employee.separated_at}; hidden from pickers`">Separated</span></h4>
                    <p class="text-gray-600 dark:text-gray-300 break-words">{{ employee.full_position || employee.designation || '—' }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-1.5">
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ employee.employee_id }}</span>
                        <span v-if="employee.status" :class="['px-1.5 py-0.5 rounded-full text-[10px] font-semibold', STATUS_CLASS[employee.status] || 'bg-gray-100 text-gray-700']">{{ employee.status }}</span>
                    </p>
                </div>
            </div>

            <div class="mx-4 mt-3 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Office</div>
                <div v-if="employee.office" class="text-gray-800 dark:text-gray-200 break-words">{{ officeName(employee) }}<span v-if="employee.detailed_to_pbo" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200" title="Works at the PBO; counts as PBO staff">Detailed to PBO</span></div>
                <div v-else class="text-gray-400 dark:text-gray-500">—</div>
            </div>

            <div class="flex-1"></div>
            <div v-if="canEditEmployees || canDeleteEmployees" class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton v-if="canEditEmployees" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', employee)" />
                <RowIconButton v-if="canDeleteEmployees" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', employee)" />
            </div>
            <div v-else class="mt-3"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { STATUS_CLASS, officeName } from '../Composables/employeeDisplay';
import type { Employee } from '../Composables/useEmployeeData';

defineProps<{
    employees: Employee[];
    canEditEmployees: boolean;
    canDeleteEmployees: boolean;
    getOfficeName?: (office: any, employee: Employee) => string;
}>();

defineEmits<{
    'edit': [employee: Employee];
    'delete': [employee: Employee];
}>();

// Initials for the avatar, e.g. "Juan D. Cruz" -> "JC"
const initials = (name: string) => {
    const words = String(name ?? '').replace(/[.,]/g, ' ').split(/\s+/).filter(Boolean);
    return ((words[0]?.[0] ?? '') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase() || '?';
};
</script>
