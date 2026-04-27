<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Time Slips
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
                    v-else-if="timeSlips.length === 0"
                    title="No Time Slips found"
                    message="Get started by creating a new time slip"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredTimeSlips.length === 0"
                    title="No Time Slips found"
                    message="Try adjusting your search or create a new time slip"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :records="paginatedTimeSlips"
                    @edit="handleEditClick"
                    @delete="handleDeleteClick"
                    @preview="handlePreviewClick"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && timeSlips.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-records="filteredTimeSlips.length"
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
            :is-preview-from-table="formComposable.isPreviewFromTable.value"
            @close="formComposable.closePreviewModal()"
            @confirm="handleConfirmPreviewAndSubmit()"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';

// Partials
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import Pagination from './Partials/Pagination.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import PreviewModal from './Partials/PreviewModal.vue';

// Composables
import { useTimeSlipsData } from './Composables/useTimeSlipsData';
import { useTimeSlipsForm } from './Composables/useTimeSlipsForm';
import type { TimeSlip } from './Composables/useTimeSlipsData';

const dataComposable = useTimeSlipsData();
const {
    timeSlips,
    employees,
    loading,
    error,
    fetchTimeSlips,
    fetchEmployees,
    createTimeSlip,
    updateTimeSlip,
    deleteTimeSlip,
} = dataComposable;

const formComposable = useTimeSlipsForm(employees, timeSlips);
const {
    formData,
    formErrors,
    showCreateModal,
    showEditModal,
    showDeleteModal,
    showPreviewModal,
    creating,
    updating,
    deleting,
    timeSlipToEdit,
    timeSlipToDelete,
    sortedEmployees,
    formatTimeForAPI,
} = formComposable;

const toastRef = ref();
const searchQuery = ref('');
const itemsPerPage = ref(10);
const currentPage = ref(1);

const creating_local = ref(false);
const updating_local = ref(false);
const deleting_local = ref(false);

const filteredTimeSlips = computed(() => {
    if (!searchQuery.value) return timeSlips.value;

    const query = searchQuery.value.toLowerCase();
    return timeSlips.value.filter((slip) => {
        const controlNo = slip.control_no?.toLowerCase() || '';
        const employeeName = slip.requesting_employee?.name?.toLowerCase() || '';
        const certifiedBy = slip.certified_by_employee?.name?.toLowerCase() || '';
        const reason = slip.reason?.toLowerCase() || '';
        const date = slip.date?.toLowerCase() || '';

        return (
            controlNo.includes(query) ||
            employeeName.includes(query) ||
            certifiedBy.includes(query) ||
            reason.includes(query) ||
            date.includes(query)
        );
    });
});

const totalPages = computed(() => Math.ceil(filteredTimeSlips.value.length / itemsPerPage.value));

const paginatedTimeSlips = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTimeSlips.value.slice(start, end);
});

const paginationRange = computed(() => {
    const range = [];
    const maxPages = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
    let end = Math.min(totalPages.value, start + maxPages - 1);

    if (end - start + 1 < maxPages) {
        start = Math.max(1, end - maxPages + 1);
    }

    if (start > 1) {
        range.push(1);
        if (start > 2) range.push('...');
    }

    for (let i = start; i <= end; i++) {
        range.push(i);
    }

    if (end < totalPages.value) {
        if (end < totalPages.value - 1) range.push('...');
        range.push(totalPages.value);
    }

    return range;
});

const recordToEdit = computed(() => timeSlipToEdit.value);
const recordToDelete = computed(() => timeSlipToDelete.value);

const handleCreateClick = () => {
    formComposable.openCreateModal();
};

const handleEditClick = (timeSlip: TimeSlip) => {
    formComposable.openEditModal(timeSlip);
};

const handleDeleteClick = (timeSlip: TimeSlip) => {
    formComposable.openDeleteModal(timeSlip);
};

const handlePreviewClick = (timeSlip: TimeSlip) => {
    formComposable.isPreviewFromTable.value = true;
    formComposable.openPreviewModal(timeSlip);
};

const handleSearchQuery = (query: string) => {
    searchQuery.value = query;
    currentPage.value = 1;
};

const handleItemsPerPage = (count: number) => {
    itemsPerPage.value = count;
    currentPage.value = 1;
};

const handleChangePage = (page: number) => {
    currentPage.value = page;
};

const handleSubmitCreateForm = async () => {
    formErrors.value = {};

    // Basic validation
    if (!formData.value.requesting_employee_id) {
        formErrors.value.requesting_employee_id = 'Employee is required';
    }
    if (!formData.value.date) {
        formErrors.value.date = 'Date is required';
    }
    if (!formData.value.reason) {
        formErrors.value.reason = 'Reason is required';
    }
    if (!formData.value.time_in_am && !formData.value.time_out_am && !formData.value.time_in_pm && !formData.value.time_out_pm) {
        formErrors.value.times = 'At least one time field must have a value';
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    // Open preview modal instead of directly submitting
    formComposable.isPreviewFromTable.value = false;
    formComposable.showPreviewModal.value = true;
};

const handleSubmitEditForm = async () => {
    formErrors.value = {};

    // Basic validation
    if (!formData.value.requesting_employee_id) {
        formErrors.value.requesting_employee_id = 'Employee is required';
    }
    if (!formData.value.date) {
        formErrors.value.date = 'Date is required';
    }
    if (!formData.value.reason) {
        formErrors.value.reason = 'Reason is required';
    }
    if (!formData.value.time_in_am && !formData.value.time_out_am && !formData.value.time_in_pm && !formData.value.time_out_pm) {
        formErrors.value.times = 'At least one time field must have a value';
    }

    if (Object.keys(formErrors.value).length > 0) {
        return;
    }

    // Open preview modal instead of directly submitting
    formComposable.isPreviewFromTable.value = false;
    formComposable.showPreviewModal.value = true;
};

const handleConfirmDelete = async () => {
    deleting.value = true;
    try {
        await deleteTimeSlip(recordToDelete.value!.id);
        timeSlips.value = timeSlips.value.filter((slip) => slip.id !== recordToDelete.value!.id);
        formComposable.closeDeleteModal();
        toastRef.value?.add(
            'error',
            'Deleted',
            'Time slip deleted successfully!',
            3000
        );
    } catch (err: unknown) {
        const errorMsg = (err as any)?.message || 'Failed to delete time slip';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};

const handleConfirmPreviewAndSubmit = async () => {
    try {
        const isUpdate = recordToEdit.value !== null;
        if (isUpdate) {
            updating.value = true;
        } else {
            creating.value = true;
        }

        const submitData = {
            control_no: formData.value.control_no,
            requesting_employee_id: formData.value.requesting_employee_id,
            date: formData.value.date,
            time_in_am: formData.value.time_in_am ? formatTimeForAPI(formData.value.time_in_am) : null,
            time_out_am: formData.value.time_out_am ? formatTimeForAPI(formData.value.time_out_am) : null,
            time_in_pm: formData.value.time_in_pm ? formatTimeForAPI(formData.value.time_in_pm) : null,
            time_out_pm: formData.value.time_out_pm ? formatTimeForAPI(formData.value.time_out_pm) : null,
            reason: formData.value.reason,
            certified_by_employee_id: formData.value.certified_by_employee_id || null,
        };

        let result;
        if (isUpdate) {
            result = await updateTimeSlip(recordToEdit.value!.id, submitData);
        } else {
            result = await createTimeSlip(submitData);
        }
        
        formComposable.closePreviewModal();

        if (isUpdate) {
            const index = timeSlips.value.findIndex((slip) => slip.id === recordToEdit.value!.id);
            if (index !== -1) {
                timeSlips.value[index] = result.data;
            }
            formComposable.closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                'Time slip updated successfully!',
                3000
            );
        } else {
            timeSlips.value.unshift(result.data);
            formComposable.closeCreateModal();
            toastRef.value?.add(
                'success',
                'Success',
                'Time slip created successfully!',
                3000
            );
        }
    } catch (err: unknown) {
        const error = err as any;
        if (error?.errors) {
            formErrors.value = error.errors;
        } else {
            const errorMsg = error?.message || 'An error occurred';
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    } finally {
        creating.value = false;
        updating.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchTimeSlips(), fetchEmployees()]);
});
</script>
