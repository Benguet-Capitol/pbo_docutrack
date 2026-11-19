<template>
    <!-- An OIC / Acting department head's leave is recommended (7.B) and approved by the Provincial Governor: ticking sets both -->
    <label v-if="!headApplicant" class="flex items-start gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
        <input
            type="checkbox"
            :checked="acting"
            class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
            @change="toggle(($event.target as HTMLInputElement).checked)"
        />
        <span>
            <span class="font-medium">The applicant is an OIC / Acting department head</span>
            <span class="block text-[11px] text-gray-500 dark:text-gray-400">The Provincial Governor recommends (7.B) and approves; change the title below when an OIC or Acting Governor signs.</span>
        </span>
    </label>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { isDepartmentHead } from '@/Composables/useOfficials';
import { applyActingHeadSignatories, isActingHeadLeave } from '../Composables/useLeaveSignatories';

const props = defineProps<{
    /** The form's values: its signatory fields are set here */
    formData: Record<string, any>;
    employees: any[];
    applicant?: any;
}>();

// A department head's leave is the Governor's already
const headApplicant = computed(() => isDepartmentHead(props.applicant));
// Read from the form, so an edited leave shows it and choosing the Budget Officer for 7.B by hand unticks it
const acting = computed(() => isActingHeadLeave(props.formData, props.applicant));

const toggle = (on: boolean) => applyActingHeadSignatories(props.formData, props.employees, props.applicant, on);
</script>
