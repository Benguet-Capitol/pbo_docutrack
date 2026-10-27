<template>
    <!-- Card View: used on small screens, or when chosen on desktop. Clicking a card opens its transactions -->
    <div class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="document in paginatedDocuments"
                :key="document.id"
                @click="viewDocumentTransactions(document)"
                :class="[
                    'border border-t-4 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col cursor-pointer',
                    statusBadge(document).accent,
                    showTransactionsModal && documentViewingTransactions?.id === document.id
                        ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300 dark:border-indigo-700'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                ]"
            >
                <div class="px-4 pt-3">
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ document.tracking_no }}</h4>
                        <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            <i class="far fa-calendar text-[10px]"></i> {{ new Date(document.date).toLocaleDateString() }}
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusBadge(document).classes]">{{ statusBadge(document).label }}</span>
                        <span v-if="document.document_type" class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium" :class="documentTypeStyle(document.document_type).classes"><i :class="[documentTypeStyle(document.document_type).icon, 'text-[10px]']"></i>{{ document.document_type }}</span>
                    </div>
                    <p class="mt-2 text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="document.particulars">{{ document.particulars || '—' }}</p>
                </div>

                <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Source</div>
                        <div class="text-gray-800 dark:text-gray-200 break-words">{{ document.source || '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Custodian</div>
                        <div class="text-gray-800 dark:text-gray-200 break-words">{{ getCustodianDisplay(document) }}</div>
                    </div>
                    <div v-if="document.remarks" class="col-span-2 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Remarks</div>
                        <div class="text-gray-800 dark:text-gray-200 break-words" style="white-space: pre-wrap;">{{ document.remarks }}</div>
                    </div>
                </div>

                <div class="flex-1"></div>
                <div class="mt-3 flex items-center justify-between gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                    <div class="font-medium min-w-0" :class="getTimeLeftStyles(document)" title="Remaining Duration">
                        {{ getTimeLeftText(document) }}
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 flex-shrink-0">
                        <RowIconButton
                            v-if="(document.status === 'created' || document.status === 'pending') && document.user_id === currentUser?.id && hasPermission('documents.forward')"
                            color="cyan" icon="fas fa-share" tooltip="Forward" @click.stop="handleForwardDocument(document)"
                        />
                        <RowIconButton v-if="canReceiveDocument(document)" color="purple" icon="fas fa-folder-open" tooltip="Receive" @click.stop="handleReceiveDocument(document)" />
                        <RowIconButton
                            v-if="document.status === 'pending' && hasPermission('documents.finalize')"
                            color="green" icon="fas fa-calendar-xmark" tooltip="End Transaction" @click.stop="handleFinalizeDocument(document)"
                        />
                        <RowIconButton
                            v-if="document.document_type === 'Annual Budget' || document.document_type === 'Supplemental Budget' || document.document_type === 'Budget Proposals'"
                            color="purple" icon="fas fa-list-check" tooltip="View Checklist" @click.stop="viewDocumentChecklist(document)"
                        />
                        <RowIconButton v-if="hasPermission('documents.edit')" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="handleEditDocument(document)" />
                        <RowIconButton v-if="hasPermission('documents.delete')" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="handleDeleteDocument(document)" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import RowIconButton from '@/Components/RowIconButton.vue';

// The card view (small screens, or when chosen)
const {
    documentTypeStyle, hasPermission, currentUser, showTransactionsModal, documentViewingTransactions,
    getTimeLeftText, getTimeLeftStyles, statusBadge, paginatedDocuments, canReceiveDocument,
    getCustodianDisplay, handleEditDocument, handleDeleteDocument, handleForwardDocument,
    handleFinalizeDocument, handleReceiveDocument, viewDocumentTransactions, viewDocumentChecklist,
} = useDocumentsContext();
</script>
