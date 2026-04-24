<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCoaData } from './Composables/useCoaData';
import { useCoaForm } from './Composables/useCoaForm';
import type { CertificateOfAppearance } from './Composables/useCoaData';
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

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

const {
    certificates,
    searchQuery,
    currentPage,
    itemsPerPage,
    sortBy,
    sortOrder,
    loading,
    error,
    filteredCertificates,
    totalPages,
    paginatedCertificates,
    paginationRange,
    formattedDate,
    fetchAllData,
    sortedEmployees,
} = useCoaData();

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
    certificateToEdit,
    certificateToDelete,
    todayDate,
    generateControlNo,
    resetForm,
    openCreateModal,
    closeCreateModal,
    openEditModal,
    closeEditModal,
    openDeleteModal,
    closeDeleteModal,
    openPreviewModal,
    closePreviewModal,
    isPreviewFromTable,
} = useCoaForm(certificates);

const showPagination = computed(() => totalPages.value > 1);

// Fetch data on component mount
onMounted(() => {
    fetchAllData();
});

// ============== Event Handlers ==============
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

const handleSort = (field: 'control_no' | 'name' | 'office' | 'purpose' | 'date') => {
    if (sortBy.value === field) {
        // Toggle sort order if same field clicked
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new field and default to descending
        sortBy.value = field;
        sortOrder.value = 'desc';
    }
    currentPage.value = 1;
};

const handleUpdateFormData = (data: any) => {
    formData.value = data;
};

const handlePreview = (certificate: CertificateOfAppearance) => {
    certificateToEdit.value = certificate;
    isPreviewFromTable.value = true;
    formData.value = {
        control_no: certificate.control_no,
        name: certificate.name,
        office: certificate.office,
        purpose: certificate.purpose,
        date: certificate.date,
        remarks: certificate.remarks || '',
    };
    openPreviewModal();
};

const handleGenerateControlNo = () => {
    if (formData.value.date) {
        formData.value.control_no = generateControlNo(formData.value.date);
    }
};

// Handle create submit
const submitCreateForm = async () => {
    if (!formData.value.name || !formData.value.office || !formData.value.purpose || !formData.value.date) {
        formErrors.value = {
            ...(!formData.value.name && { name: 'Name is required' }),
            ...(!formData.value.office && { office: 'Office is required' }),
            ...(!formData.value.purpose && { purpose: 'Purpose is required' }),
            ...(!formData.value.date && { date: 'Date is required' }),
        };
        return;
    }

    // Open preview instead of directly submitting
    openPreviewModal();
};

// Handle edit submit
const submitEditForm = async () => {
    if (!certificateToEdit.value) return;

    if (!formData.value.name || !formData.value.office || !formData.value.purpose || !formData.value.date) {
        formErrors.value = {
            ...(!formData.value.name && { name: 'Name is required' }),
            ...(!formData.value.office && { office: 'Office is required' }),
            ...(!formData.value.purpose && { purpose: 'Purpose is required' }),
            ...(!formData.value.date && { date: 'Date is required' }),
        };
        return;
    }

    // Open preview instead of directly submitting
    openPreviewModal();
};

// Handle confirm from preview modal
const handleConfirmPreviewAndSubmit = async () => {
    if (certificateToEdit.value) {
        // Editing
        await submitConfirmEdit();
    } else {
        // Creating
        await submitConfirmCreate();
    }
};

const submitConfirmCreate = async () => {
    creating.value = true;
    formErrors.value = {};

    try {
        const submitData = {
            control_no: formData.value.control_no,
            name: formData.value.name,
            office: formData.value.office,
            purpose: formData.value.purpose,
            date: formData.value.date,
            remarks: formData.value.remarks || '',
        };

        const response = await fetch('/api/certificate-of-appearances', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const data = await response.json();
            if (data.errors) {
                formErrors.value = data.errors;
            } else {
                const errorMsg = data.error || 'Failed to create certificate';
                toastRef.value?.add('error', 'Error', errorMsg, 4000);
                formErrors.value = { submit: errorMsg };
            }
            return;
        }

        const newCertificate = await response.json();
        certificates.value.unshift(newCertificate.data);
        closePreviewModal();
        closeCreateModal();

        toastRef.value?.add(
            'success',
            'Created',
            `Certificate <strong>${newCertificate.data.control_no}</strong> created successfully!`,
            3000
        );
    } catch (err) {
        formErrors.value = { submit: err instanceof Error ? err.message : 'An error occurred' };
    } finally {
        creating.value = false;
    }
};

const submitConfirmEdit = async () => {
    if (!certificateToEdit.value) return;

    updating.value = true;
    formErrors.value = {};

    try {
        const submitData = {
            control_no: formData.value.control_no,
            name: formData.value.name,
            office: formData.value.office,
            purpose: formData.value.purpose,
            date: formData.value.date,
            remarks: formData.value.remarks || '',
        };

        const response = await fetch(`/api/certificate-of-appearances/${certificateToEdit.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const data = await response.json();
            if (data.errors) {
                formErrors.value = data.errors;
            } else {
                const errorMsg = data.error || 'Failed to update certificate';
                toastRef.value?.add('error', 'Error', errorMsg, 4000);
                formErrors.value = { submit: errorMsg };
            }
            return;
        }

        const updatedCertificate = await response.json();
        const index = certificates.value.findIndex(c => c.id === certificateToEdit.value!.id);
        if (index !== -1) {
            certificates.value[index] = updatedCertificate.data;
        }
        closePreviewModal();
        closeEditModal();

        toastRef.value?.add(
            'info',
            'Updated',
            `Certificate <strong>${updatedCertificate.data.control_no}</strong> updated successfully!`,
            3000
        );
    } catch (err) {
        formErrors.value = { submit: err instanceof Error ? err.message : 'An error occurred' };
    } finally {
        updating.value = false;
    }
};

// Handle delete
const confirmDelete = async () => {
    if (!certificateToDelete.value) return;

    deleting.value = true;

    try {
        const response = await fetch(`/api/certificate-of-appearances/${certificateToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const data = await response.json();
            const errorMsg = data.error || 'Failed to delete certificate';
            throw new Error(errorMsg);
        }

        const controlNo = certificateToDelete.value.control_no;
        certificates.value = certificates.value.filter(c => c.id !== certificateToDelete.value!.id);
        closeDeleteModal();

        toastRef.value?.add(
            'error',
            'Deleted',
            `Certificate <strong>${controlNo}</strong> deleted successfully!`,
            3000
        );
    } catch (err) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Certificates of Appearance
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header 
                    :search-query="searchQuery"
                    :items-per-page="itemsPerPage"
                    @create="openCreateModal"
                    @update:search-query="handleSearchQuery"
                    @update:items-per-page="handleItemsPerPage"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState 
                    v-else-if="certificates.length === 0"
                    title="No records found"
                    message="Get started by creating a new certificate of appearance"
                />

                <!-- No Records for Search -->
                <EmptyState 
                    v-else-if="filteredCertificates.length === 0"
                    title="No Certificates found"
                    message="Try adjusting your search or create a new certificate"
                />

                <!-- Data Table -->
                <Table 
                    v-else
                    :certificates="paginatedCertificates"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                    @preview="handlePreview"
                    @sort="handleSort"
                />

                <!-- Pagination Controls -->
                <Pagination 
                    v-if="!loading && certificates.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-records="filteredCertificates.length"
                    :pagination-range="paginationRange"
                    @page-change="handlePageChange"
                />
            </div>
        </div>

        <!-- Modals -->
        <CreateModal
            :show="showCreateModal"
            :form-data="formData"
            :form-errors="formErrors"
            :loading="creating"
            :today-date="todayDate"
            @update:formData="handleUpdateFormData"
            @close="closeCreateModal"
            @submit="submitCreateForm"
            @generate-control-no="handleGenerateControlNo"
        />

        <EditModal
            :show="showEditModal"
            :form-data="formData"
            :form-errors="formErrors"
            :loading="updating"
            :today-date="todayDate"
            @update:formData="handleUpdateFormData"
            @close="closeEditModal"
            @submit="submitEditForm"
        />

        <DeleteModal
            :show="showDeleteModal"
            :certificate="certificateToDelete"
            :loading="deleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />

        <PreviewModal
            :show="showPreviewModal"
            :form-data="formData"
            :formatted-date="formattedDate"
            :is-preview-from-table="isPreviewFromTable"
            :sorted-employees="sortedEmployees"
            @close="closePreviewModal"
            @confirm="handleConfirmPreviewAndSubmit"
        />
    </AuthenticatedLayout>
</template>
