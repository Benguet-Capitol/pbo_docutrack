<template>
    <div class="grid gap-4">
        <!-- Fill from HRIS: search by name, employee ID or office -->
        <div class="space-y-2">
            <label for="hris_search" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Fill from HRIS</label>
            <div class="relative">
                <div class="relative flex items-center">
                    <i class="fas fa-search absolute left-3 text-gray-400 text-sm"></i>
                    <input
                        id="hris_search"
                        v-model="search"
                        type="text"
                        autocomplete="off"
                        placeholder="Search HRIS by name, employee ID or office"
                        @focus="loadHris"
                        class="block w-full pl-10 pr-8 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                    <i v-if="hrisLoading" class="fas fa-spinner fa-spin absolute right-3 text-gray-400 text-sm"></i>
                </div>
                <div v-if="matches.length > 0" class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                    <button
                        v-for="record in matches"
                        :key="record.employee_id_number"
                        type="button"
                        @click="pick(record)"
                        class="w-full text-left px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-gray-700"
                    >
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ record.name }}</span>
                        <span class="text-gray-500 dark:text-gray-400"> · {{ record.employee_id_number }}</span>
                        <span class="block text-gray-500 dark:text-gray-400 truncate">{{ record.position_title || '—' }} · {{ record.office_desc || '—' }}<template v-if="record.status"> · {{ record.status }}</template></span>
                    </button>
                </div>
                <p v-else-if="hrisError" class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ hrisError }}</p>
                <p v-else-if="search.trim().length >= 2 && !hrisLoading" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">No HRIS employee matches "{{ search }}".</p>
                <p v-else class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Picking an HRIS record fills the fields below; you can still edit them.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Employee ID -->
            <div class="space-y-2">
                <label for="employee_id_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee ID <span class="text-red-600">*</span></label>
                <input id="employee_id_field" v-model="formData.employee_id" type="text" :class="inputClass('employee_id')" placeholder="e.g. 01323000299" />
                <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
            </div>

            <!-- Name -->
            <div class="space-y-2">
                <label for="name_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-600">*</span></label>
                <input id="name_field" v-model="formData.name" type="text" :class="inputClass('name')" placeholder="e.g. Juan B. Dela Cruz" />
                <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
            </div>

            <!-- Position Title -->
            <div class="space-y-2">
                <label for="position_title_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Position Title <span class="text-red-600">*</span></label>
                <input id="position_title_field" v-model="formData.position_title" type="text" :class="inputClass('position_title')" placeholder="e.g. Administrative Aide IV" />
                <span v-if="formErrors.position_title" class="text-red-500 text-xs">{{ formErrors.position_title }}</span>
            </div>

            <!-- Parenthetical Title -->
            <div class="space-y-2">
                <label for="parenthetical_title_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Parenthetical Title</label>
                <input id="parenthetical_title_field" v-model="formData.parenthetical_title" type="text" :class="inputClass('parenthetical_title')" placeholder="e.g. Clerk II" />
                <span v-if="formErrors.parenthetical_title" class="text-red-500 text-xs">{{ formErrors.parenthetical_title }}</span>
            </div>

            <!-- Status -->
            <div class="space-y-2">
                <label for="status_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Status <span class="text-red-600">*</span></label>
                <select id="status_field" v-model="formData.status" :class="inputClass('status')">
                    <option v-for="status in EMPLOYEE_STATUSES" :key="status" :value="status">{{ status }}</option>
                </select>
                <span v-if="formErrors.status" class="text-red-500 text-xs">{{ formErrors.status }}</span>
            </div>

            <!-- Office -->
            <div class="space-y-2">
                <label for="office_field" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office <span class="text-red-600">*</span></label>
                <select id="office_field" v-model.number="formData.office" :class="inputClass('office')">
                    <option value="">Select Office</option>
                    <option v-for="office in offices" :key="office.id" :value="office.id">{{ office.office_name }}</option>
                </select>
                <span v-if="formErrors.office" class="text-red-500 text-xs">{{ formErrors.office }}</span>
            </div>
        </div>

        <!-- Works at the PBO while belonging to another office in HRIS -->
        <label v-if="Number(formData.office) !== PBO_OFFICE_ID" class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" v-model="formData.detailed_to_pbo" class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
            <span class="text-xs text-gray-700 dark:text-gray-300">
                Detailed to PBO
                <span class="block text-[11px] text-gray-500 dark:text-gray-400">Counts as PBO staff in Leaves, slips, Locator Chart, etc. The monthly HRIS sync keeps this.</span>
            </span>
        </label>

        <!-- What prints on forms -->
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            <i class="fas fa-print mr-1"></i>
            Printed title: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ printedTitle || '—' }}</span>
            <span class="ml-1">(a department head prints their parenthetical title)</span>
        </p>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { PBO_OFFICE_ID } from '@/Composables/useOfficials';
import { EMPLOYEE_STATUSES, type FormData } from '../Composables/useEmployeeForm';
import type { Office } from '../Composables/useEmployeeData';
import { useHrisEmployees, type HrisRecord } from '../Composables/useHrisEmployees';

const props = defineProps<{
    formData: FormData;
    formErrors: Record<string, string>;
    offices: Office[];
}>();

// HRIS list, loaded once per page visit and shared by the Create and Edit forms
const { records: hrisRecords, loading: hrisLoading, error: hrisError, load: loadHris } = useHrisEmployees();
const search = ref('');

const matches = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (query.length < 2 || !hrisRecords.value) return [];
    return hrisRecords.value
        .filter((r) => r.name?.toLowerCase().includes(query)
            || r.employee_id_number?.toLowerCase().includes(query)
            || r.office_desc?.toLowerCase().includes(query))
        .slice(0, 12);
});

/** HRIS position "Administrative Aide IV (Clerk II)" → ['Administrative Aide IV', 'Clerk II'] (same rule as Employee::splitPosition) */
const splitPosition = (title: string | null): [string, string] => {
    const clean = String(title ?? '').replace(/\s+/g, ' ').trim();
    const m = clean.match(/^(.*?)\s*\(([^()]+)\)\s*$/);
    return m && m[1].trim() ? [m[1].trim(), m[2].trim()] : [clean, ''];
};

const pick = (record: HrisRecord) => {
    const [position, parenthetical] = splitPosition(record.position_title);
    props.formData.employee_id = record.employee_id_number;
    props.formData.name = record.name || '';
    props.formData.position_title = position;
    props.formData.parenthetical_title = parenthetical;
    if (record.status) props.formData.status = record.status;
    if (record.office_id) props.formData.office = record.office_id;
    search.value = '';
};

/** Same rule as Employee::titleOf */
const printedTitle = computed(() => {
    const position = props.formData.position_title?.trim() || '';
    const parenthetical = props.formData.parenthetical_title?.trim() || '';
    return parenthetical && /department head/i.test(position) ? parenthetical : position;
});

const inputClass = (field: string) => [
    'block w-full px-3 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500',
    props.formErrors[field] ? 'border-red-500' : 'border-gray-300',
];
</script>
