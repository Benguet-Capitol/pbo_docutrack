<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-leaf text-emerald-600 dark:text-emerald-400"></i>
                            Create Leave
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <!-- Submit Error -->
                        <div v-if="formErrors['submit']" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-sm">
                            {{ formErrors['submit'] }}
                        </div>

                        <div class="space-y-4">
                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Employee <span class="text-red-500">*</span></label>
                                <select v-model.number="formData.employee_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" :class="[formErrors.employee_id ? 'border-red-500 focus:border-red-500' : '']">
                                    <option value="0">Select Employee</option>
                                    <option v-for="emp in requestingOptions" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                </select>
                                <p v-if="formErrors['employee_id']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['employee_id'] }}</p>
                            </div>

                            <!-- Date of Filing -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Filing <span class="text-red-500">*</span></label>
                                <input v-model="formData.date_of_filing" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" :class="[formErrors.date_of_filing ? 'border-red-500 focus:border-red-500' : '']" />
                                <p v-if="formErrors['date_of_filing']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['date_of_filing'] }}</p>
                            </div>

                            <!-- 5. Salary: the employee's monthly salary from HRIS, saved with the leave -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Salary (monthly)</label>
                                <input v-model="formData.salary" type="number" min="0" step="0.01" placeholder="Filled from HRIS when the employee is chosen" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Printed in 5. SALARY as of filing; edit it if HRIS is wrong.</p>
                            </div>

                            <!-- Leave types: one entry per type, each with its own dates -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-medium text-gray-700 dark:text-gray-300">Leave Types <span class="text-red-500">*</span></h3>
                                    <button
                                        type="button"
                                        @click="addEntry"
                                        :disabled="formData.entries.length >= leaveTypes.length"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 border border-emerald-600 rounded-lg hover:bg-emerald-50 dark:hover:bg-gray-700 disabled:opacity-50"
                                    >
                                        <i class="fas fa-plus"></i> Add another leave type
                                    </button>
                                </div>
                                <LeaveEntryFields
                                    v-for="(entry, index) in formData.entries"
                                    :key="index"
                                    :entry="entry"
                                    :index="index"
                                    :total="formData.entries.length"
                                    :leave-types="leaveTypes"
                                    :taken-types="formData.entries.filter((_, other) => other !== index).map((other) => other.type_of_leave)"
                                    :credit="leaveCredits?.[index] ?? null"
                                    :removable="formData.entries.length > 1"
                                    :uid="`create-${index}`"
                                    @remove="formData.entries.splice(index, 1)"
                                />
                                <p v-if="formErrors['type_of_leave']" class="text-xs text-red-600 dark:text-red-400">{{ formErrors['type_of_leave'] }}</p>
                                <p v-if="formErrors['inclusive_dates']" class="text-xs text-red-600 dark:text-red-400">{{ formErrors['inclusive_dates'] }}</p>
                            </div>

                            <!-- 7.B Recommendation -->
                            <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">7.B Recommendation</h3>
                                <div class="flex flex-wrap gap-4">
                                    <label v-for="option in RECOMMENDATIONS" :key="option.value" class="flex items-center gap-2">
                                        <input v-model="formData.recommendation" type="radio" name="recommendation_create" :value="option.value" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ option.label }}</span>
                                    </label>
                                </div>
                                <div v-if="formData.recommendation === 'disapproval'">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">For disapproval due to <span class="text-red-500">*</span></label>
                                    <textarea v-model="formData.disapproval_reason" rows="2" maxlength="500" placeholder="Reason for disapproval" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                    <p v-if="formErrors['disapproval_reason']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['disapproval_reason'] }}</p>
                                </div>
                            </div>

                            <!-- Signatories printed on the form (7.A, 7.B, authorized official) -->
                            <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Signatories</h3>
                                <LeaveSignatoryField
                                    v-for="signatory in LEAVE_SIGNATORIES"
                                    :key="signatory.key"
                                    :signatory="signatory.key"
                                    :employees="employees"
                                    :applicant="applicant"
                                    :employee-id="(formData as any)[`${signatory.key}_employee_id`]"
                                    :title="(formData as any)[`${signatory.key}_title`]"
                                    @change="(value) => { (formData as any)[`${signatory.key}_employee_id`] = value.employeeId; (formData as any)[`${signatory.key}_title`] = value.title; }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="$emit('confirm')"
                            :disabled="loading || !canSubmit"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ loading ? 'Creating...' : 'Preview & Continue' }}
                        </button>
                        <button
                            @click="$emit('close')"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <MissingFieldsHint :fields="missingFields" :message="limitMessage" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { isPboStaff } from '@/Composables/useOfficials';
import { computed } from 'vue';
import LeaveEntryFields from './LeaveEntryFields.vue';
import { newLeaveEntry, type LeaveFormData } from '../Composables/useLeavesForm';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import LeaveSignatoryField from './LeaveSignatoryField.vue';
import { LEAVE_SIGNATORIES } from '../Composables/useLeaveSignatories';
interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface FormErrors {
    [key: string]: string;
}

const props = defineProps<{
    show: boolean;
    formData: LeaveFormData;
    formErrors: FormErrors;
    employees: Employee[];
    loading: boolean;
    leaveTypes: string[];
    /** Yearly credit per entry (limited leave types), aligned with formData.entries */
    leaveCredits?: Array<{ year: number; limit: number; used: number; remaining: number; requested: number; exceeds: boolean } | null>;
    /** Required fields still empty */
    missingFields?: string[];
    /** Yearly leave limit problem, if any */
    limitMessage?: string | null;
}>();

/** Requesting employee: the office's staff (HRIS brings in every employee), plus whoever this record already has */
const requestingOptions = computed(() => {
    const selected: number[] = ([props.formData.employee_id]).map(Number);
    return ((props.employees || []) as any[]).filter((emp: any) => isPboStaff(emp) || selected.includes(Number(emp.id)));
});

/** The applicant: the authorized official is the Governor for a department head, else the Provincial Administrator */
const applicant = computed(() => ((props.employees || []) as any[]).find((emp: any) => emp.id === Number(props.formData.employee_id)) || null);

/** 7.B Recommendation choices ("" = not yet decided) */
const RECOMMENDATIONS = [
    { value: '', label: 'Not yet' },
    { value: 'approval', label: 'For approval' },
    { value: 'disapproval', label: 'For disapproval' },
];

const canSubmit = computed(() => !(props.missingFields?.length) && !props.limitMessage);

defineEmits<{
    close: [];
    confirm: [];
}>();

/** Adds an entry with the first leave type not used yet */
const addEntry = () => {
    const taken = props.formData.entries.map((entry) => entry.type_of_leave);
    const type = props.leaveTypes.find((option) => !taken.includes(option));
    if (type) props.formData.entries.push(newLeaveEntry(type));
};
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
