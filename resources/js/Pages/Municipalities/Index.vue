<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Municipalities
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    :can-create-municipalities="canCreateMunicipalities"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="handleCreateClick"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="municipalities.length === 0" />

                <!-- Data Table -->
                <Table
                    v-else
                    :municipalities="paginatedMunicipalities"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    :can-edit-municipalities="canEditMunicipalities"
                    :can-delete-municipalities="canDeleteMunicipalities"
                    @sort="handleSort"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="!loading && municipalities.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="filteredMunicipalities.length"
                    @page-change="changePage"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show-create-modal="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            @close="handleCloseCreateModal"
            @save="handleSaveCreate"
        />

        <EditModal
            :show-edit-modal="showEditModal"
            :form-data="formData"
            :form-errors="formErrors"
            @close="handleCloseEditModal"
            @save="handleSaveEdit"
        />

        <DeleteModal
            :show-delete-modal="showDeleteModal"
            :municipality-to-delete="municipalityToDelete"
            @close="handleCloseDeleteModal"
            @confirm="handleConfirmDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import Pagination from './Partials/Pagination.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import { ref } from 'vue';
import { useMunicipalityData } from './Composables/useMunicipalityData';
import { useMunicipalityForm } from './Composables/useMunicipalityForm';

// ============== Composables ==============

const dataComposable = useMunicipalityData();
const formComposable = useMunicipalityForm();

// ============== Toast Reference ==============

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Expose State & Methods ==============

// Data Management
const municipalities = dataComposable.municipalities;
const loading = dataComposable.loading;
const error = dataComposable.error;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const filteredMunicipalities = dataComposable.filteredMunicipalities;
const totalPages = dataComposable.totalPages;
const paginatedMunicipalities = dataComposable.paginatedMunicipalities;

// Form Management
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const editingMunicipality = formComposable.editingMunicipality;
const municipalityToDelete = formComposable.municipalityToDelete;
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;
const canCreateMunicipalities = formComposable.canCreateMunicipalities;
const canEditMunicipalities = formComposable.canEditMunicipalities;
const canDeleteMunicipalities = formComposable.canDeleteMunicipalities;

// ============== Event Handlers ==============

/**
 * handleSort: Handle sort changes from table column headers
 * @param {string} field - The field to sort by
 */
const handleSort = (field: string) => {
    dataComposable.toggleSort(field as any);
};

/**
 * changePage: Handle pagination changes
 * @param {number} page - The page to navigate to
 */
const changePage = (page: number) => {
    dataComposable.changePage(page);
};

/**
 * handleCreateClick: Handle create button click
 */
const handleCreateClick = () => {
    formComposable.openCreateModal();
};

/**
 * handleCloseCreateModal: Handle create modal close
 */
const handleCloseCreateModal = () => {
    formComposable.closeCreateModal();
};

/**
 * handleEditClick: Handle edit button click
 * @param {Municipality} municipality - The municipality to edit
 */
const handleEditClick = (municipality: any) => {
    formComposable.openEditModal(municipality);
};

/**
 * handleCloseEditModal: Handle edit modal close
 */
const handleCloseEditModal = () => {
    formComposable.closeEditModal();
};

/**
 * handleDeleteClick: Handle delete button click
 * @param {Municipality} municipality - The municipality to delete
 */
const handleDeleteClick = (municipality: any) => {
    formComposable.openDeleteModal(municipality);
};

/**
 * handleCloseDeleteModal: Handle delete modal close
 */
const handleCloseDeleteModal = () => {
    formComposable.closeDeleteModal();
};

/**
 * handleSaveCreate: Handle create form submission
 */
const handleSaveCreate = async () => {
    try {
        const newMunicipality = await formComposable.createMunicipality();
        if (newMunicipality) {
            dataComposable.addMunicipality(newMunicipality);
            formComposable.closeCreateModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `Municipality: <strong>${newMunicipality.name}</strong> has been created successfully!`,
                3000
            );
        }
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleSaveEdit: Handle edit form submission
 */
const handleSaveEdit = async () => {
    if (!editingMunicipality.value) return;

    try {
        const updatedMunicipality = await formComposable.updateMunicipality(editingMunicipality.value.id);
        if (updatedMunicipality) {
            dataComposable.updateMunicipality(editingMunicipality.value.id, updatedMunicipality);
            formComposable.closeEditModal();
            
            toastRef.value?.add(
                'info',
                'Success',
                `Municipality: <strong>${updatedMunicipality.name}</strong> has been updated successfully!`,
                3000
            );
        }
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleConfirmDelete: Handle delete confirmation
 */
const handleConfirmDelete = async () => {
    if (!municipalityToDelete.value) return;

    const deletingMunicipality = municipalityToDelete.value;

    try {
        await formComposable.deleteMunicipality(deletingMunicipality.id);
        dataComposable.deleteMunicipality(deletingMunicipality.id);
        formComposable.closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `Municipality: <strong>${deletingMunicipality.name}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};
</script>
