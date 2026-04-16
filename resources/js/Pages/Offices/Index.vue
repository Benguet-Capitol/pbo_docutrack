<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Offices
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    :can-create-offices="canCreateOffices"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="handleCreateClick"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="!loading && offices.length === 0 && !searchQuery" />

                <!-- Empty Search Results -->
                <div v-else-if="paginatedOffices.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                    <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">No offices found matching your search criteria.</p>
                </div>

                <!-- Office Table -->
                <Table
                    v-else
                    :offices="paginatedOffices"
                    :can-edit-offices="canEditOffices"
                    :can-delete-offices="canDeleteOffices"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @sort="handleSort"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="!loading && offices.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="filteredOffices.length"
                    @page-change="handleChangePage"
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
            :office-to-delete="officeToDelete"
            @close="handleCloseDeleteModal"
            @confirm="handleConfirmDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useOfficeData, type Office } from './Composables/useOfficeData';
import { useOfficeForm } from './Composables/useOfficeForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import Pagination from './Partials/Pagination.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';

// ============== Toast Reference ==============
const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Composables ==============
const dataComposable = useOfficeData();
const formComposable = useOfficeForm();

// ============== Expose State from Composables ==============
// Data Management
const offices = dataComposable.offices;
const loading = dataComposable.loading;
const error = dataComposable.error;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const filteredOffices = dataComposable.filteredOffices;
const totalPages = dataComposable.totalPages;
const paginatedOffices = dataComposable.paginatedOffices;

// Form Management
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const editingOffice = formComposable.editingOffice;
const officeToDelete = formComposable.officeToDelete;
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;
const canCreateOffices = formComposable.canCreateOffices;
const canEditOffices = formComposable.canEditOffices;
const canDeleteOffices = formComposable.canDeleteOffices;

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
const handleChangePage = (page: number) => {
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
 * @param {Office} office - The office to edit
 */
const handleEditClick = (office: any) => {
    formComposable.openEditModal(office);
};

/**
 * handleCloseEditModal: Handle edit modal close
 */
const handleCloseEditModal = () => {
    formComposable.closeEditModal();
};

/**
 * handleDeleteClick: Handle delete button click
 * @param {Office} office - The office to delete
 */
const handleDeleteClick = (office: any) => {
    formComposable.openDeleteModal(office);
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
        const newOffice = await formComposable.createOffice();
        if (newOffice) {
            dataComposable.addOffice(newOffice);
            formComposable.closeCreateModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `Office: <strong>${newOffice.office_name}</strong> has been created successfully!`,
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
    if (!editingOffice.value) return;

    try {
        const updatedOffice = await formComposable.updateOffice(editingOffice.value.id);
        if (updatedOffice) {
            dataComposable.updateOffice(editingOffice.value.id, updatedOffice);
            formComposable.closeEditModal();
            
            toastRef.value?.add(
                'info',
                'Success',
                `Office: <strong>${updatedOffice.office_name}</strong> has been updated successfully!`,
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
    if (!officeToDelete.value) return;

    const deletingOffice = officeToDelete.value;

    try {
        await formComposable.deleteOffice(deletingOffice.id);
        dataComposable.deleteOffice(deletingOffice.id);
        formComposable.closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `Office: <strong>${deletingOffice.office_name}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

// ============== Lifecycle Hooks ==============

/**
 * Fetch offices data on component mount
 */
onMounted(() => {
    dataComposable.fetchOffices();
});
</script>

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
