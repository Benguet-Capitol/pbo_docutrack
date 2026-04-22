<template>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                        <button @click="emit('update:sort-by', 'tracking_no'); emit('update:sort-order', sortOrder === 'asc' ? 'desc' : 'asc')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Tracking No
                            <span v-if="sortBy === 'tracking_no'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                        <button @click="emit('update:sort-by', 'date'); emit('update:sort-order', sortOrder === 'asc' ? 'desc' : 'asc')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Date
                            <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">Document Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">Source</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">Particulars</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">Custodian</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Status</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Processing Time</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Remaining Duration</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Transactions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <template v-if="documents.length > 0">
                    <template v-for="document in documents" :key="document.id">
                        <tr @click="toggleExpanded(document.id)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentId === document.id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                            <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                {{ document.tracking_no }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                {{ new Date(document.date).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                <span v-if="document.document_type">{{ document.document_type }}</span>
                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                <span v-if="document.source">{{ document.source }}</span>
                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                <span v-if="document.particulars" :title="document.particulars">{{ document.particulars }}</span>
                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                {{ getCustodianName(document) }}
                            </td>
                            <td class="px-4 py-2 text-xs text-center">
                                <span v-if="document.status === 'created'" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">Created</span>
                                <span v-else-if="document.status === 'forwarded'" class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded-full text-xs font-medium">Forwarded</span>
                                <span v-else-if="document.status === 'pending'" class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-medium">Pending</span>
                                <span v-else-if="document.status === 'finalized'" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium">Transaction Ended</span>
                                <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">{{ document.status }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-center text-gray-600 dark:text-gray-400 font-medium">
                                {{ calculateProcessingTime(document) }}
                            </td>
                            <td class="px-4 py-2 text-xs text-center font-medium">
                                <div :class="getTimeLeftStyles(document)">
                                    {{ getTimeLeftText(document) }}
                                </div>
                            </td>
                            <td class="px-4 py-2 text-xs text-center">
                                <button
                                    @click.stop="toggleExpanded(document.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                >
                                    <i :class="['fas', expandedDocumentId === document.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                    {{ document.transactions?.length || 0 }}
                                </button>
                            </td>
                        </tr>

                        <!-- Expanded Transactions Row -->
                        <tr v-if="expandedDocumentId === document.id" :key="`transactions-${document.id}`" class="bg-gray-50 dark:bg-gray-700/50">
                            <td :colspan="10" class="px-6 py-6">
                                <TransactionHistory
                                    :document="document"
                                    :format-duration="formatDuration"
                                    :get-action-type="getActionType"
                                />
                            </td>
                        </tr>
                    </template>
                </template>
                <tr v-else>
                    <td :colspan="10" class="px-6 py-8 text-center">
                        <div v-if="loading" class="text-gray-500 dark:text-gray-400 space-y-2">
                            <i class="fas fa-spinner fa-spin text-2xl opacity-30"></i>
                            <p>Loading documents...</p>
                        </div>
                        <div v-else-if="error" class="text-red-600 dark:text-red-400">
                            <i class="fas fa-exclamation-circle text-2xl opacity-30 block mb-2"></i>
                            <p>{{ error }}</p>
                        </div>
                        <div v-else class="text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                            <p>No documents found</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import TransactionHistory from './TransactionHistory.vue';

interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string;
    source: string;
    status: string;
    remarks: string;
    user_id: number;
    user?: { id: number; name: string; email: string } | null;
    transactions: any[];
}

defineProps<{
    documents: Document[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    expandedDocumentId: number | null;
    loading: boolean;
    error: string;
    calculateProcessingTime: (doc: Document) => string;
    getTimeLeftText: (doc: Document) => string;
    getTimeLeftStyles: (doc: Document) => object;
    getCustodianName: (doc: Document) => string;
    toggleExpanded: (id: number) => void;
    formatDuration: (hours: number) => string;
    getActionType: (action: string) => string;
}>();

const emit = defineEmits<{
    'update:sort-by': [value: string];
    'update:sort-order': [value: 'asc' | 'desc'];
}>();
</script>
