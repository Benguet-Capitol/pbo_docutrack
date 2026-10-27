<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Documents
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <Header />

                <StatusTabs />

                <!-- Loading State: v-if shows spinner while data is being fetched -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading documents...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State: v-else-if displays error message if fetch fails -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Documents</h3>
                            <!-- Displays the error message from error ref -->
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State: v-else-if shows when no documents exist -->
                <div v-else-if="documents.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No documents found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new document</p>
                </div>

                <DocumentsTable v-else-if="viewMode === 'table'" />
                <DocumentCards v-else />

                <DocumentsPagination />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal />
        <EditModal />
        <DeleteModal />
        <ForwardModal />
        <FinalizeModal />
        <ReceiveModal />
        <TransactionsModal />
        <ChecklistModal />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { provide } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import { DOCUMENTS_PAGE, useDocumentsPage } from './Documents/Composables/useDocumentsPage';
import Header from './Documents/Partials/Header.vue';
import StatusTabs from './Documents/Partials/StatusTabs.vue';
import DocumentsTable from './Documents/Partials/DocumentsTable.vue';
import DocumentCards from './Documents/Partials/DocumentCards.vue';
import DocumentsPagination from './Documents/Partials/DocumentsPagination.vue';
import CreateModal from './Documents/Partials/CreateModal.vue';
import EditModal from './Documents/Partials/EditModal.vue';
import DeleteModal from './Documents/Partials/DeleteModal.vue';
import ForwardModal from './Documents/Partials/ForwardModal.vue';
import FinalizeModal from './Documents/Partials/FinalizeModal.vue';
import ReceiveModal from './Documents/Partials/ReceiveModal.vue';
import TransactionsModal from './Documents/Partials/TransactionsModal.vue';
import ChecklistModal from './Documents/Partials/ChecklistModal.vue';

/**
 * Documents: the page's logic is in ./Documents/Composables (state, list, actions, checklist) and its sections in
 * ./Documents/Partials; they share one page context (useDocumentsPage, provided here).
 */
const page = useDocumentsPage();
provide(DOCUMENTS_PAGE, page);

const { toastRef, viewMode, documents, loading, error } = page;
</script>
