<template>
    <!-- Edit Employee Modal -->
    <Teleport to="body" v-if="showEditModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-3xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-edit text-blue-600 dark:text-blue-400"></i>
                        Edit Employee
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <EmployeeFields :form-data="formData" :form-errors="formErrors" :offices="offices" />

                    <div v-if="formErrors.submit" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        :disabled="missingFields.length > 0 || !hasChanges"
                        @click="onSave"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <MissingFieldsHint :fields="missingFields" :message="hasChanges ? null : NO_CHANGES_MESSAGE" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import { useFormChanges, NO_CHANGES_MESSAGE } from '@/Composables/useFormChanges';
import EmployeeFields from './EmployeeFields.vue';
import { employeeMissingFields } from '../Composables/employeeRequiredFields';
import type { FormData } from '../Composables/useEmployeeForm';
import type { Office } from '../Composables/useEmployeeData';

const props = defineProps<{
    showEditModal: boolean;
    formData: FormData;
    formErrors: Record<string, string>;
    offices: Office[];
}>();

const emit = defineEmits<{
    'close': [];
    'save': [];
}>();

// Update stays disabled until the required fields are filled
const missingFields = computed(() => employeeMissingFields(props.formData));
// Update stays disabled until something is changed
const { hasChanges } = useFormChanges(() => props.formData, () => props.showEditModal);

const onClose = () => emit('close');
const onSave = () => emit('save');
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
