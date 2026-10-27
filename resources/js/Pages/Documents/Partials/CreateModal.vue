<template>
    <!-- Create Document Modal: Teleports to body when showCreateModal is true -->
    <!-- @click.self="closeCreateModal" closes modal when clicking outside the dialog -->
    <Teleport to="body" v-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header: Shows title and close button -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file text-emerald-600 dark:text-emerald-400"></i>
                        Create Document
                    </h3>
                    <!-- Close Button: Calls closeCreateModal() when clicked -->
                    <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body: Contains form inputs for creating a new document -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Tracking No Field: Auto-generated as YYYY-MM-NNNN -->
                        <div class="space-y-2">
                            <label for="tracking_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tracking No <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.tracking_no"
                                    id="tracking_no"
                                    type="text"
                                    placeholder="Auto-generated"
                                    disabled
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300 focus:border-gray-300"
                                />
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generated based on current date</p>
                            <span v-if="formErrors.tracking_no" class="text-red-500 text-xs">{{ formErrors.tracking_no }}</span>
                        </div>

                        <!-- Date Field: Set to current date, max is current date -->
                        <div class="space-y-2">
                            <label for="date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.date"
                                    id="date"
                                    type="date"
                                    :max="todayDate"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                        </div>

                        <!-- Document Type Field: Select from predefined list -->
                        <div class="space-y-2">
                            <label for="document_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Document Type <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-file-alt absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.document_type"
                                    id="document_type"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option value="">Select Document Type</option>
                                    <option value="Annual Budget">Annual Budget</option>
                                    <option value="Supplemental Budget">Supplemental Budget</option>
                                    <option value="Budget Proposals">Budget Proposals</option>
                                    <optgroup label="Referral">
                                        <option value="Referral - Simple">Referral - Simple</option>
                                        <option value="Referral - Complex">Referral - Complex</option>
                                        <option value="Referral - Highly Technical">Referral - Highly Technical</option>
                                    </optgroup>
                                </select>
                            </div>
                            <span v-if="formErrors.document_type" class="text-red-500 text-xs">{{ formErrors.document_type }}</span>
                        </div>

                        <!-- Source Type Field: Choose between Internal and External -->
                        <div class="space-y-2">
                            <label for="source_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source Type</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-network-wired absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.sourceType"
                                    id="source_type"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option value="internal">Internal (Offices)</option>
                                    <option value="external">External (Municipalities)</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>
                        </div>

                        <!-- Source Field: Select from Offices or Municipalities based on sourceType, or text input for Others -->
                        <div class="space-y-2">
                            <label for="source" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source <span class="text-red-600">*</span></label>
                            <!-- Dropdown for Internal and External -->
                            <div v-if="formData.sourceType !== 'others'" class="relative flex items-center">
                                <i class="fas fa-link absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.source"
                                    id="source"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option value="">Select Source</option>
                                    <option v-if="formData.sourceType === 'internal'" v-for="office in offices" :key="office.id" :value="office.office_name">
                                        {{ office.office_name }}
                                    </option>
                                    <option v-if="formData.sourceType === 'external'" v-for="municipality in municipalities" :key="municipality.id" :value="municipality.name">
                                        {{ municipality.name }}
                                    </option>
                                </select>
                            </div>
                            <!-- Text input for Others -->
                            <div v-if="formData.sourceType === 'others'" class="relative flex items-center">
                                <i class="fas fa-edit absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.source"
                                    id="source"
                                    type="text"
                                    placeholder="Enter source"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.source" class="text-red-500 text-xs">{{ formErrors.source }}</span>
                        </div>

                        <!-- SB No. Field: Only visible for Supplemental Budget documents -->
                        <div v-if="formData.document_type === 'Supplemental Budget'" class="space-y-2">
                            <label for="sb_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">SB No.</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-file-invoice-dollar absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.sb_no"
                                    id="sb_no"
                                    type="text"
                                    placeholder="Enter Supplemental Budget Number"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Particulars Field: Required -->
                        <div class="space-y-2">
                            <label for="particulars" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Particulars <span class="text-red-600">*</span></label>
                            <div class="relative flex items-start">
                                <i class="fas fa-pen-fancy absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea
                                    v-model="formData.particulars"
                                    id="particulars"
                                    placeholder="Document Particulars"
                                    rows="4"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                                ></textarea>
                            </div>
                            <span v-if="formErrors.particulars" class="text-red-500 text-xs">{{ formErrors.particulars }}</span>
                        </div>

                        <!-- Remarks Field: Optional -->
                        <div class="space-y-2">
                            <label for="remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                            <div class="relative flex items-start">
                                <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea
                                    v-model="formData.remarks"
                                    id="remarks"
                                    placeholder="Additional remarks..."
                                    rows="3"
                                    style="white-space: pre-wrap; word-break: break-word;"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="handleCreateDocument"
                        :disabled="missingDocumentFields.length > 0"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i :class="documentTypeHasChecklist ? 'fas fa-list-check' : 'fas fa-save'"></i>
                        {{ documentTypeHasChecklist ? 'Continue to Checklisting' : 'Save' }}
                    </button>
                    <!-- Cancel Button: Calls closeCreateModal() to close the form -->
                    <button
                        @click="closeCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <MissingFieldsHint :fields="missingDocumentFields" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';

// Create a document
const {
    showCreateModal, offices, municipalities, formData, formErrors, todayDate, documentTypeHasChecklist,
    closeCreateModal, missingDocumentFields, handleCreateDocument,
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
