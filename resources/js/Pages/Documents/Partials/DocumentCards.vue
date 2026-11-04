<template>
    <!-- Card View: used on small screens, or when chosen on desktop. Clicking a card opens its transactions -->
    <div class="p-4">
        <div :class="CARD_GRID">
            <RecordCard
                v-for="document in paginatedDocuments"
                :key="document.id"
                class="cursor-pointer"
                :accent="statusBadge(document).accent"
                :icon="document.document_type ? documentTypeStyle(document.document_type).icon : 'fas fa-file-alt'"
                :title="document.tracking_no"
                :date="new Date(document.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"
                :selected="showTransactionsModal && documentViewingTransactions?.id === document.id"
                @click="viewDocumentTransactions(document)"
            >
                <template #aside>
                    <span :class="[LIST_TAG, 'rounded', statusBadge(document).classes]">{{ statusBadge(document).label }}</span>
                </template>
                <p :class="[CARD_SUBJECT, 'line-clamp-3']" :title="document.particulars">{{ document.particulars || '—' }}</p>
                <div v-if="document.document_type" :class="CARD_TAGS">
                    <span :class="[LIST_TAG, 'rounded', documentTypeStyle(document.document_type).classes]"><i :class="[documentTypeStyle(document.document_type).icon, 'text-[10px]']"></i>{{ document.document_type }}</span>
                </div>
                <CardField v-if="document.remarks" label="Remarks" icon="fas fa-comment-dots">
                    <p style="white-space: pre-wrap;">{{ document.remarks }}</p>
                </CardField>
                <div :class="[CARD_STATS, 'mt-auto grid-cols-2']">
                    <CardField label="Source" icon="fas fa-landmark" :value="document.source" />
                    <CardField label="Custodian" icon="fas fa-user" :value="getCustodianDisplay(document)" />
                </div>

                <template #meta>
                    <div class="font-medium" :class="getTimeLeftStyles(document)" title="Remaining Duration">
                        {{ getTimeLeftText(document) }}
                    </div>
                </template>
                <template #actions>
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
                </template>
            </RecordCard>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_STATS, CARD_SUBJECT, CARD_TAGS } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';

// The card view (small screens, or when chosen)
const {
    documentTypeStyle, hasPermission, currentUser, showTransactionsModal, documentViewingTransactions,
    getTimeLeftText, getTimeLeftStyles, statusBadge, paginatedDocuments, canReceiveDocument,
    getCustodianDisplay, handleEditDocument, handleDeleteDocument, handleForwardDocument,
    handleFinalizeDocument, handleReceiveDocument, viewDocumentTransactions, viewDocumentChecklist,
} = useDocumentsContext();
</script>
