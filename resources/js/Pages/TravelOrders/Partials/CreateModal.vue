<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-bus text-emerald-600 dark:text-emerald-400"></i>
                            Create Travel Order
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Control No Field -->
                            <div class="space-y-2">
                                <label for="create_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.control_no" id="create_control_no" type="text" disabled placeholder="Auto-generated" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="create_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.date" @input="$emit('update:form-data', { ...formData, date: ($event.target as HTMLInputElement).value })" id="create_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Requesting Employees Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requesting Employee(s) <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-32 overflow-y-auto">
                                        <label v-for="emp in requestingEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                            <input type="checkbox" :value="emp.id" :checked="formData.employee_ids.includes(emp.id)" @change="$emit('update:form-data', { ...formData, employee_ids: ($event.target as HTMLInputElement).checked ? [...formData.employee_ids, emp.id] : formData.employee_ids.filter((id: number) => id !== emp.id) })" class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}<span v-if="!isPboStaff(emp) && emp.office" class="ml-1 text-gray-500 dark:text-gray-400">({{ emp.office.office_abbreviation }})</span></span>
                                        </label>
                                    </div>
                                    <!-- Add an employee from another office (the checklist lists the office's staff) -->
                                    <div class="relative">
                                        <div class="relative flex items-center">
                                            <i class="fas fa-user-plus absolute left-3 text-gray-400 text-xs"></i>
                                            <input
                                                v-model="otherSearch"
                                                type="text"
                                                placeholder="Add an employee from another office (type a name or office)"
                                                class="block w-full pl-9 pr-3 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                            />
                                        </div>
                                        <div v-if="otherMatches.length > 0" class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                                            <button
                                                v-for="emp in otherMatches"
                                                :key="emp.id"
                                                type="button"
                                                @click="addOtherEmployee(emp.id)"
                                                class="w-full text-left px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-gray-700 flex items-center justify-between gap-2"
                                            >
                                                <span class="text-gray-800 dark:text-gray-200">{{ emp.name }}</span>
                                                <span class="text-gray-500 dark:text-gray-400 truncate">{{ emp.office?.office_abbreviation || '' }}</span>
                                            </button>
                                        </div>
                                        <p v-else-if="otherSearch.trim().length >= 2" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">No employee from another office matches "{{ otherSearch }}".</p>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Going To Field -->
                            <div class="space-y-2">
                                <label for="create_going_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Going To <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.going_to" @input="$emit('update:form-data', { ...formData, going_to: ($event.target as HTMLInputElement).value })" id="create_going_to" type="text" placeholder="Destination" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.going_to ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.going_to" class="text-red-500 text-xs">{{ formErrors.going_to }}</span>
                            </div>

                            <!-- Inclusive Dates Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Inclusive Dates <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="flex gap-2 items-end">
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                                            <input v-model="newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                                            <input v-model="newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                        </div>
                                        <button @click.prevent="addInclusiveDate" type="button" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-xs font-medium">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="(entry, idx) in formData.inclusive_dates" :key="idx" @click="editInclusiveDate(idx as number)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs cursor-pointer hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">
                                            <i class="fas fa-edit text-emerald-600 dark:text-emerald-400"></i>
                                            {{ formatInclusiveDate(entry) }}
                                            <button @click.stop="removeInclusiveDate(idx as number)" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 font-bold">×</button>
                                        </span>
                                    </div>
                                </div>
                                <span v-if="formErrors.inclusive_dates" class="text-red-500 text-xs">{{ formErrors.inclusive_dates }}</span>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="flex gap-2">
                                    <textarea v-model="newPurpose" placeholder="Add purpose..." class="flex-1 px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors resize-none" rows="3"></textarea>
                                    <button @click.prevent="addPurpose" type="button" class="px-4 py-2 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors h-fit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div v-if="formData.purpose.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="(p, index) in formData.purpose" :key="index" class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded-full text-xs">
                                        <span v-if="editingPurposeIndex !== index">{{ p }}</span>
                                        <textarea v-else v-model="editingPurposeValue" class="bg-emerald-200 dark:bg-emerald-800 border-0 px-2 py-1 rounded text-emerald-900 dark:text-emerald-100 text-xs focus:outline-none resize-none" rows="4" @keyup.enter="saveEditPurpose(index as number)" @keyup.escape="cancelEditPurpose"></textarea>
                                        <div class="flex gap-1">
                                            <button v-if="editingPurposeIndex !== index" @click="startEditPurpose(index as number)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button v-else @click="saveEditPurpose(index as number)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                            <button v-if="editingPurposeIndex !== index" @click="removePurpose(index as number)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button v-else @click="cancelEditPurpose" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Supervisor Field -->
                            <div class="space-y-2">
                                <label for="create_supervisor" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.supervisor_employee_id || ''"
                                        @change="$emit('update:form-data', { ...formData, supervisor_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                                        id="create_supervisor"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none border-gray-300 focus:border-emerald-500"
                                    >
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in supervisorOptions" :key="emp.id" :value="emp.id">
                                            {{ emp.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Approver Field -->
                            <div class="space-y-2">
                                <label for="create_approver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Approved By <span v-if="!formData.is_acting_approver" class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.approver_employee_id || ''"
                                        @change="$emit('update:form-data', { ...formData, approver_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                                        id="create_approver"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.approver_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                    >
                                        <option :value="null">Select approver</option>
                                        <option v-for="emp in approverOptions" :key="emp.id" :value="emp.id">
                                            {{ emp.name }} ({{ emp.designation }})
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.approver_employee_id" class="text-red-500 text-xs">{{ formErrors.approver_employee_id }}</span>

                                <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :checked="formData.is_acting_approver"
                                        @change="$emit('update:form-data', { ...formData, is_acting_approver: ($event.target as HTMLInputElement).checked, acting_approver_name: ($event.target as HTMLInputElement).checked ? formData.acting_approver_name : '', acting_approver_designation: ($event.target as HTMLInputElement).checked ? formData.acting_approver_designation : '' })"
                                        class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600"
                                    />
                                    <span class="text-xs text-gray-700 dark:text-gray-300">Acting Personnel (enter name and designation of acting signatory)</span>
                                </label>

                                <div v-if="formData.is_acting_approver" class="grid gap-3 mt-2 pl-1">
                                    <div class="space-y-2">
                                        <label for="create_acting_approver_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Acting Name <span class="text-red-600">*</span></label>
                                        <NamePicker input-id="create_acting_approver_name" placeholder="Search an employee or type a name" focus-class="focus:border-emerald-500" :limit="50" :invalid="!!formErrors.acting_approver_name" :model-value="formData.acting_approver_name || ''" :options="actingApproverOptions" @update:model-value="$emit('update:form-data', { ...formData, acting_approver_name: $event })" />
                                        <span v-if="formErrors.acting_approver_name" class="text-red-500 text-xs">{{ formErrors.acting_approver_name }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <label for="create_acting_approver_designation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Acting Designation <span class="text-red-600">*</span></label>
                                        <input
                                            :value="formData.acting_approver_designation"
                                            @input="$emit('update:form-data', { ...formData, acting_approver_designation: ($event.target as HTMLInputElement).value })"
                                            id="create_acting_approver_designation"
                                            type="text"
                                            placeholder="e.g. Acting Provincial Governor"
                                            class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                            :class="[formErrors.acting_approver_designation ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                        />
                                        <span v-if="formErrors.acting_approver_designation" class="text-red-500 text-xs">{{ formErrors.acting_approver_designation }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Field -->
                            <div class="space-y-2">
                                <label for="create_driver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Driver</label>
                                <NamePicker input-id="create_driver" icon="fas fa-user" placeholder="Select a driver or type a name" focus-class="focus:border-emerald-500" :model-value="formData.driver || ''" :options="driverOptions" @update:model-value="$emit('update:form-data', { ...formData, driver: $event })" />
                            </div>

                            <!-- Vehicle Field -->
                            <div class="space-y-2">
                                <label for="create_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-car absolute left-3 text-gray-400 text-sm"></i>
                                    <select :value="formData.vehicle" @change="$emit('update:form-data', { ...formData, vehicle: ($event.target as HTMLSelectElement).value as 'RP Vehicle' | 'PUJ' })" id="create_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option value="PUJ">PUJ</option>
                                        <option value="RP Vehicle">RP Vehicle</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Plate Number Field (RP Vehicle Only) -->
                            <div v-if="formData.vehicle === 'RP Vehicle'" class="space-y-2">
                                <label for="create_plate_number" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Plate Number</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.plate_number" @input="$emit('update:form-data', { ...formData, plate_number: ($event.target as HTMLInputElement).value })" id="create_plate_number" type="text" placeholder="Enter plate number" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors border-gray-300 focus:border-emerald-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button :disabled="!canSubmit" @click="$emit('submit')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-eye"></i>
                            Preview & Continue
                        </button>
                        <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <MissingFieldsHint :fields="missingFields" :message="submitMessage" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import { isPboStaff } from '@/Composables/useOfficials';
import NamePicker, { type NameOption } from './NamePicker.vue';
import { ref, computed } from 'vue';
import type { Employee } from '../Composables/useTravelOrdersData';

const props = defineProps<{
    show: boolean;
    formData: any;
    formErrors: Record<string, string>;
    /** Employees of other offices, searchable to add to the travel order */
    otherEmployees?: any[];
    requestingEmployees: any[];
    supervisorOptions: any[];
    approverOptions: any[];
    driverOptions: NameOption[];
    actingApproverOptions: NameOption[];
}>();

// ============== Requesting employees from other offices ==============
const otherSearch = ref('');
const otherMatches = computed(() => {
    const query = otherSearch.value.trim().toLowerCase();
    if (query.length < 2) return [];
    const selected = props.formData.employee_ids || [];
    return (props.otherEmployees || [])
        .filter((emp: any) => !selected.includes(emp.id)
            && (emp.name?.toLowerCase().includes(query)
                || emp.office?.office_abbreviation?.toLowerCase().includes(query)
                || emp.office?.office_name?.toLowerCase().includes(query)))
        .slice(0, 8);
});
const addOtherEmployee = (id: number) => {
    emit('update:form-data', { ...props.formData, employee_ids: [...(props.formData.employee_ids || []), id] });
    otherSearch.value = '';
};

// ============== Required fields: Save/Create stays disabled until they're filled ==============
const missingFields = computed(() => {
    const f: any = props.formData || {};
    const missing: string[] = [];
    if (!String(f.date ?? '').trim()) missing.push("Date");
    if (!String(f.going_to ?? '').trim()) missing.push("Going To");
    if (!(f.inclusive_dates?.length)) missing.push("Inclusive Dates");
    if (!(f.purpose?.length)) missing.push("Purpose");
    if (!String(f.vehicle ?? '').trim()) missing.push("Vehicle");
    if (!(f.employee_ids?.length)) missing.push("Employees");
    if (!f.is_acting_approver && !f.approver_employee_id) missing.push("Approver");
    if (f.is_acting_approver && !String(f.acting_approver_name ?? '').trim()) missing.push("Acting Approver Name");
    if (f.is_acting_approver && !String(f.acting_approver_designation ?? '').trim()) missing.push("Acting Approver Designation");
    return missing;
});
const submitMessage = computed<string | null>(() => {
    const f: any = props.formData || {};
    return null;
});
const canSubmit = computed(() => missingFields.value.length === 0 && !submitMessage.value);

const emit = defineEmits<{
    close: [];
    'update:form-data': [data: any];
    submit: [];
}>();

const newPurpose = ref('');
const editingPurposeIndex = ref<number | null>(null);
const editingPurposeValue = ref('');
const newInclusiveDate = ref('');
const newInclusiveDateRange = ref('');

const addPurpose = () => {
    if (newPurpose.value?.trim()) {
        props.formData.purpose.push(newPurpose.value.trim());
        newPurpose.value = '';
    }
};

const removePurpose = (index: number) => {
    props.formData.purpose.splice(index, 1);
};

const startEditPurpose = (index: number) => {
    editingPurposeIndex.value = index;
    editingPurposeValue.value = props.formData.purpose[index];
};

const saveEditPurpose = (index: number) => {
    if (editingPurposeValue.value.trim()) {
        props.formData.purpose[index] = editingPurposeValue.value.trim();
    }
    editingPurposeIndex.value = null;
    editingPurposeValue.value = '';
};

const cancelEditPurpose = () => {
    editingPurposeIndex.value = null;
    editingPurposeValue.value = '';
};

const addInclusiveDate = () => {
    if (!newInclusiveDate.value) return;

    let entry = newInclusiveDate.value;
    if (newInclusiveDateRange.value) {
        entry = `${newInclusiveDate.value} - ${newInclusiveDateRange.value}`;
    }

    const newInclusiveDates = [...props.formData.inclusive_dates];
    if (!newInclusiveDates.includes(entry)) {
        newInclusiveDates.push(entry);
    }

    props.formData.inclusive_dates = newInclusiveDates;
    newInclusiveDate.value = '';
    newInclusiveDateRange.value = '';
};

const editInclusiveDate = (index: number) => {
    const entry = props.formData.inclusive_dates[index];
    if (entry.includes(' - ')) {
        const [startStr, endStr] = entry.split(' - ');
        newInclusiveDate.value = startStr.trim();
        newInclusiveDateRange.value = endStr.trim();
    } else {
        newInclusiveDate.value = entry.trim();
        newInclusiveDateRange.value = '';
    }
    removeInclusiveDate(index);
};

const removeInclusiveDate = (index: number) => {
    props.formData.inclusive_dates = props.formData.inclusive_dates.filter((_: any, i: number) => i !== index);
};

const formatInclusiveDate = (dateEntry: string): string => {
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