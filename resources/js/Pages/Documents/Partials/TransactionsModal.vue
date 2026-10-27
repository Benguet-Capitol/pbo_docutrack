<template>
    <!-- Document Transactions Modal: Displays all transactions for a document -->
    <Teleport to="body" v-if="showTransactionsModal && documentViewingTransactions">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeTransactionsModal">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-purple-50 to-purple-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
                        Document Transactions
                    </h3>
                    <button @click="closeTransactionsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Tracking No.: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.tracking_no }}</span>
                        </p>
                        <p v-if="documentViewingTransactions.source" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Source: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.source }}</span>
                        </p>
                        <p v-if="documentViewingTransactions.particulars" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Particulars: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.particulars }}</span>
                        </p>
                    </div>

                    <!-- No Transactions State -->
                    <div v-if="documentTransactions.length === 0" class="py-8 text-center">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-3xl mb-2 block"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">No transactions found</p>
                    </div>

                    <!-- Transactions Timeline -->
                    <div v-else class="space-y-3">
                        <div v-for="transaction in documentTransactions" :key="transaction.id" class="flex gap-4 pb-4 border-l-2 border-gray-300 dark:border-gray-600 pl-4">
                            <!-- Timeline Dot -->
                            <div class="flex-shrink-0 mt-1">
                                <div v-if="getActionType(transaction.action) === 'created'" class="w-3 h-3 rounded-full bg-green-500 ring-2 ring-green-200 dark:ring-green-900/30 -ml-5"></div>
                                <div v-else-if="getActionType(transaction.action) === 'forwarded'" class="w-3 h-3 rounded-full bg-cyan-500 ring-2 ring-cyan-200 dark:ring-cyan-900/30 -ml-5"></div>
                                <div v-else-if="getActionType(transaction.action) === 'received'" class="w-3 h-3 rounded-full bg-orange-500 ring-2 ring-orange-200 dark:ring-orange-900/30 -ml-5"></div>
                                <div v-else-if="getActionType(transaction.action) === 'finalized'" class="w-3 h-3 rounded-full bg-purple-500 ring-2 ring-purple-200 dark:ring-purple-900/30 -ml-5"></div>
                            </div>

                            <!-- Transaction Details -->
                            <div class="flex-grow">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-grow">
                                        <!-- Action Badge -->
                                        <div class="mb-1">
                                            <span v-if="getActionType(transaction.action) === 'created'" class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Created</span>
                                            <span v-else-if="getActionType(transaction.action) === 'forwarded'" class="inline-block px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                            <span v-else-if="getActionType(transaction.action) === 'received'" class="inline-block px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">Received</span>
                                            <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Transaction Ended</span>
                                        </div>
                                        
                                        <!-- Action Details -->
                                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-1">{{ transaction.action }}</p>

                                        <!-- User, timestamp, and recipient info -->
                                        <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded text-xs">
                                            <p class="text-gray-600 dark:text-gray-400">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.user?.name || 'Unknown User' }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    — {{ new Date(transaction.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                                    at {{ new Date(transaction.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                                                </span>
                                            </p>
                                            <p v-if="transaction.duration_hours !== null && transaction.duration_hours !== undefined" class="text-gray-600 dark:text-gray-400 mt-1">
                                                <i class="fas fa-hourglass-end text-blue-600 dark:text-blue-400 mr-1"></i>
                                                Duration: <span class="font-semibold">{{ formatDuration(transaction.duration_hours) }}</span>
                                            </p>
                                            <p v-if="getActionType(transaction.action) === 'forwarded' && (transaction.forwardedToUser || transaction.forwardedToOffice || transaction.forwardedToMunicipality)" class="text-gray-600 dark:text-gray-400 mt-1">
                                                <span v-if="transaction.forwardedToUser">
                                                    Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToUser.name }}</span>
                                                </span>
                                                <span v-else-if="transaction.forwardedToOffice">
                                                    Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToOffice.office_name }}</span>
                                                </span>
                                                <span v-else-if="transaction.forwardedToMunicipality">
                                                    Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToMunicipality.name }}</span>
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Remarks -->
                                        <p v-if="transaction.remarks" class="text-xs text-gray-700 dark:text-gray-300 mt-2 italic" style="white-space: pre-wrap; word-break: break-word;">
                                            "{{ transaction.remarks }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="closeTransactionsModal"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';

// A document's transaction history
const {
    getActionType, formatDuration, showTransactionsModal, documentViewingTransactions,
    documentTransactions, closeTransactionsModal,
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
