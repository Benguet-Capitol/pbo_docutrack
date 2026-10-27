<template>
    <!-- Delete Document Modal: Confirmation dialog for deleting a document -->
    <Teleport to="body" v-if="showDeleteModal && documentToDelete">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header: Shows warning icon and title -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                        Delete Document
                    </h3>
                    <!-- Close Button -->
                    <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body: Confirmation message -->
                <div class="px-6 py-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Are you sure you want to delete document <span class="font-semibold">{{ documentToDelete.tracking_no }}</span>?
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                It will be moved to the Trash, where an Administrator or Developer can restore it or delete it permanently.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer: Confirm and Cancel buttons -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="confirmDeleteDocument"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-trash-alt"></i>
                        Delete
                    </button>
                    <button
                        @click="closeDeleteModal"
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
import { useDocumentsContext } from '../Composables/useDocumentsPage';

// Move a document to the Trash
const {
    showDeleteModal, documentToDelete, closeDeleteModal, confirmDeleteDocument,
} = useDocumentsContext();
</script>

<!-- Modal animation (was on the page; scoped styles apply per component) -->
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
