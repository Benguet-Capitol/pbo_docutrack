<template>
    <!-- Approved By: signatory title + name from PBO employees -->
    <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Approved By (Signatory)</label>

        <p v-if="governorApproves" class="text-xs text-gray-500 dark:text-gray-400 italic">
            <i class="fas fa-info-circle mr-1"></i>The Provincial Governor approves when the Provincial Budget Officer is the requesting employee.
        </p>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="relative flex items-center">
                <i class="fas fa-id-badge absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                <select
                    :value="title || DEFAULT_SIGNATORY_TITLE"
                    @change="onTitleChange(($event.target as HTMLSelectElement).value)"
                    aria-label="Signatory title"
                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none"
                    :class="[errors?.approved_by_title ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                >
                    <option v-for="option in SIGNATORY_TITLES" :key="option" :value="option">{{ option }}</option>
                </select>
            </div>

            <div class="relative flex items-center">
                <i class="fas fa-user-tie absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                <select
                    :value="selectedEmployeeId ?? ''"
                    @change="onEmployeeChange(($event.target as HTMLSelectElement).value)"
                    aria-label="Signatory name"
                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none"
                    :class="[errors?.approved_by_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                >
                    <option value="" disabled>Select a signatory</option>
                    <option v-for="emp in pboEmployees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
            </div>
        </div>

        <span v-if="errors?.approved_by_title" class="block text-red-500 text-xs">{{ errors.approved_by_title }}</span>
        <span v-if="errors?.approved_by_employee_id" class="block text-red-500 text-xs">{{ errors.approved_by_employee_id }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    SIGNATORY_TITLES,
    DEFAULT_SIGNATORY_TITLE,
    isPboEmployee,
    findProvincialBudgetOfficer,
    type SignatoryEmployee,
} from '@/Composables/useSignatory';

const props = defineProps<{
    employees: SignatoryEmployee[];
    employeeId: number | null | undefined;
    title: string | null | undefined;
    errors?: Record<string, string>;
    governorApproves?: boolean;
}>();

const emit = defineEmits<{
    change: [value: { approved_by_employee_id: number | null; approved_by_title: string | null }];
}>();

const pboEmployees = computed(() => props.employees.filter(isPboEmployee));

// Nothing stored yet means the Provincial Budget Officer signs, so show them as selected
const selectedEmployeeId = computed(() => props.employeeId ?? findProvincialBudgetOfficer(pboEmployees.value)?.id ?? null);

const onTitleChange = (value: string) => {
    emit('change', { approved_by_employee_id: selectedEmployeeId.value, approved_by_title: value });
};

const onEmployeeChange = (value: string) => {
    emit('change', { approved_by_employee_id: value ? Number(value) : null, approved_by_title: props.title || DEFAULT_SIGNATORY_TITLE });
};
</script>
