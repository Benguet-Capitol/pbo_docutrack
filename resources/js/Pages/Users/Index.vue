<template>
    <Toast ref="toastRef" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Users
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    :can-manage-users="canManageUsers"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="handleCreateClick"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="!loading && users.length === 0 && !searchQuery" />

                <!-- Empty Search Results -->
                <div v-else-if="paginatedUsers.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                    <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">No users found matching your search criteria.</p>
                </div>

                <!-- User Table -->
                <Table
                    v-else
                    :users="paginatedUsers"
                    :can-manage-users="canManageUsers"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @sort="handleSort"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="!loading && users.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="filteredUsers.length"
                    @page-change="handleChangePage"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show-create-modal="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            :offices="offices"
            :employees="employees"
            @close="handleCloseCreateModal"
            @save="handleSaveCreate"
            @employee-change="handleEmployeeChange"
        />

        <EditModal
            :show-edit-modal="showEditModal"
            :form-data="formData"
            :form-errors="formErrors"
            :offices="offices"
            @close="handleCloseEditModal"
            @save="handleSaveEdit"
        />

        <DeleteModal
            :show-delete-modal="showDeleteModal"
            :user-to-delete="userToDelete"
            @close="handleCloseDeleteModal"
            @confirm="handleConfirmDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useUserData, type User } from './Composables/useUserData';
import { useUserForm } from './Composables/useUserForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
const dataComposable = useUserData();
const formComposable = useUserForm(dataComposable.employees);

// ============== Expose State from Composables ==============
// Data Management
const users = dataComposable.users;
const employees = dataComposable.employees;
const offices = dataComposable.offices;
const loading = dataComposable.loading;
const error = dataComposable.error;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const filteredUsers = dataComposable.filteredUsers;
const totalPages = dataComposable.totalPages;
const paginatedUsers = dataComposable.paginatedUsers;

// Form Management
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const editingUser = formComposable.editingUser;
const userToDelete = formComposable.userToDelete;
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;
const canManageUsers = formComposable.canManageUsers;

// ============== Event Handlers ==============

/**
 * handleSort: Handle sort changes from table column headers
 */
const handleSort = (field: string) => {
    dataComposable.toggleSort(field as any);
};

/**
 * handleChangePage: Handle pagination changes
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
 */
const handleEditClick = (user: any) => {
    formComposable.openEditModal(user);
};

/**
 * handleCloseEditModal: Handle edit modal close
 */
const handleCloseEditModal = () => {
    formComposable.closeEditModal();
};

/**
 * handleDeleteClick: Handle delete button click
 */
const handleDeleteClick = (user: any) => {
    formComposable.openDeleteModal(user);
};

/**
 * handleCloseDeleteModal: Handle delete modal close
 */
const handleCloseDeleteModal = () => {
    formComposable.closeDeleteModal();
};

/**
 * handleEmployeeChange: Handle employee selection change
 */
const handleEmployeeChange = () => {
    const selectedEmpId = Number(formData.value.employee_id);
    const emp = dataComposable.getEmployeeById(selectedEmpId);
    
    if (emp) {
        formData.value.name = emp.name;
        formData.value.office = Number(emp.office_id);
    }
};

/**
 * handleSaveCreate: Handle create form submission
 */
const handleSaveCreate = async () => {
    try {
        const newUser = await formComposable.createUser();
        if (newUser) {
            dataComposable.addUser(newUser);
            formComposable.closeCreateModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `User: <strong>${newUser.username}</strong> has been created successfully!`,
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
    if (!editingUser.value) return;

    try {
        const updatedUser = await formComposable.updateUser(editingUser.value.id);
        if (updatedUser) {
            dataComposable.updateUser(editingUser.value.id, updatedUser);
            formComposable.closeEditModal();
            
            toastRef.value?.add(
                'info',
                'Success',
                `User: <strong>${updatedUser.username}</strong> has been updated successfully!`,
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
    if (!userToDelete.value) return;

    const deletingUser = userToDelete.value;

    try {
        await formComposable.deleteUser(deletingUser.id);
        dataComposable.deleteUser(deletingUser.id);
        formComposable.closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `User: <strong>${deletingUser.username}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};
</script>
