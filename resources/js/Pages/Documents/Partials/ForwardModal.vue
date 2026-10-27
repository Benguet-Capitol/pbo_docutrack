<template>
    <!-- Forward Document Modal: Modal for forwarding document to User, Office, or Municipality -->
    <Teleport to="body" v-if="showForwardModal && documentToForward">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeForwardModal">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-share-alt text-blue-600 dark:text-blue-400"></i>
                        Forward Document
                    </h3>
                    <button @click="closeForwardModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-4">
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        Forwarding document: <span class="font-semibold">{{ documentToForward.tracking_no }}</span>
                    </p>

                    <!-- Forward To Type Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Forward To Type</label>
                        <div class="flex gap-2">
                            <button
                                @click="forwardData.forward_to_type = 'user'; forwardData.forward_to_id = null;"
                                :class="forwardData.forward_to_type === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                :disabled="false"
                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-user mr-1"></i>User
                            </button>
                            <button
                                @click="selectForwardOffice"
                                :class="forwardData.forward_to_type === 'office' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                :disabled="forwardDocumentSourceType === 'municipality'"
                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-building mr-1"></i>Office
                            </button>
                            <button
                                @click="selectForwardMunicipality"
                                :class="forwardData.forward_to_type === 'municipality' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                :disabled="forwardDocumentSourceType === 'office'"
                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-map mr-1"></i>Municipality
                            </button>
                        </div>
                    </div>

                    <!-- User Selection -->
                    <div v-if="forwardData.forward_to_type === 'user'" class="space-y-2">
                        <label for="forward_user_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select User</label>
                        <div class="relative flex items-center">
                            <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm"></i>
                            <select
                                v-model.number="forwardData.forward_to_id"
                                id="forward_user_id"
                                class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                            >
                                <option :value="null">Select User</option>
                                <option v-for="user in availableUsersForForward" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                        <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                    </div>

                    <!-- Office Selection -->
                    <div v-if="forwardData.forward_to_type === 'office'" class="space-y-2">
                        <label for="forward_office_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select Office</label>
                        <div class="relative flex items-center">
                            <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                            <select
                                v-model.number="forwardData.forward_to_id"
                                id="forward_office_id"
                                class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                            >
                                <option :value="null">Select Office</option>
                                <option v-for="office in offices" :key="office.id" :value="office.id">
                                    {{ office.office_name }}
                                </option>
                            </select>
                        </div>
                        <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                    </div>

                    <!-- Municipality Selection -->
                    <div v-if="forwardData.forward_to_type === 'municipality'" class="space-y-2">
                        <label for="forward_municipality_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select Municipality</label>
                        <div class="relative flex items-center">
                            <i class="fas fa-map absolute left-3 text-gray-400 text-sm"></i>
                            <select
                                v-model.number="forwardData.forward_to_id"
                                id="forward_municipality_id"
                                class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                            >
                                <option :value="null">Select Municipality</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                    {{ municipality.name }}
                                </option>
                            </select>
                        </div>
                        <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                    </div>

                    <!-- Remarks: Optional -->
                    <div class="space-y-2">
                        <label for="forward_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                        <div class="relative flex items-start">
                            <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                            <textarea
                                v-model="forwardData.remarks"
                                id="forward_remarks"
                                placeholder="Add remarks for forwarding..."
                                rows="3"
                                style="white-space: pre-wrap; word-break: break-word;"
                                class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div v-if="forwardErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-xs text-red-600 dark:text-red-400">{{ forwardErrors.submit }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="handleSubmitForward"
                        :disabled="!forwardData.forward_to_id"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-check"></i>
                        Forward
                    </button>
                    <button
                        @click="closeForwardModal"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <MissingFieldsHint :fields="forwardData.forward_to_id ? [] : ['Recipient']" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';

// Forward a document to a user, office or municipality
const {
    showForwardModal, documentToForward, forwardData, forwardErrors, offices, municipalities,
    forwardDocumentSourceType, availableUsersForForward, selectForwardOffice, selectForwardMunicipality,
    closeForwardModal, handleSubmitForward,
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
