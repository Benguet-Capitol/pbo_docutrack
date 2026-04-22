<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Pass Slips
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
                    v-else-if="passSlips.length === 0"
                    title="No records found"
                    message="Get started by creating a new pass slip"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredPassSlips.length === 0"
                    title="No Pass Slips found"
                    message="Try adjusting your search or create a new pass slip"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedPassSlips"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && passSlips.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-records="filteredPassSlips.length"
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
            :employees="employees"
            :sorted-employees="sortedEmployees"
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
import { usePassSlipsData } from './Composables/usePassSlipsData';
import { usePassSlipsForm } from './Composables/usePassSlipsForm';
import type { PassSlip } from './Composables/usePassSlipsData';

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Composables ==============
const dataComposable = usePassSlipsData();
const formComposable = usePassSlipsForm(dataComposable.employees, dataComposable.passSlips);

// ============== Expose State from Composables ==============
// Data Management
const passSlips = dataComposable.passSlips;
const employees = dataComposable.employees;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const loading = dataComposable.loading;
const error = dataComposable.error;
const filteredPassSlips = dataComposable.filteredPassSlips;
const sortedEmployees = dataComposable.sortedEmployees;
const totalPages = dataComposable.totalPages;
const paginatedPassSlips = dataComposable.paginatedPassSlips;
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
const recordToEdit = formComposable.passSlipToEdit;
const recordToDelete = formComposable.passSlipToDelete;

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

const handleEditClick = (record: PassSlip) => {
    formComposable.openEditModal(record);
};

const handleDeleteClick = (record: PassSlip) => {
    formComposable.openDeleteModal(record);
};

const handleUpdateFormData = (data: any) => {
    formComposable.formData.value = data;
};

const handleSubmitCreateForm = async () => {
    if (!formComposable.validateForm()) return;
    // Open preview modal instead of directly submitting
    formComposable.showPreviewModal.value = true;
};

const handleSubmitEditForm = async () => {
    if (!formComposable.validateForm() || !formComposable.passSlipToEdit.value) return;
    // Open preview modal instead of directly submitting
    formComposable.showPreviewModal.value = true;
};

const handleConfirmDelete = async () => {
    if (!formComposable.passSlipToDelete.value) return;

    try {
        formComposable.deleting.value = true;

        const response = await fetch(`/api/pass-slips/${formComposable.passSlipToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to delete record');
        }

        const controlNo = formComposable.passSlipToDelete.value.control_no;
        dataComposable.passSlips.value = dataComposable.passSlips.value.filter(r => r.id !== formComposable.passSlipToDelete.value!.id);
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
        const isUpdate = formComposable.passSlipToEdit.value !== null;
        if (isUpdate) {
            formComposable.updating.value = true;
        } else {
            formComposable.creating.value = true;
        }

        const submitData = {
            control_no: formComposable.formData.value.control_no,
            date: formComposable.formData.value.date,
            requested_time: formComposable.formData.value.requested_time,
            purpose: formComposable.formData.value.purpose,
            location: formComposable.formData.value.location,
            expected_return_time: formComposable.formData.value.expected_return_time,
            remarks: formComposable.formData.value.remarks,
            employee_ids: formComposable.formData.value.employee_ids,
            recommending_approval_employee_id: formComposable.formData.value.recommending_approval_employee_id,
            vehicle: formComposable.formData.value.vehicle,
            returnType: formComposable.formData.value.returnType,
        };

        const url = isUpdate ? `/api/pass-slips/${formComposable.passSlipToEdit.value!.id}` : '/api/pass-slips';
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
            const index = dataComposable.passSlips.value.findIndex(r => r.id === formComposable.passSlipToEdit.value!.id);
            if (index !== -1) {
                dataComposable.passSlips.value[index] = result.data;
            }
            formComposable.closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                `Record <strong>${result.data.control_no}</strong> updated successfully!`,
                3000
            );
        } else {
            dataComposable.passSlips.value.push(result.data);
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
    dataComposable.fetchPassSlips().catch(err => {
        toastRef.value?.add('error', 'Error', err.message, 4000);
    });
    dataComposable.fetchEmployees();
});
</script>

<style scoped>
.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}

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
</style>
