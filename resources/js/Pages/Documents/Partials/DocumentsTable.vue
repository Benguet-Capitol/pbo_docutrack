<template>
    <!-- Data Table: shown in table view once documents data is loaded (list style shared with pbo-registryV2:
         the document pinned on the left, related details grouped, shaded rows, actions pinned on the right) -->
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-xs border-separate border-spacing-0">
            <thead>
                <tr>
                    <th class="sticky left-0 z-[2] min-w-[16rem]" :class="LIST_HEAD">
                        <div class="flex items-center gap-1.5">
                            <template v-for="(field, i) in DOCUMENT_SORTS" :key="field.key">
                                <span v-if="i > 0" class="text-gray-400 dark:text-gray-500">/</span>
                                <button @click="toggleSort(field.key as any)" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ field.label }}
                                    <span v-if="sortBy === field.key">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                </button>
                            </template>
                        </div>
                    </th>
                    <th :class="LIST_HEAD">
                        <button @click="toggleSort('particulars')" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Particulars &amp; Remarks
                            <span v-if="sortBy === 'particulars'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th :class="LIST_HEAD">
                        <button @click="toggleSort('source')" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Source &amp; Custodian
                            <span v-if="sortBy === 'source'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th :class="LIST_HEAD" class="min-w-[8rem]">
                        <button @click="toggleSort('status')" class="flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Status
                            <span v-if="sortBy === 'status'">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th :class="LIST_HEAD" class="min-w-[9rem]">Remaining Duration</th>
                    <th class="sticky right-0 z-[2] text-center shadow-[inset_1px_0_0_theme(colors.gray.300)] dark:shadow-[inset_1px_0_0_theme(colors.gray.700)]" :class="LIST_HEAD">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(document, index) in paginatedDocuments"
                    :key="document.id"
                    @click="viewDocumentTransactions(document)"
                    class="cursor-pointer transition-colors [&>td]:border-t [&>td]:border-t-gray-200 dark:[&>td]:border-t-gray-700"
                    :class="documentRowClass(document, index)"
                >
                    <!-- Document: tracking no., type and date -->
                    <td class="sticky left-0 z-[1] bg-inherit px-3 py-2.5 align-top">
                        <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-100 break-all">{{ document.tracking_no }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span v-if="document.document_type" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold" :class="documentTypeStyle(document.document_type).classes">
                                <i :class="[documentTypeStyle(document.document_type).icon, 'text-[10px]']"></i>{{ document.document_type }}
                            </span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <i class="far fa-calendar text-[10px] mr-0.5"></i>{{ new Date(document.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                            </span>
                        </div>
                    </td>

                    <!-- Particulars and remarks -->
                    <td class="px-3 py-2.5 align-top max-w-md">
                        <p v-if="document.particulars" class="text-gray-800 dark:text-gray-200 break-words line-clamp-2" :title="document.particulars">{{ document.particulars }}</p>
                        <p v-if="document.remarks" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400 break-words" style="white-space: pre-wrap;">
                            <i class="fas fa-comment-dots text-[10px] mr-0.5"></i>{{ document.remarks }}
                        </p>
                        <span v-if="!document.particulars && !document.remarks" class="text-gray-400">-</span>
                    </td>

                    <!-- Source and custodian -->
                    <td class="px-3 py-2.5 align-top min-w-[12rem]">
                        <div class="flex items-start gap-1.5 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-building text-[10px] text-gray-400 mt-0.5" title="Source"></i>
                            <span class="break-words">{{ document.source || '—' }}</span>
                        </div>
                        <div class="mt-1 flex items-start gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                            <i class="fas fa-user-tag text-[10px] mt-0.5" title="Custodian"></i>
                            <span class="break-words">{{ getCustodianDisplay(document) }}</span>
                        </div>
                    </td>

                    <!-- Status -->
                    <td class="px-3 py-2.5 align-top">
                        <span :class="['inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap', statusBadge(document).classes]">{{ statusBadge(document).label }}</span>
                    </td>

                    <!-- Remaining duration -->
                    <td class="px-3 py-2.5 align-top">
                        <span class="font-medium" :class="getTimeLeftStyles(document)">{{ getTimeLeftText(document) }}</span>
                    </td>

                    <!-- Actions -->
                    <td class="sticky right-0 z-[1] hover:z-[3] focus-within:z-[3] bg-inherit px-3 py-2.5 align-top text-center shadow-[inset_1px_0_0_theme(colors.gray.200)] dark:shadow-[inset_1px_0_0_theme(colors.gray.700)]">
                        <div class="flex flex-wrap items-center justify-center gap-1.5">
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
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import RowIconButton from '@/Components/RowIconButton.vue';

// The list view (document pinned on the left, actions pinned on the right)
const {
    LIST_HEAD, DOCUMENT_SORTS, documentTypeStyle, hasPermission, sortBy, sortOrder, currentUser,
    getTimeLeftText, getTimeLeftStyles, documentRowClass, statusBadge, paginatedDocuments,
    canReceiveDocument, getCustodianDisplay, toggleSort, handleEditDocument, handleDeleteDocument,
    handleForwardDocument, handleFinalizeDocument, handleReceiveDocument, viewDocumentTransactions,
    viewDocumentChecklist,
} = useDocumentsContext();
</script>
