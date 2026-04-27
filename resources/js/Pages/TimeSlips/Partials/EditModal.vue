<template>
    <Teleport to="body" v-if="show && recordToEdit">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Time Slip
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                <!-- Control No Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Control No
                    </label>
                    <input
                        :value="formData.control_no"
                        type="text"
                        disabled
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 focus:outline-none"
                    />
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.date"
                        type="date"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                    <span v-if="formErrors.date" class="text-xs text-red-500">{{ formErrors.date }}</span>
                </div>

                <!-- Requesting Employee -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Requesting Employee <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model.number="formData.requesting_employee_id"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">Select employee</option>
                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                            {{ emp.name }}
                        </option>
                    </select>
                    <span v-if="formErrors.requesting_employee_id" class="text-xs text-red-500">{{ formErrors.requesting_employee_id }}</span>
                </div>

                <!-- Time Fields Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">In AM</label>
                        <input
                            v-model="formData.time_in_am"
                            type="time"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Out AM</label>
                        <input
                            v-model="formData.time_out_am"
                            type="time"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">In PM</label>
                        <input
                            v-model="formData.time_in_pm"
                            type="time"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Out PM</label>
                        <input
                            v-model="formData.time_out_pm"
                            type="time"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>
                <span v-if="formErrors.times" class="text-xs text-red-500 block">{{ formErrors.times }}</span>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="formData.reason"
                        rows="4"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                        placeholder="Enter the reason for the time slip..."
                    ></textarea>
                    <span v-if="formErrors.reason" class="text-xs text-red-500">{{ formErrors.reason }}</span>
                </div>

                <!-- Certified By -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Certified By
                    </label>
                    <select
                        v-model.number="formData.certified_by_employee_id"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">Select Certified By</option>
                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                            {{ emp.name }}
                        </option>
                        </select>
                    </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="$emit('submit')" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ updating ? 'Loading...' : 'Preview & Continue' }}
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
defineProps<{
    show: boolean;
    recordToEdit: any;
    formData: any;
    formErrors: Record<string, string>;
    sortedEmployees: any[];
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
