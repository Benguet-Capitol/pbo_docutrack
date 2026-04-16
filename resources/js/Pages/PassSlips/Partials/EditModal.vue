<template>
    <Teleport to="body" v-if="show && recordToEdit">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Pass Slip
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
                                <label for="control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No</label>
                                <input :value="formData.control_no" type="text" disabled class="block w-full px-4 py-2 text-xs border rounded-lg bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-600 dark:text-white cursor-not-allowed opacity-75" />
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <input :value="formData.date" @input="$emit('update:formData', { ...formData, date: ($event.target as HTMLInputElement).value })" id="date" type="date" class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Requesting Employees Field -->
                            <div class="space-y-2">
                                <label for="employees" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requesting Employee(s) <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-32 overflow-y-auto">
                                        <div v-if="sortedEmployees.length === 0" class="text-xs text-gray-500 dark:text-gray-400 py-2">
                                            No employees available
                                        </div>
                                        <label v-for="emp in sortedEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                            <input type="checkbox" :value="emp.id" :checked="formData.employee_ids.includes(emp.id)" @change="$emit('update:formData', { ...formData, employee_ids: ($event.target as HTMLInputElement).checked ? [...formData.employee_ids, emp.id] : formData.employee_ids.filter((id: number) => id !== emp.id) })" class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                        </label>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Requested to Leave At Field -->
                            <div class="space-y-2">
                                <label for="requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested to Leave At <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.requested_time" @input="$emit('update:formData', { ...formData, requested_time: ($event.target as HTMLInputElement).value })" id="requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label for="purpose" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea :value="formData.purpose" @input="$emit('update:formData', { ...formData, purpose: ($event.target as HTMLTextAreaElement).value })" id="purpose" placeholder="Enter purpose" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.purpose ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"></textarea>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Location Field -->
                            <div class="space-y-2">
                                <label for="location" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input :value="formData.location" @input="$emit('update:formData', { ...formData, location: ($event.target as HTMLInputElement).value })" id="location" type="text" placeholder="Enter location" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.location ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.location" class="text-red-500 text-xs">{{ formErrors.location }}</span>
                            </div>

                            <!-- Expected Time of Return Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Expected Time of Return <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" :checked="formData.returnType === 'time'" @change="$emit('update:formData', { ...formData, returnType: 'time' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Specific Time</span>
                                            </label>
                                            <div v-show="formData.returnType === 'time'" class="relative flex items-center">
                                                <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                                <input :value="formData.expected_return_time" @input="$emit('update:formData', { ...formData, expected_return_time: ($event.target as HTMLInputElement).value })" id="expected_return_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.expected_return_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" :checked="formData.returnType === 'asap'" @change="$emit('update:formData', { ...formData, returnType: 'asap' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">ASAP</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" :checked="formData.returnType === 'nwd'" @change="$emit('update:formData', { ...formData, returnType: 'nwd' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NWD</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" :checked="formData.returnType === 'time_slip'" @change="$emit('update:formData', { ...formData, returnType: 'time_slip' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Time Slip</span>
                                            </label>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" :checked="formData.returnType === 'nom'" @change="$emit('update:formData', { ...formData, returnType: 'nom' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NOM</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :checked="formData.returnType === 'memo'" @change="$emit('update:formData', { ...formData, returnType: 'memo' })" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Memo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.expected_return_time" class="text-red-500 text-xs">{{ formErrors.expected_return_time }}</span>
                            </div>

                            <!-- Vehicle to be Used Field -->
                            <div class="space-y-2">
                                <label for="vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle to be Used <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-van-shuttle absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select :value="formData.vehicle" @change="$emit('update:formData', { ...formData, vehicle: ($event.target as HTMLSelectElement).value as 'RP Vehicle' | 'PUJ' })" id="vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option value="RP Vehicle">RP Vehicle</option>
                                        <option value="PUJ">PUJ</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Recommending Approval (Supervisor) Field -->
                            <div class="space-y-2">
                                <label for="recommending_approval_employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Recommending Approval (Supervisor)</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select :value="formData.recommending_approval_employee_id || ''" @change="$emit('update:formData', { ...formData, recommending_approval_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })" id="recommending_approval_employee_id" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.recommending_approval_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.recommending_approval_employee_id" class="text-red-500 text-xs">{{ formErrors.recommending_approval_employee_id }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label for="remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea :value="formData.remarks" @input="$emit('update:formData', { ...formData, remarks: ($event.target as HTMLTextAreaElement).value })" id="remarks" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none" :class="[formErrors.remarks ? 'border-red-500 focus:border-red-500' : 'border-gray-300']"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="$emit('submit')" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ updating ? 'Updating...' : 'Preview & Continue' }}
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
import type { PassSlip, Employee } from '../Composables/usePassSlipsData';

defineProps<{
    show: boolean;
    recordToEdit: PassSlip | null;
    formData: {
        control_no: string;
        date: string;
        requested_time: string;
        purpose: string;
        location: string;
        expected_return_time: string;
        remarks: string;
        employee_ids: number[];
        recommending_approval_employee_id: number | null;
        vehicle: 'RP Vehicle' | 'PUJ';
        returnType: 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo';
    };
    formErrors: Record<string, string>;
    sortedEmployees: Employee[];
    updating: boolean;
}>();

defineEmits<{
    'update:formData': [data: any];
    submit: [];
    close: [];
}>();
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
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
