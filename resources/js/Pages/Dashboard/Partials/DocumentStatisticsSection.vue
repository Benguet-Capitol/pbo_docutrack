<template>
    <!-- Documents Processing Time Section: Average Duration per document type -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header Section -->
        <div class="px-4 sm:px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
                Average Processing Time for Documents
            </h3>
        </div>

        <!-- Document Statistics Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                            Document Type
                        </th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                            Total Documents
                        </th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                            Average Duration
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-if="documentProcessingStatistics.length > 0">
                        <template v-for="docStat in documentProcessingStatistics" :key="`doc-${docStat.documentType}`">
                            <tr @click="$emit('expandDocumentType', docStat.documentType)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentTypeLocal === docStat.documentType ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <i :class="['fas', expandedDocumentTypeLocal === docStat.documentType ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                    {{ docStat.documentType }}
                                </td>
                                <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center justify-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-xs font-medium">
                                        {{ docStat.count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-center font-medium">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                        <i class="fas fa-stopwatch"></i>
                                        {{ formatHours(docStat.averageHours) }}
                                    </span>
                                </td>
                            </tr>
                            <!-- Expanded Document Type Details Row -->
                            <tr v-if="expandedDocumentTypeLocal === docStat.documentType" class="bg-gray-50 dark:bg-gray-700/50">
                                <td :colspan="3" class="px-6 py-6">
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                            <i class="fas fa-list text-purple-600 dark:text-purple-400"></i>
                                            Documents of Type: {{ docStat.documentType }}
                                        </h4>
                                        
                                        <!-- No Documents State -->
                                        <div v-if="getDocumentsForType(docStat.documentType).length === 0" class="py-6 text-center">
                                            <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">No documents found</p>
                                        </div>

                                        <!-- Documents Table -->
                                        <div v-else class="overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead class="bg-gray-200 dark:bg-gray-600">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Tracking No</th>
                                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Processing Time</th>
                                                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Remaining Duration</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                    <tr v-for="doc in getDocumentsForType(docStat.documentType)" :key="`doc-detail-${doc.id}`" class="bg-white dark:bg-gray-800">
                                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                            {{ doc.tracking_no }}
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate" :title="doc.particulars || '-'">
                                                            {{ doc.particulars || '-' }}
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                                            <span v-if="doc.status === 'created'" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">Created</span>
                                                            <span v-else-if="doc.status === 'forwarded'" class="px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                            <span v-else-if="doc.status === 'pending'" class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded text-xs font-medium">Pending</span>
                                                            <span v-else-if="doc.status === 'finalized'" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Ended</span>
                                                            <span v-else class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded text-xs font-medium">{{ doc.status }}</span>
                                                        </td>
                                                        <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                            {{ calculateProcessingTime(doc) }}
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <div :class="getTimeLeftStyles(doc)">
                                                                {{ getTimeLeftText(doc) }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-else>
                        <td :colspan="3" class="px-6 py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                <p class="text-sm">No document data available</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    documentProcessingStatistics: Array<any>,
    expandedDocumentType: String,
    filteredDocuments: Array<any>,
    formatHours: Function,
    calculateProcessingTime: Function,
    getTimeLeftText: Function,
    getTimeLeftStyles: Function,
});

const emit = defineEmits(['expandDocumentType']);

const expandedDocumentTypeLocal = computed({
    get: () => props.expandedDocumentType,
    set: (val) => emit('expandDocumentType', val),
});

const getDocumentsForType = (documentType: string) => {
    return props.filteredDocuments.filter((doc: any) => doc.document_type === documentType);
};
</script>
