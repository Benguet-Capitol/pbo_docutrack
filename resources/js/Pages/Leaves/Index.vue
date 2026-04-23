<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Leaves</h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="openCreateModal"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState 
                    v-else-if="leaves.length === 0"
                    title="No records found"
                    message="Get started by creating a new leave"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredLeaves.length === 0"
                    title="No Leaves found"
                    message="Try adjusting your search or create a new leave"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedLeaves"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                    @sort="toggleSort"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && !error && leaves.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :filtered-count="filteredLeaves.length"
                    :pagination-range="paginationRange"
                    @page-change="currentPage = $event"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show="showCreateModal"
            :form-data="(formData as any)"
            :form-errors="formErrors"
            :employees="sortedEmployees"
            :loading="creating"
            :leave-types="leaveTypes"
            @close="closeCreateModal"
            @confirm="createLeave"
        />

        <EditModal
            :show="showEditModal"
            :form-data="(formData as any)"
            :form-errors="formErrors"
            :employees="sortedEmployees"
            :loading="updating"
            :leave-types="leaveTypes"
            @close="closeEditModal"
            @confirm="updateLeave"
        />

        <DeleteModal
            :show="showDeleteModal"
            :record="currentDeleteRecord"
            :deleting="deleting"
            @close="handleCloseDeleteModal"
            @confirm="deleteLeave"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useLeavesData } from './Composables/useLeavesData';
import { useLeavesForm } from './Composables/useLeavesForm';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import Pagination from './Partials/Pagination.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';

// Data composable
const {
    leaves,
    employees,
    searchQuery,
    currentPage,
    itemsPerPage,
    sortBy,
    sortOrder,
    loading,
    error,
    filteredLeaves,
    sortedEmployees,
    paginatedLeaves,
    totalPages,
    paginationRange,
    fetchLeaves,
    fetchEmployees,
    toggleSort,
} = useLeavesData();

// Toast ref
const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// Toast function
const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    if (type === 'success') {
        toastRef.value?.add('success', 'Success', message, 3000);
    } else if (type === 'error') {
        toastRef.value?.add('error', 'Error', message, 4000);
    } else {
        toastRef.value?.add('info', 'Info', message, 3000);
    }
};

// Form composable
const {
    showCreateModal,
    showEditModal,
    showDeleteModal,
    creating,
    updating,
    deleting,
    formData,
    formErrors,
    openCreateModal: _openCreateModal,
    closeCreateModal,
    openEditModal: _openEditModal,
    closeEditModal,
    openDeleteModal: _openDeleteModal,
    closeDeleteModal,
    createLeave: _createLeave,
    updateLeave: _updateLeave,
    deleteLeave: _deleteLeave,
} = useLeavesForm(leaves, fetchLeaves, showToast);

const leaveTypes = [
    'Vacation Leave',
    'Mandatory/Forced Leave',
    'Sick Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Special Privilege Leave',
    'Study Leave',
    '10-Day VAWC Leave',
    'Rehabilitation Leave',
    'Special Leave Benefits for Women',
    'Special Emergency (Calamity) Leave',
    'Adoption Leave',
    'Wellness Leave',
    'Others'
];

// Track the current record being deleted
const currentDeleteRecord = ref<any>(null);

// Wrapper methods to handle modal opening and data refresh
const openCreateModal = () => {
    _openCreateModal();
};

const openEditModal = (leave: any) => {
    _openEditModal(leave);
};

const openDeleteModal = (leave: any) => {
    currentDeleteRecord.value = leave;
    _openDeleteModal(leave);
};

const createLeave = async () => {
    await _createLeave();
};

const updateLeave = async () => {
    await _updateLeave();
};

const deleteLeave = async () => {
    await _deleteLeave();
    currentDeleteRecord.value = null;
};

const handleCloseDeleteModal = () => {
    currentDeleteRecord.value = null;
    closeDeleteModal();
};

// Initial data fetch
onMounted(() => {
    fetchLeaves();
    fetchEmployees();
});
</script>
