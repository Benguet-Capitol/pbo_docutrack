<template>
    <!-- Delete Employee Modal -->
    <Teleport to="body" v-if="showDeleteModal && employeeToDelete">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                        Delete Employee
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Are you sure you want to delete <span class="font-semibold">{{ employeeToDelete.name }}</span>?
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                This action cannot be undone. All associated data will be permanently deleted.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="onConfirm"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-trash-alt"></i>
                        Delete
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { Employee } from '../Composables/useEmployeeData';

defineProps({
    showDeleteModal: {
        type: Boolean,
        required: true,
    },
    employeeToDelete: {
        type: Object as () => Employee | null,
        required: false,
    },
});

const emit = defineEmits<{
    'close': [];
    'confirm': [];
}>();

const onClose = () => {
    emit('close');
};

const onConfirm = () => {
    emit('confirm');
};
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
