<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Employees
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    :can-create-edit-employees="canCreateEditEmployees"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="handleCreateClick"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="!loading && employees.length === 0 && !searchQuery" />

                <!-- Empty Search Results -->
                <div v-else-if="paginatedEmployees.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                    <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">No employees found matching your search criteria.</p>
                </div>

                <!-- Employee Table -->
                <Table
                    v-else
                    :employees="paginatedEmployees"
                    :can-edit-employees="canCreateEditEmployees"
                    :can-delete-employees="canDeleteEmployees"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    :get-office-name="getOfficeNameById"
                    @sort="handleSort"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="!loading && employees.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="filteredEmployees.length"
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
            @close="handleCloseCreateModal"
            @save="handleSaveCreate"
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
            :employee-to-delete="employeeToDelete"
            @close="handleCloseDeleteModal"
            @confirm="handleConfirmDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useEmployeeData, type Employee } from './Composables/useEmployeeData';
import { useEmployeeForm } from './Composables/useEmployeeForm';
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
const dataComposable = useEmployeeData();
const formComposable = useEmployeeForm();

// ============== Expose State from Composables ==============
// Data Management
const employees = dataComposable.employees;
const offices = dataComposable.offices;
const loading = dataComposable.loading;
const error = dataComposable.error;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const filteredEmployees = dataComposable.filteredEmployees;
const totalPages = dataComposable.totalPages;
const paginatedEmployees = dataComposable.paginatedEmployees;

// Form Management
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const editingEmployee = formComposable.editingEmployee;
const employeeToDelete = formComposable.employeeToDelete;
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;
const canCreateEditEmployees = formComposable.canCreateEditEmployees;
const canDeleteEmployees = formComposable.canDeleteEmployees;

// ============== Helper Methods ==============

/**
 * getOfficeNameById: Get the office name for a given ID
 * Delegates to the composable method
 */
const getOfficeNameById = (officeId: number | null | undefined, employee?: Employee): string => {
    return dataComposable.getOfficeNameById(officeId, employee);
};

// ============== Event Handlers ==============

/**
 * handleSort: Handle sort changes from table column headers
 * @param {string} field - The field to sort by
 */
const handleSort = (field: string) => {
    dataComposable.toggleSort(field as any);
};

/**
 * handleChangePage: Handle pagination changes
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
 * @param {Employee} employee - The employee to edit
 */
const handleEditClick = (employee: any) => {
    formComposable.openEditModal(employee);
};

/**
 * handleCloseEditModal: Handle edit modal close
 */
const handleCloseEditModal = () => {
    formComposable.closeEditModal();
};

/**
 * handleDeleteClick: Handle delete button click
 * @param {Employee} employee - The employee to delete
 */
const handleDeleteClick = (employee: any) => {
    formComposable.openDeleteModal(employee);
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
        const newEmployee = await formComposable.createEmployee();
        if (newEmployee) {
            dataComposable.addEmployee(newEmployee);
            formComposable.closeCreateModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `Employee: <strong>${newEmployee.name}</strong> has been created successfully!`,
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
    if (!editingEmployee.value) return;

    try {
        const updatedEmployee = await formComposable.updateEmployee(editingEmployee.value.id);
        if (updatedEmployee) {
            dataComposable.updateEmployee(editingEmployee.value.id, updatedEmployee);
            formComposable.closeEditModal();
            
            toastRef.value?.add(
                'info',
                'Success',
                `Employee: <strong>${updatedEmployee.name}</strong> has been updated successfully!`,
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
    if (!employeeToDelete.value) return;

    const deletingEmployee = employeeToDelete.value;

    try {
        await formComposable.deleteEmployee(deletingEmployee.id);
        dataComposable.deleteEmployee(deletingEmployee.id);
        formComposable.closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `Employee: <strong>${deletingEmployee.name}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};
</script>
