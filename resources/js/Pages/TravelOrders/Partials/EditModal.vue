<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Travel Order
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
                                <label for="edit_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.control_no" id="edit_control_no" type="text" disabled placeholder="Auto-generated" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="edit_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.date" @input="$emit('update:form-data', { ...formData, date: ($event.target as HTMLInputElement).value })" id="edit_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
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
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                        </label>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Going To Field -->
                            <div class="space-y-2">
                                <label for="edit_going_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Going To <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.going_to" @input="$emit('update:form-data', { ...formData, going_to: ($event.target as HTMLInputElement).value })" id="edit_going_to" type="text" placeholder="Destination" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.going_to ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
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
                                            <input v-model="newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                                            <input v-model="newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                        </div>
                                        <button @click.prevent="addInclusiveDate" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="(entry, idx) in formData.inclusive_dates" :key="idx" @click="editInclusiveDate(idx as number)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                                            {{ formatInclusiveDate(entry) }}
                                            <button @click.stop="removeInclusiveDate(idx as number)" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-bold">×</button>
                                        </span>
                                    </div>
                                </div>
                                <span v-if="formErrors.inclusive_dates" class="text-red-500 text-xs">{{ formErrors.inclusive_dates }}</span>
                            </div>
                            
                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="flex gap-2">
                                    <textarea v-model="newPurpose" placeholder="Add purpose..." class="flex-1 px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 transition-colors resize-none" rows="3"></textarea>
                                    <button @click.prevent="addPurpose" type="button" class="px-4 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors h-fit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div v-if="formData.purpose.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="(p, index) in formData.purpose" :key="index" class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs">
                                        <span v-if="editingPurposeIndex !== index">{{ p }}</span>
                                        <textarea v-else v-model="editingPurposeValue" class="bg-blue-200 dark:bg-blue-800 border-0 px-2 py-1 rounded text-blue-900 dark:text-blue-200 text-xs focus:outline-none resize-none" rows="4" @keyup.enter="saveEditPurpose(index as number)" @keyup.escape="cancelEditPurpose"></textarea>
                                        <div class="flex gap-1">
                                            <button v-if="editingPurposeIndex !== index" @click="startEditPurpose(index as number)" type="button" class="hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button v-else @click="saveEditPurpose(index as number)" type="button" class="hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                            <button v-if="editingPurposeIndex !== index" @click="removePurpose(index as number)" type="button" class="hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button v-else @click="cancelEditPurpose" type="button" class="hover:text-blue-600 dark:hover:text-blue-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Supervisor Field -->
                            <div class="space-y-2">
                                <label for="edit_supervisor" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.supervisor_employee_id || ''"
                                        @change="$emit('update:form-data', { ...formData, supervisor_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                                        id="edit_supervisor"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none border-gray-300 focus:border-blue-500"
                                    >
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in supervisorOptions" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Approver Field -->
                            <div class="space-y-2">
                                <label for="edit_approver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Approved By <span v-if="!formData.is_acting_approver" class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.approver_employee_id || ''"
                                        @change="$emit('update:form-data', { ...formData, approver_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                                        id="edit_approver"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.approver_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
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
                                        class="rounded border-gray-300 dark:border-gray-600 accent-blue-600"
                                    />
                                    <span class="text-xs text-gray-700 dark:text-gray-300">Acting Personnel (enter name and designation of acting signatory)</span>
                                </label>

                                <div v-if="formData.is_acting_approver" class="grid gap-3 mt-2 pl-1">
                                    <div class="space-y-2">
                                        <label for="edit_acting_approver_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Acting Name <span class="text-red-600">*</span></label>
                                        <input
                                            :value="formData.acting_approver_name"
                                            @input="$emit('update:form-data', { ...formData, acting_approver_name: ($event.target as HTMLInputElement).value })"
                                            id="edit_acting_approver_name"
                                            type="text"
                                            placeholder="e.g. Juan B. Dela Cruz"
                                            class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                            :class="[formErrors.acting_approver_name ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                        />
                                        <span v-if="formErrors.acting_approver_name" class="text-red-500 text-xs">{{ formErrors.acting_approver_name }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <label for="edit_acting_approver_designation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Acting Designation <span class="text-red-600">*</span></label>
                                        <input
                                            :value="formData.acting_approver_designation"
                                            @input="$emit('update:form-data', { ...formData, acting_approver_designation: ($event.target as HTMLInputElement).value })"
                                            id="edit_acting_approver_designation"
                                            type="text"
                                            placeholder="e.g. Acting Provincial Governor"
                                            class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                            :class="[formErrors.acting_approver_designation ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                        />
                                        <span v-if="formErrors.acting_approver_designation" class="text-red-500 text-xs">{{ formErrors.acting_approver_designation }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Field -->
                            <div class="space-y-2">
                                <label for="edit_driver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Driver</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.driver" @input="$emit('update:form-data', { ...formData, driver: ($event.target as HTMLInputElement).value })" id="edit_driver" type="text" list="edit_driver_options" placeholder="Enter or select driver name" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors border-gray-300 focus:border-blue-500" />
                                    <datalist id="edit_driver_options">
                                        <option v-for="driver in driverOptions" :key="driver" :value="driver" />
                                    </datalist>
                                </div>
                            </div>

                            <!-- Vehicle Field -->
                            <div class="space-y-2">
                                <label for="edit_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-car absolute left-3 text-gray-400 text-sm"></i>
                                    <select :value="formData.vehicle" @change="$emit('update:form-data', { ...formData, vehicle: ($event.target as HTMLSelectElement).value as 'RP Vehicle' | 'PUJ' })" id="edit_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']">
                                        <option value="PUJ">PUJ</option>
                                        <option value="RP Vehicle">RP Vehicle</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Plate Number Field (RP Vehicle Only) -->
                            <div v-if="formData.vehicle === 'RP Vehicle'" class="space-y-2">
                                <label for="edit_plate_number" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Plate Number</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.plate_number" @input="$emit('update:form-data', { ...formData, plate_number: ($event.target as HTMLInputElement).value })" id="edit_plate_number" type="text" placeholder="Enter plate number" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors border-gray-300 focus:border-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="$emit('submit')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-eye"></i>
                            Preview & Continue
                        </button>
                        <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Employee } from '../Composables/useTravelOrdersData';

const props = defineProps<{
    show: boolean;
    formData: any;
    formErrors: Record<string, string>;
    requestingEmployees: any[];
    supervisorOptions: any[];
    approverOptions: any[];
    driverOptions: string[];
}>();

defineEmits<{
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

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>