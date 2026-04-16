<template>
    <Teleport to="body" v-if="show && recordToEdit">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
                <div v-if="recordToEdit" class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                        Edit Tardiness/Undertime
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
                            <label for="edit_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                <input :value="formData.control_no" id="edit_control_no" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-600 dark:text-white cursor-not-allowed opacity-75" />
                            </div>
                        </div>

                        <!-- Date Filed Field -->
                        <div class="space-y-2">
                            <label for="edit_date_filed" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date Filed <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <input :value="formData.date_filed" @input="$emit('update:formData', { ...formData, date_filed: ($event.target as HTMLInputElement).value })" id="edit_date_filed" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date_filed ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                            </div>
                            <span v-if="formErrors.date_filed" class="text-red-500 text-xs">{{ formErrors.date_filed }}</span>
                        </div>

                        <!-- Type Field -->
                        <div class="space-y-2">
                            <label for="edit_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Type <span class="text-red-600">*</span></label>
                            <select
                                :value="formData.type"
                                @change="$emit('update:formData', { ...formData, type: ($event.target as HTMLSelectElement).value })"
                                id="edit_type"
                                class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                :class="[formErrors.type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                            >
                                <option value="Undertime">Undertime</option>
                                <option value="Tardiness">Tardiness</option>
                            </select>
                            <span v-if="formErrors.type" class="text-red-500 text-xs">{{ formErrors.type }}</span>
                        </div>

                        <!-- Requested Date Field -->
                        <div class="space-y-2">
                            <label for="edit_requested_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Date <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <input :value="formData.requested_date" @input="$emit('update:formData', { ...formData, requested_date: ($event.target as HTMLInputElement).value })" id="edit_requested_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                            </div>
                            <span v-if="formErrors.requested_date" class="text-red-500 text-xs">{{ formErrors.requested_date }}</span>
                        </div>

                        <!-- Employee Field -->
                        <div class="space-y-2">
                            <label for="edit_employee" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee <span class="text-red-600">*</span></label>
                            <select
                                :value="formData.employee_id"
                                @change="$emit('update:formData', { ...formData, employee_id: Number(($event.target as HTMLSelectElement).value) })"
                                id="edit_employee"
                                class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                :class="[formErrors.employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                            >
                                <option value="0">Select an employee</option>
                                <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
                                </option>
                            </select>
                            <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                        </div>

                        <!-- Requested Time Field -->
                        <div class="space-y-2">
                            <label for="edit_requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Time <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <input :value="formData.requested_time" @input="$emit('update:formData', { ...formData, requested_time: ($event.target as HTMLInputElement).value })" id="edit_requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                            </div>
                            <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                        </div>

                        <!-- Reason Field -->
                        <div class="space-y-2">
                            <label for="edit_reason" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-600">*</span></label>
                            <div class="relative flex items-start">
                                <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea :value="formData.reason" @input="$emit('update:formData', { ...formData, reason: ($event.target as HTMLTextAreaElement).value })" id="edit_reason" placeholder="Enter reason for tardiness/undertime" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.reason ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"></textarea>
                            </div>
                            <span v-if="formErrors.reason" class="text-red-500 text-xs">{{ formErrors.reason }}</span>
                        </div>

                        <!-- Return Time -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Return Time</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="radio" :value="'time'" :checked="formData.returnType === 'time'" @change="$emit('update:formData', { ...formData, returnType: 'time' })" class="rounded" />
                                    Specific Time
                                </label>
                                <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="radio" :value="'nwd'" :checked="formData.returnType === 'nwd'" @change="$emit('update:formData', { ...formData, returnType: 'nwd' })" class="rounded" />
                                    NWD
                                </label>
                            </div>
                            <input
                                v-if="formData.returnType === 'time'"
                                :value="formData.return_time"
                                @input="$emit('update:formData', { ...formData, return_time: ($event.target as HTMLInputElement).value })"
                                type="time"
                                class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 mt-2 transition-colors"
                            />
                        </div>

                        <!-- Supervisor Field -->
                        <div class="space-y-2">
                            <label for="edit_supervisor" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <select
                                    :value="formData.supervisor_employee_id || ''"
                                    @change="$emit('update:formData', { ...formData, supervisor_employee_id: ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null })"
                                    id="edit_supervisor"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.supervisor_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                >
                                    <option value="">Select a supervisor</option>
                                    <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                        {{ emp.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="formErrors.supervisor_employee_id" class="text-red-500 text-xs">{{ formErrors.supervisor_employee_id }}</span>
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
import type { Employee, TardinessRecord } from '../Composables/useTardinessData';

defineProps<{
    show: boolean;
    recordToEdit: TardinessRecord | null;
    formData: {
        control_no: string;
        date_filed: string;
        type: string;
        requested_date: string;
        employee_id: number;
        requested_time: string;
        reason: string;
        return_time: string;
        returnType: 'time' | 'nwd';
        supervisor_employee_id: number | null;
    };
    formErrors: Record<string, string>;
    sortedEmployees: Employee[];
    updating: boolean;
}>();

defineEmits<{
    'update:formData': [data: any];
    close: [];
    submit: [];
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
