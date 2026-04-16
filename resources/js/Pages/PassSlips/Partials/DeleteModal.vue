<template>
    <Teleport to="body" v-if="show && recordToDelete">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash text-red-600 dark:text-red-400"></i>
                            Delete Pass Slip
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6">
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                            Are you sure you want to delete this pass slip?
                        </p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                            Control No: <span class="font-bold text-red-600">{{ recordToDelete.control_no }}</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            This action cannot be undone.
                        </p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="$emit('close')" :disabled="deleting" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button @click="$emit('confirm')" :disabled="deleting" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-trash"></i>
                            {{ deleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import type { PassSlip } from '../Composables/usePassSlipsData';

defineProps<{
    show: boolean;
    recordToDelete: PassSlip | null;
    deleting: boolean;
}>();

defineEmits<{
    close: [];
    confirm: [];
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
