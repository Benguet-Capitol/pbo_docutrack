<template>
    <!-- Document Checklist Modal: Shows checklist items before saving Annual Budget or Supplemental Budget documents -->
    <Teleport to="body" v-if="showChecklistModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeChecklistModal">
            <div class="relative w-full max-w-7xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp overflow-hidden flex flex-col h-screen sm:h-auto sm:max-h-screen">
                <!-- Modal Header with Close Button -->
                <div class="flex items-center justify-between px-8 py-3 border-b border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
                    <div></div>
                    <button @click="closeChecklistModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <!-- Modal Body: Print-like Form Layout -->
                <div class="flex-1 overflow-y-auto px-8 py-6 bg-gray-100 dark:bg-gray-900">
                    <div v-if="checklistTemplate.items && checklistTemplate.items.length > 0" class="space-y-6 max-w-6xl">
                        <!-- Form Header Section -->
                        <div class="bg-white dark:bg-gray-700 p-6 border border-gray-300 dark:border-gray-600">
                            <!-- Form Number -->
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4">
                                <span v-if="formData.document_type !== 'Budget Proposals'">
                                    {{ formData.document_type === 'Supplemental Budget' ? 'LBR Form No. 1B' : 'LBR Form No. 1A' }}
                                </span>
                            </div>

                            <!-- Title -->
                            <div class="text-center mb-6">
                                <h2 v-if="formData.document_type !== 'Budget Proposals'" class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">CHECKLIST ON DOCUMENTARY AND SIGNATURE REQUIREMENTS</h2>
                                <h2 v-else class="text-base font-bold text-gray-900 dark:text-gray-100 mb-1">CHECKLIST ON DOCUMENTARY REQUIREMENTS</h2>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                    FOR THE {{ formData.document_type === 'Supplemental Budget' ? 'SUPPLEMENTAL BUDGET' : (formData.document_type === 'Budget Proposals' ? 'BUDGET PROPOSAL' : 'ANNUAL BUDGET') }}
                                </h3>
                            </div>

                            <!-- Document Info Section -->
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-700 dark:text-gray-300">Date Received: </span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formData.date ? new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '' }}
                                    </span>
                                </div>
                                
                                <div v-if="formData.document_type !== 'Budget Proposals'">
                                    <span class="text-gray-700 dark:text-gray-300">Municipality: </span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formData.source || '-' }}</span>
                                </div>

                                <div v-if="formData.document_type !== 'Budget Proposals'">
                                    <span class="text-gray-700 dark:text-gray-300">Municipality Class: </span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ municipalities.find((m: any) => m.name === formData.source)?.city_class || '-' }}
                                    </span>
                                </div>

                                <div v-if="formData.document_type === 'Budget Proposals'">
                                    <span class="text-gray-700 dark:text-gray-300">Office: </span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formData.source || '-' }}</span>
                                </div>

                                <div class="border-t border-gray-300 dark:border-gray-600 pt-3">
                                </div>

                                <!-- Validation Error Alert -->
                                <div v-if="checklistValidationError" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg">
                                    <div class="flex gap-3">
                                        <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0 mt-0.5"></i>
                                        <div class="text-sm text-red-800 dark:text-red-300" v-html="checklistValidationError"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist Table (Print-like layout) -->
                        <div class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 overflow-hidden">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-gray-200 dark:bg-gray-600 border-b-2 border-gray-400 dark:border-gray-500">
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-500 w-1/2">DOCUMENT</th>
                                        <th v-if="formData.document_type !== 'Budget Proposals'" class="px-4 py-3 text-left text-xs font-bold text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-500">SIGNATORY</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 dark:text-gray-100">REMARKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(item, index) in checklistData" :key="item.checklist_item_id">
                                        <!-- Main Item Row with Remarks Inline -->
                                        <tr class="border-b border-gray-300 dark:border-gray-500 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100 text-sm">
                                                <div class="flex items-start gap-2">
                                                    <input 
                                                        type="checkbox" 
                                                        v-model="item.is_checked"
                                                        @change="handleDocumentCheckboxChange(item)"
                                                        class="w-4 h-4 cursor-pointer text-blue-600 rounded mt-0.5 flex-shrink-0"
                                                    />
                                                    <div>
                                                        <span v-if="!item.is_subitem" class="font-semibold">{{ item.group_letter }}. </span>
                                                        <span class="font-semibold">{{ item.item_name }}</span>
                                                    </div>
                                                </div>
                                                <!-- Annexes -->
                                                <div v-if="item.annexes && item.annexes.length > 0" class="mt-2 ml-6 space-y-2">
                                                    <div v-for="(annex, annexIndex) in item.annexes" :key="`${item.checklist_item_id}-annex-${annexIndex}`" class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                        <input 
                                                            type="checkbox" 
                                                            v-model="annex.is_checked"
                                                            class="w-4 h-4 cursor-pointer text-green-600 rounded flex-shrink-0"
                                                        />
                                                        <i class="fas fa-file text-green-600"></i>
                                                        <span>{{ annex.name }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td v-if="!shouldSkipSignatoryCell(item) && formData.document_type !== 'Budget Proposals'" :rowspan="isFirstItemOfGroupD(item) ? getGroupDRowspan() : (isFirstItemOfGroupE(item) ? getGroupERowspan() : 1)" class="px-4 py-3 text-gray-900 dark:text-gray-100 text-sm border-r border-gray-300 dark:border-gray-500">
                                                <!-- Signatories with Status Dropdowns -->
                                                <div v-if="(isPartOfGroupD(item) || isPartOfGroupE(item) ? getParentSignatories(item) : item.signatories) && (isPartOfGroupD(item) || isPartOfGroupE(item) ? getParentSignatories(item).length : item.signatories.length) > 0" class="space-y-2">
                                                    <div v-for="(signatory, sigIndex) in (isPartOfGroupD(item) || isPartOfGroupE(item) ? getParentSignatories(item) : item.signatories)" :key="`${item.checklist_item_id}-sig-${sigIndex}`" class="flex items-center gap-2" :class="{ 'opacity-50': !getDocumentItemForSignatories(item).is_checked }">
                                                        <input 
                                                            type="checkbox" 
                                                            v-model="signatory.is_checked"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="w-4 h-4 text-blue-600 rounded flex-shrink-0 disabled:cursor-not-allowed disabled:opacity-50"
                                                            :class="{ 'cursor-pointer': getDocumentItemForSignatories(item).is_checked }"
                                                        />
                                                        <span class="text-xs flex-1">{{ signatory.name }}</span>
                                                        <select 
                                                            v-model="signatory.acting_status"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-1.5 py-0.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 flex-shrink-0 disabled:cursor-not-allowed disabled:opacity-50"
                                                            :class="{ 'cursor-pointer': getDocumentItemForSignatories(item).is_checked }"
                                                        >
                                                            <option value="">Regular</option>
                                                            <option value="Acting">Acting</option>
                                                            <option value="OIC">OIC</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </td>
                                            <td v-if="!shouldSkipRemarksCell(item)" :rowspan="isFirstItemOfGroupC(item) ? getGroupCRowspan() : (isFirstItemOfGroupD(item) ? getGroupDRowspan() : (isFirstItemOfGroupE(item) ? getGroupERowspan() : 1))" class="px-4 py-3 text-gray-900 dark:text-gray-100 text-xs">
                                                <!-- Remarks Fields Inline -->
                                                <div v-if="Object.keys(item.remarks).length > 0 && !shouldHideRemarks(item, index)" class="space-y-2" :class="{ 'opacity-50': !getDocumentItemForSignatories(item).is_checked }">
                                                    <div v-for="(value, label, labelIndex) in item.remarks" :key="`${item.checklist_item_id}-remark-${labelIndex}`" class="flex flex-col gap-1">
                                                        <label class="font-semibold text-gray-600 dark:text-gray-400 text-xs">{{ label }}:</label>
                                                        <!-- Date input for date fields -->
                                                        <input 
                                                            v-if="label.toLowerCase().includes('date') || label.toLowerCase().includes('dated')"
                                                            :value="item.remarks[label as string]"
                                                            @input="(e: Event) => item.remarks[label as string] = (e.target as HTMLInputElement).value"
                                                            type="date"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        />
                                                        <!-- Number input for Amount -->
                                                        <input 
                                                            v-else-if="label.toLowerCase().includes('amount')"
                                                            :value="item.remarks[label as string]"
                                                            @input="(e: Event) => item.remarks[label as string] = (e.target as HTMLInputElement).value"
                                                            type="number"
                                                            placeholder="0.00"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        />
                                                        <!-- Textarea for Details field -->
                                                        <textarea 
                                                            v-else-if="label.toLowerCase().includes('details')"
                                                            :value="item.remarks[label as string]"
                                                            @input="(e: Event) => item.remarks[label as string] = (e.target as HTMLTextAreaElement).value"
                                                            placeholder="Enter details..."
                                                            rows="3"
                                                            style="white-space: pre-wrap; word-break: break-word;"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 resize-none disabled:opacity-50 disabled:cursor-not-allowed"
                                                        />
                                                        <!-- Text input for other fields -->
                                                        <input 
                                                            v-else
                                                            :value="item.remarks[label as string]"
                                                            @input="(e: Event) => item.remarks[label as string] = (e.target as HTMLInputElement).value"
                                                            type="text"
                                                            placeholder="Enter value..."
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        />
                                                    </div>
                                                </div>
                                                <!-- Parent Remarks Inline (for Supplemental Budget group c, d, and e - merged remarks) -->
                                                <div v-else-if="isFirstItemOfGroupC(item) || isFirstItemOfGroupD(item) || isFirstItemOfGroupE(item)" class="space-y-2" :class="{ 'opacity-50': !getDocumentItemForSignatories(item).is_checked }">
                                                    <!-- Only show Details field for merged remarks -->
                                                    <div class="flex flex-col gap-1">
                                                        <label class="font-semibold text-gray-600 dark:text-gray-400 text-xs">Details:</label>
                                                        <textarea 
                                                            :value="item.remarks['Details'] || ''"
                                                            @input="(e: Event) => {
                                                                if (!item.remarks) item.remarks = {};
                                                                item.remarks['Details'] = (e.target as HTMLTextAreaElement).value;
                                                            }"
                                                            placeholder="Enter details..."
                                                            style="white-space: pre-wrap; word-break: break-word;"
                                                            rows="4"
                                                            :disabled="!getDocumentItemForSignatories(item).is_checked"
                                                            class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-blue-500 resize-none disabled:opacity-50 disabled:cursor-not-allowed"
                                                        />
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Checklist Progress Summary -->
                        <div class="bg-white dark:bg-gray-700 p-6 border border-gray-300 dark:border-gray-600">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Checklist Completion Progress</p>
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 w-64 bg-gray-300 dark:bg-gray-600 rounded-full h-3">
                                            <div 
                                                class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all duration-300"
                                                :style="{ width: checklistProgress + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100 min-w-fit">{{ checklistCheckedCount }} / {{ checklistTotalCount }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Checklist State -->
                    <div v-else class="py-12 text-center bg-white dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No checklist template available for this document type</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 sticky bottom-0">
                    <button
                        @click="printChecklist"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-print"></i>
                        Print Checklist
                    </button>
                    <button
                        v-if="editingDocument"
                        @click="saveChecklistChanges"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-save"></i>
                        Save Checklist
                    </button>
                    <button
                        v-else
                        @click="saveDocumentWithChecklist"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-save"></i>
                        Save & Create Document
                    </button>
                    <button
                        @click="closeChecklistModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
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

// The document checklist (create, view, edit, print)
const {
    isFirstItemOfGroupC, isFirstItemOfGroupD, isFirstItemOfGroupE, isPartOfGroupD, isPartOfGroupE,
    editingDocument, municipalities, showChecklistModal, checklistTemplate, checklistData,
    checklistValidationError, formData, getParentSignatories, shouldHideRemarks, getGroupCRowspan,
    getGroupDRowspan, getGroupERowspan, shouldSkipRemarksCell, shouldSkipSignatoryCell,
    getDocumentItemForSignatories, handleDocumentCheckboxChange, closeChecklistModal,
    checklistCheckedCount, checklistTotalCount, checklistProgress, saveDocumentWithChecklist,
    saveChecklistChanges, printChecklist,
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
