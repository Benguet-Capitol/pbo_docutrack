<template>
    <!-- End Transaction Document Modal: Confirmation dialog for ending a document transaction -->
    <Teleport to="body" v-if="showFinalizeModal && documentToFinalize">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeFinalizeModal">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header: Shows success icon and title -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-green-50 to-green-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        End Transaction
                    </h3>
                    <!-- Close Button -->
                    <button @click="closeFinalizeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body: Confirmation message and remarks input -->
                <div class="px-6 py-6 space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                Are you sure you want to end the transaction for document <span class="font-semibold">{{ documentToFinalize.tracking_no }}</span>?
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                This action cannot be undone. The document status will be changed to "Transaction Ended" immediately.
                            </p>
                        </div>
                    </div>

                    <!-- Remarks Field -->
                    <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <label for="finalize_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks (Optional)</label>
                        <div class="relative flex items-start">
                            <textarea
                                v-model="finalizeModalRemarks"
                                id="finalize_remarks"
                                placeholder="Add any remarks or notes about ending transaction for this document..."
                                rows="3"
                                style="white-space: pre-wrap; word-break: break-word;"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-500 transition-colors text-xs resize-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer: Confirm and Cancel buttons -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="confirmFinalizeDocument"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-check"></i>
                        End Transaction
                    </button>
                    <button
                        @click="closeFinalizeModal"
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

// End a document's transaction
const {
    showFinalizeModal, documentToFinalize, finalizeModalRemarks, closeFinalizeModal,
    confirmFinalizeDocument,
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
