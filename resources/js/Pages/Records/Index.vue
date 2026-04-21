<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Records
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    @create-click="handleCreateClick"
                    @update:search-query="handleSearchQuery"
                    @update:items-per-page="handleItemsPerPage"
                />

                <!-- Tab Navigation -->
                <div class="px-6 py-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <!-- Main Record Types -->
                    <div class="flex gap-2 overflow-x-auto border-b border-gray-100 dark:border-gray-700">
                        <button 
                            v-for="type in recordTypes" 
                            :key="type"
                            @click="handleTabChange(type)"
                            :class="[
                                'px-4 py-3 text-xs font-medium whitespace-nowrap border-b-2 transition-colors',
                                activeTab === type
                                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                    : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                            ]"
                        >
                            {{ type }}
                        </button>
                    </div>

                    <!-- Sub-types for selected main type (if available) -->
                    <div v-if="recordTypesHierarchy[activeTab]?.length > 0" class="bg-gray-50 dark:bg-gray-900/30 px-4 py-3">
                        <!-- Button style for types with few subtypes (≤ 8) -->
                        <div v-if="recordTypesHierarchy[activeTab].length <= 8" class="flex gap-1 overflow-x-auto">
                            <button
                                @click="handleSubtypeChange(null)"
                                :class="[
                                    'px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full transition-colors',
                                    activeSubtype === null
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                All
                            </button>
                            <button
                                v-for="subtype in recordTypesHierarchy[activeTab]"
                                :key="subtype"
                                @click="handleSubtypeChange(subtype)"
                                :class="[
                                    'px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full transition-colors',
                                    activeSubtype === subtype
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                {{ subtype }}
                            </button>
                        </div>

                        <!-- Dropdown style for types with many subtypes (> 8) -->
                        <div v-else class="flex gap-2 items-center flex-wrap">
                            <button
                                @click="handleSubtypeChange(null)"
                                :class="[
                                    'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                    activeSubtype === null
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                All
                            </button>

                            <div class="relative">
                                <select 
                                    :value="activeSubtype || ''"
                                    @change="(e) => handleSubtypeChange(e.target.value || null)"
                                    class="appearance-none px-3 py-1 text-xs font-medium rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all width-auto pr-6"
                                >
                                    <option value="">Select a subtype...</option>
                                    <option v-for="subtype in recordTypesHierarchy[activeTab]" :key="subtype" :value="subtype">
                                        {{ subtype }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 pointer-events-none text-xs"></i>
                            </div>

                            <div v-if="activeSubtype" class="flex items-center gap-2 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ activeSubtype }}</span>
                                <button @click="handleSubtypeChange(null)" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState 
                    v-else-if="records.length === 0"
                    title="No records found"
                    message="Get started by creating a new record"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredRecords.length === 0"
                    :title="`No ${activeTab} found`"
                    message="Try adjusting your search or create a new record"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedRecords"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @sort="handleSort"
                    @view="handleViewFile"
                    @download="handleDownloadFile"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && records.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-records="filteredRecords.length"
                    :pagination-range="paginationPages"
                    @page-change="handlePageChange"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            :creating="creating"
            :record-types="recordTypes"
            :record-types-hierarchy="recordTypesHierarchy"
            @update:form-data="formData = $event"
            @close="closeCreateModal"
            @submit="submitCreateForm"
            @file-upload="handleFileUpload"
        />

        <EditModal
            :show="showEditModal"
            :record-to-edit="recordToEdit"
            :form-data="formData"
            :form-errors="formErrors"
            :updating="updating"
            :record-types="recordTypes"
            :record-types-hierarchy="recordTypesHierarchy"
            @update:form-data="formData = $event"
            @close="closeEditModal"
            @submit="submitEditForm"
            @file-upload="handleFileUpload"
        />

        <DeleteModal
            :show="showDeleteModal"
            :record-to-delete="recordToDelete"
            :deleting="deleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />

        <PreviewModal
            :show="showPreviewModal"
            @close="closePreviewModal"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import Pagination from './Partials/Pagination.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import PreviewModal from './Partials/PreviewModal.vue';
import { useRecordsData } from './Composables/useRecordsData';
import { useRecordsForm } from './Composables/useRecordsForm';
import type { Record } from './Composables/useRecordsData';

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Composables ==============
const dataComposable = useRecordsData();
const formComposable = useRecordsForm(dataComposable);

// ============== Expose State from Composables ==============
// Data Management
const records = dataComposable.records;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const loading = dataComposable.loading;
const error = dataComposable.error;
const activeTab = dataComposable.activeTab;
const activeSubtype = dataComposable.activeSubtype;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const recordTypesHierarchy = dataComposable.recordTypesHierarchy;
const recordTypes = dataComposable.recordTypes;
const filteredRecords = dataComposable.filteredRecords;
const totalPages = dataComposable.totalPages;
const paginatedRecords = dataComposable.paginatedRecords;
const paginationPages = dataComposable.paginationPages;

// Form Management
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const showPreviewModal = formComposable.showPreviewModal;
const creating = formComposable.creating;
const updating = formComposable.updating;
const deleting = formComposable.deleting;
const recordToEdit = formComposable.recordToEdit;
const recordToDelete = formComposable.recordToDelete;
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;

// ============== Event Handlers ==============
const handleTabChange = (type: string) => {
    activeTab.value = type;
    activeSubtype.value = null;
    currentPage.value = 1;
    searchQuery.value = '';
};

const handleSubtypeChange = (subtype: string | null) => {
    activeSubtype.value = subtype;
    currentPage.value = 1;
    searchQuery.value = '';
};

const handleSearchQuery = (query: string) => {
    searchQuery.value = query;
    currentPage.value = 1;
};

const handleItemsPerPage = (count: number) => {
    itemsPerPage.value = count;
    currentPage.value = 1;
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
};

const handleSort = (field: string) => {
    dataComposable.toggleSort(field as any);
};

const handleCreateClick = async () => {
    await formComposable.openCreateModal(recordTypesHierarchy.value, activeTab.value);
};

const closeCreateModal = () => {
    formComposable.closeCreateModal();
};

const submitCreateForm = async () => {
    await formComposable.submitCreateForm(toastRef, dataComposable.fetchRecords);
};

const handleEditClick = (record: Record) => {
    formComposable.handleEditRecord(record);
};

const closeEditModal = () => {
    formComposable.closeEditModal();
};

const submitEditForm = async () => {
    await formComposable.submitEditForm(toastRef, dataComposable.fetchRecords);
};

const handleDeleteClick = (record: Record) => {
    formComposable.openDeleteModal(record);
};

const closeDeleteModal = () => {
    formComposable.closeDeleteModal();
};

const confirmDelete = async () => {
    await formComposable.confirmDelete(toastRef, dataComposable.fetchRecords);
};

const handleFileUpload = (event: Event) => {
    formComposable.handleFileUpload(event);
};

const handleViewFile = (record: Record) => {
    formComposable.viewFile(record);
};

const handleDownloadFile = (record: Record) => {
    formComposable.downloadFile(record);
};

const closePreviewModal = () => {
    showPreviewModal.value = false;
};

// ============== Lifecycle ==============
onMounted(() => {
    dataComposable.fetchRecords();
});
</script>
