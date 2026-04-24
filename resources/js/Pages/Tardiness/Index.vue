<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Tardiness/Undertime
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

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState 
                    v-else-if="tardiness.length === 0"
                    title="No records found"
                    message="Get started by creating a new tardiness/undertime record"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredTardiness.length === 0"
                    title="No Tardiness/Undertime found"
                    message="Try adjusting your search or create a new record"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedTardiness"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                    @preview="handlePreviewClick"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && tardiness.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-records="filteredTardiness.length"
                    :pagination-range="paginationRange"
                    @page-change="handleChangePage"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            :sorted-employees="sortedEmployees"
            :today-date="formComposable.todayDate.value"
            :creating="creating"
            @update:form-data="handleUpdateFormData"
            @close="formComposable.closeCreateModal()"
            @submit="handleSubmitCreateForm"
        />

        <EditModal
            :show="showEditModal"
            :record-to-edit="recordToEdit"
            :form-data="formData"
            :form-errors="formErrors"
            :sorted-employees="sortedEmployees"
            :updating="updating"
            @update:form-data="handleUpdateFormData"
            @close="formComposable.closeEditModal()"
            @submit="handleSubmitEditForm"
        />

        <DeleteModal
            :show="showDeleteModal"
            :record-to-delete="recordToDelete"
            :deleting="deleting"
            @close="formComposable.closeDeleteModal()"
            @confirm="handleConfirmDelete"
        />

        <PreviewModal
            :show="showPreviewModal"
            :form-data="formData"
            :requesting-employee="formComposable.getRequestingEmployee()"
            :supervisor="formComposable.getSupervisor()"
            :provincial-budget-officer="formComposable.getProvincialBudgetOfficer()"
            :provincial-governor="formComposable.getProvincialGovernor()"
            :saving="creating || updating"
            :is-preview-from-table="formComposable.isPreviewFromTable.value"
            @close="formComposable.closePreviewModal()"
            @confirm="handleConfirmPreviewAndSubmit"
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
import { useTardinessData } from './Composables/useTardinessData';
import { useTardinessForm } from './Composables/useTardinessForm';
import type { TardinessRecord } from './Composables/useTardinessData';

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Composables ==============
const dataComposable = useTardinessData();
const formComposable = useTardinessForm(dataComposable.employees, dataComposable.tardiness);

// ============== Expose State from Composables ==============
// Data Management
const tardiness = dataComposable.tardiness;
const employees = dataComposable.employees;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const loading = dataComposable.loading;
const error = dataComposable.error;
const filteredTardiness = dataComposable.filteredTardiness;
const sortedEmployees = dataComposable.sortedEmployees;
const totalPages = dataComposable.totalPages;
const paginatedTardiness = dataComposable.paginatedTardiness;
const paginationRange = dataComposable.paginationRange;

// Form Management
const formData = formComposable.formData;
const formErrors = formComposable.formErrors;
const showCreateModal = formComposable.showCreateModal;
const showEditModal = formComposable.showEditModal;
const showDeleteModal = formComposable.showDeleteModal;
const showPreviewModal = formComposable.showPreviewModal;
const creating = formComposable.creating;
const updating = formComposable.updating;
const deleting = formComposable.deleting;
const recordToEdit = formComposable.recordToEdit;
const recordToDelete = formComposable.recordToDelete;

// ============== Event Handlers ==============

const handleSearchQuery = (query: string) => {
    dataComposable.searchQuery.value = query;
    dataComposable.currentPage.value = 1;
};

const handleItemsPerPage = (items: number) => {
    dataComposable.itemsPerPage.value = items;
    dataComposable.currentPage.value = 1;
};

const handleChangePage = (page: number) => {
    dataComposable.currentPage.value = page;
};

const handleCreateClick = () => {
    formComposable.openCreateModal();
};

const handleEditClick = (record: TardinessRecord) => {
    formComposable.openEditModal(record);
};

const handleDeleteClick = (record: TardinessRecord) => {
    formComposable.openDeleteModal(record);
};

const handlePreviewClick = (record: TardinessRecord) => {
    formComposable.recordToEdit.value = record;
    formComposable.formData.value = {
        control_no: record.control_no,
        date_filed: formComposable.formatDateForInput(record.date_filed),
        requested_date: formComposable.formatDateForInput(record.requested_date),
        requested_time: formComposable.formatTimeForInput(record.requested_time),
        return_time: record.return_time ? formComposable.formatTimeForInput(record.return_time) : '',
        reason: record.reason || '',
        type: record.type || 'Tardiness',
        employee_id: record.employee_id || null,
    };
    formComposable.isPreviewFromTable.value = true;
    formComposable.showPreviewModal.value = true;
};

const handleUpdateFormData = (data: any) => {
    formComposable.formData.value = data;
};

const handleSubmitCreateForm = async () => {
    if (!formComposable.validateForm()) return;
    // Open preview modal instead of directly submitting
    formComposable.isPreviewFromTable.value = false;
    formComposable.showPreviewModal.value = true;
};

const handleSubmitEditForm = async () => {
    if (!formComposable.validateForm() || !formComposable.recordToEdit.value) return;
    // Open preview modal instead of directly submitting
    formComposable.isPreviewFromTable.value = false;
    formComposable.showPreviewModal.value = true;
};

const handleConfirmDelete = async () => {
    if (!formComposable.recordToDelete.value) return;

    try {
        formComposable.deleting.value = true;

        const response = await fetch(`/api/tardiness/${formComposable.recordToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to delete record');
        }

        const controlNo = formComposable.recordToDelete.value.control_no;
        dataComposable.tardiness.value = dataComposable.tardiness.value.filter(r => r.id !== formComposable.recordToDelete.value!.id);
        formComposable.closeDeleteModal();

        toastRef.value?.add(
            'error',
            'Deleted',
            `Record <strong>${controlNo}</strong> deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        formComposable.deleting.value = false;
    }
};

const handleConfirmPreviewAndSubmit = async () => {
    try {
        const isUpdate = formComposable.recordToEdit.value !== null;
        if (isUpdate) {
            formComposable.updating.value = true;
        } else {
            formComposable.creating.value = true;
        }

        let returnTime = formComposable.formData.value.return_time;
        if (formComposable.formData.value.returnType === 'nwd') {
            returnTime = '17:00:00'; // 5:00 PM for NWD
        }

        const submitData = {
            control_no: formComposable.formData.value.control_no,
            date_filed: formComposable.formData.value.date_filed,
            type: formComposable.formData.value.type,
            requested_date: formComposable.formData.value.requested_date,
            employee_id: formComposable.formData.value.employee_id,
            requested_time: formComposable.formData.value.requested_time,
            reason: formComposable.formData.value.reason,
            return_time: returnTime,
            supervisor_employee_id: formComposable.formData.value.supervisor_employee_id,
        };

        const url = isUpdate ? `/api/tardiness/${formComposable.recordToEdit.value!.id}` : '/api/tardiness';
        const method = isUpdate ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));

            // Handle validation errors (422)
            if (response.status === 422 && errorData.errors) {
                Object.keys(errorData.errors).forEach(field => {
                    formComposable.formErrors.value[field] = errorData.errors[field][0];
                });
                const errorMessages = Object.values(errorData.errors)
                    .flat()
                    .join(', ');
                throw new Error(errorMessages);
            }

            throw new Error(errorData.error || errorData.message || `Failed to ${isUpdate ? 'update' : 'create'} record`);
        }

        const result = await response.json();
        formComposable.closePreviewModal();

        if (isUpdate) {
            const index = dataComposable.tardiness.value.findIndex(r => r.id === formComposable.recordToEdit.value!.id);
            if (index !== -1) {
                dataComposable.tardiness.value[index] = result.data;
            }
            formComposable.closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                `Record <strong>${result.data.control_no}</strong> updated successfully!`,
                3000
            );
        } else {
            dataComposable.tardiness.value.push(result.data);
            formComposable.closeCreateModal();
            toastRef.value?.add(
                'success',
                'Success',
                `Record <strong>${result.data.control_no}</strong> created successfully!`,
                3000
            );
        }
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formComposable.formErrors.value['submit'] = errorMsg;
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        formComposable.creating.value = false;
        formComposable.updating.value = false;
    }
};

onMounted(() => {
    dataComposable.fetchTardiness().catch(err => {
        toastRef.value?.add('error', 'Error', err.message, 4000);
    });
    dataComposable.fetchEmployees();
});
</script>
