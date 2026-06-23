<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Travel Orders
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    @create-click="formComposable.openCreateModal()"
                    @update:search-query="handleSearchQuery"
                    @update:items-per-page="handleItemsPerPage"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState 
                    v-else-if="travelOrders.length === 0"
                    title="No records found"
                    message="Get started by creating a new travel order"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredTravelOrders.length === 0"
                    title="No Travel Orders found"
                    message="Try adjusting your search or create a new travel order"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedTravelOrders"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                    @preview="handlePreviewClick"
                    @sort="handleSort"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && !error && travelOrders.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :filtered-count="filteredTravelOrders.length"
                    :pagination-range="paginationRange"
                    @page-change="currentPage = $event"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            :requesting-employees="requestingEmployees"
            :supervisor-options="supervisorOptions"
            :approver-options="approverOptions"
            :driver-options="driverOptions"
            @close="formComposable.closeCreateModal()"
            @update:form-data="formData = $event"
            @submit="submitCreateTravelOrder"
        />

        <EditModal
            :show="showEditModal"
            :form-data="formData"
            :form-errors="formErrors"
            :requesting-employees="requestingEmployees"
            :supervisor-options="supervisorOptions"
            :approver-options="approverOptions"
            :driver-options="driverOptions"
            @close="formComposable.closeEditModal()"
            @update:form-data="formData = $event"
            @submit="submitEditTravelOrder"
        />

        <DeleteModal
            :show="showDeleteModal"
            :record="travelOrderToDelete"
            :deleting="deleting"
            @close="formComposable.closeDeleteModal()"
            @confirm="handleConfirmDelete"
        />

        <PreviewModal
            :show="showPreviewModal"
            :form-data="formData"
            :employees="employees"
            :sorted-employees="sortedEmployees"
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
import { useTravelOrdersData } from './Composables/useTravelOrdersData';
import { useTravelOrdersForm } from './Composables/useTravelOrdersForm';

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Composables ==============
const dataComposable = useTravelOrdersData();
const formComposable = useTravelOrdersForm(dataComposable.employees, dataComposable.travelOrders);

// ============== Expose State from Composables ==============
// Data Management
const travelOrders = dataComposable.travelOrders;
const employees = dataComposable.employees;
const searchQuery = dataComposable.searchQuery;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const sortBy = dataComposable.sortBy;
const sortOrder = dataComposable.sortOrder;
const loading = dataComposable.loading;
const error = dataComposable.error;
const filteredTravelOrders = dataComposable.filteredTravelOrders;
const sortedEmployees = dataComposable.sortedEmployees;
const totalPages = dataComposable.totalPages;
const paginatedTravelOrders = dataComposable.paginatedTravelOrders;
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
const travelOrderToEdit = formComposable.travelOrderToEdit;
const travelOrderToDelete = formComposable.travelOrderToDelete;
const requestingEmployees = formComposable.requestingEmployees;
const supervisorOptions = formComposable.supervisorOptions;
const approverOptions = formComposable.approverOptions;
const driverOptions = formComposable.driverOptions;

// ============== Event Handlers ==============

const handleSearchQuery = (query: string) => {
    dataComposable.searchQuery.value = query;
    dataComposable.currentPage.value = 1;
};

const handleItemsPerPage = (count: string) => {
    dataComposable.itemsPerPage.value = Number(count);
    dataComposable.currentPage.value = 1;
};

const handleSort = (field: string) => {
    dataComposable.toggleSort(field);
};

const handleEditClick = (record: any) => {
    formComposable.openEditModal(record);
};

const handleDeleteClick = (record: any) => {
    formComposable.openDeleteModal(record);
};

const handlePreviewClick = (record: any) => {
    formComposable.travelOrderToEdit.value = record;
    formComposable.isPreviewFromTable.value = true;
    formComposable.openPreviewModal();
};

const showToast = (type: 'success' | 'error' | 'info', title: string, message: string) => {
    toastRef.value?.add(type, title, message, type === 'error' ? 4000 : 3000);
};

const submitCreateTravelOrder = async () => {
    if (!formComposable.validateForm()) return;
    formComposable.openPreviewModal();
};

const submitEditTravelOrder = async () => {
    if (!formComposable.validateForm()) return;
    formComposable.openPreviewModal();
};

const handleConfirmDelete = async () => {
    if (!travelOrderToDelete.value) return;

    try {
        deleting.value = true;
        const response = await fetch(`/api/travel-orders/${travelOrderToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to delete travel order');
        }

        const controlNo = travelOrderToDelete.value.control_no;
        await dataComposable.fetchTravelOrders();
        formComposable.closeDeleteModal();
        
        showToast('error', 'Deleted', `Travel order <strong>${controlNo}</strong> has been deleted successfully!`);
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        showToast('error', 'Error', errorMsg);
    } finally {
        deleting.value = false;
    }
};

const handleConfirmPreviewAndSubmit = async () => {
    try {
        const isUpdate = travelOrderToEdit.value !== null;
        if (isUpdate) {
            updating.value = true;
        } else {
            creating.value = true;
        }

        const submitData = {
            control_no: formData.value.control_no,
            date: formData.value.date,
            going_to: formData.value.going_to,
            inclusive_dates: formData.value.inclusive_dates,
            purpose: formData.value.purpose,
            vehicle: formData.value.vehicle,
            plate_number: formData.value.plate_number,
            employee_ids: formData.value.employee_ids,
            driver: formData.value.driver,
            supervisor_employee_id: formData.value.supervisor_employee_id,
            approver_employee_id: formData.value.approver_employee_id,
            is_acting_approver: formData.value.is_acting_approver,
            acting_approver_name: formData.value.is_acting_approver ? formData.value.acting_approver_name : null,
            acting_approver_designation: formData.value.is_acting_approver ? formData.value.acting_approver_designation : null,
        };

        const url = isUpdate ? `/api/travel-orders/${travelOrderToEdit.value!.id}` : '/api/travel-orders';
        const method = isUpdate ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            
            if (response.status === 422 && errorData.errors) {
                Object.keys(errorData.errors).forEach(field => {
                    formErrors.value[field] = errorData.errors[field][0];
                });
                const errorMessages = Object.values(errorData.errors)
                    .flat()
                    .join(', ');
                throw new Error(errorMessages as string);
            }
            
            throw new Error(errorData.error || errorData.message || `Failed to ${isUpdate ? 'update' : 'create'} travel order`);
        }

        const result = await response.json();
        formComposable.closePreviewModal();

        if (isUpdate) {
            const index = travelOrders.value.findIndex(t => t.id === travelOrderToEdit.value!.id);
            if (index !== -1) {
                travelOrders.value[index] = result.data;
            }
            formComposable.closeEditModal();
            showToast('info', 'Updated', `Travel order <strong>${result.data.control_no}</strong> updated successfully!`);
        } else {
            travelOrders.value.push(result.data);
            formComposable.closeCreateModal();
            showToast('success', 'Success', `Travel order <strong>${result.data.control_no}</strong> created successfully!`);
        }
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        showToast('error', 'Error', errorMsg);
    } finally {
        creating.value = false;
        updating.value = false;
    }
};

// ============== Initialization ==============

onMounted(async () => {
    await dataComposable.fetchTravelOrders();
    await dataComposable.fetchEmployees();
});
</script>