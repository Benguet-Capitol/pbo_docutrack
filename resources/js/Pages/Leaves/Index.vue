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
                    :can-create-leaves="canCreateLeaves && (canViewAllLeaves || !!ownEmployeeId)"
                    @update:search-query="searchQuery = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @create-click="openCreateModal"
                    
                    :selected-year="selectedYear"
                    :available-years="availableYears"
                    @update:selected-year="selectedYear = $event"
                    :view-mode="viewMode"
                    @update:view-mode="setViewMode"
                />

                <!-- Leaves are personal data: without "view all", only the user's own leaves are listed -->
                <div
                    v-if="!canViewAllLeaves"
                    class="mx-4 sm:mx-6 mt-4 mb-4 flex items-center gap-4 rounded-xl border-l-4 px-4 sm:px-5 py-4 shadow-sm"
                    :class="ownEmployeeId
                        ? 'border-emerald-500 bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/30 dark:to-gray-800'
                        : 'border-amber-500 bg-gradient-to-r from-amber-50 to-white dark:from-amber-900/30 dark:to-gray-800'"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg"
                        :class="ownEmployeeId ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800/60 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-800/60 dark:text-amber-200'"
                    >
                        <i :class="ownEmployeeId ? 'fas fa-user-shield' : 'fas fa-user-lock'"></i>
                    </div>
                    <div class="min-w-0">
                        <template v-if="ownEmployeeId">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                My Leaves<span v-if="ownEmployeeName" class="font-normal text-gray-600 dark:text-gray-300"> · {{ ownEmployeeName }}</span>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">You're seeing only your own leave applications.</p>
                        </template>
                        <template v-else>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Your account isn't linked to an employee</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">No leaves can be shown yet. Ask an administrator to link your account to your employee record in Users.</p>
                        </template>
                    </div>
                </div>

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
                    message="Try another year, adjust your search, or create a new leave"
                />

                <!-- Data Table -->
                <Table
                    v-else-if="viewMode === 'table'"
                    :records="paginatedLeaves"
                    :sort-by="sortBy"
                    :sort-order="sortOrder"
                    :can-edit-leaves="canEditLeaves"
                    :can-delete-leaves="canDeleteLeaves"
                    @preview="openRecordPreview"
                    @edit="openEditModal"
                    @delete="openDeleteModal"
                    @sort="toggleSort"
                />
                <div v-else class="p-4">
                    <CardView
                        :records="paginatedLeaves"
                        :can-edit-leaves="canEditLeaves"
                        :can-delete-leaves="canDeleteLeaves"
                        @preview="openRecordPreview"
                        @edit="openEditModal"
                        @delete="openDeleteModal"
                    />
                </div>

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
            :leave-credits="leaveCreditInfo"
            :missing-fields="missingLeaveFields"
            :limit-message="leaveLimitMessage"
            :only-employee-id="canViewAllLeaves ? null : ownEmployeeId"
            @close="closeCreateModal"
            @confirm="openCreatePreview"
        />

        <EditModal
            :show="showEditModal"
            :form-data="(formData as any)"
            :form-errors="formErrors"
            :employees="sortedEmployees"
            :loading="updating"
            :leave-types="leaveTypes"
            :leave-credits="leaveCreditInfo"
            :missing-fields="missingLeaveFields"
            :limit-message="leaveLimitMessage"
            :only-employee-id="canViewAllLeaves ? null : ownEmployeeId"
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

        <!-- CS Form No. 6 print preview (create step, or a saved leave) -->
        <PreviewModal
            :show="showPreviewModal"
            :leave="previewLeave"
            :employee="previewEmployee"
            :signatories="previewSignatories"
            :mode="previewMode"
            :leave-id="previewLeaveId"
            :saving="creating"
            @close="showPreviewModal = false"
            @confirm="confirmCreateFromPreview"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
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
import CardView from './Partials/CardView.vue';
import Pagination from './Partials/Pagination.vue';
import CreateModal from './Partials/CreateModal.vue';
import EditModal from './Partials/EditModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import PreviewModal from './Partials/PreviewModal.vue';
import { applyDefaultSignatories, resolveLeaveSignatory, followApplicantSignatory } from './Composables/useLeaveSignatories';
import { useResponsiveView } from '@/Composables/useResponsiveView';

// Table on desktop (user can switch to cards), always cards on small screens
const { viewMode, setViewMode } = useResponsiveView('leavesView');

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
    selectedYear,
    availableYears,
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
    leaveCreditInfo,
    missingLeaveFields,
    leaveLimitMessage,
    openCreateModal: _openCreateModal,
    closeCreateModal,
    openEditModal: _openEditModal,
    closeEditModal,
    openDeleteModal: _openDeleteModal,
    closeDeleteModal,
    createLeave: _createLeave,
    updateLeave: _updateLeave,
    deleteLeave: _deleteLeave,
    canCreateLeaves,
    canViewAllLeaves,
    ownEmployeeId,
    canEditLeaves,
    canDeleteLeaves,
} = useLeavesForm(leaves, fetchLeaves, showToast);

/** The signed-in user's employee name (My Leaves banner) */
const ownEmployeeName = computed(() => (employees.value as any[]).find((emp: any) => emp.id === ownEmployeeId.value)?.name || '');

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
// Signatories default to the current PHRMDO, Provincial Budget Officer and Provincial Governor
const openCreateModal = () => {
    _openCreateModal();
    applyDefaultSignatories(formData.value as any, employees.value);
};

const openEditModal = (leave: any) => {
    _openEditModal(leave);
    applyDefaultSignatories(formData.value as any, employees.value);
    // Leaves saved before salaries were kept get the current one
    if (formData.value.salary === '' || formData.value.salary === null) loadSalary();
};

// The authorized official follows the applicant (Governor for a department head, else the Provincial Administrator)
// (only when the employee is changed in the open form, not when another leave is opened)
watch(() => [formData.value.employee_id, formData.value.control_no] as const, ([, controlNo], [previousId, previousControlNo]) => {
    if (controlNo === previousControlNo && (showCreateModal.value || showEditModal.value)) {
        followApplicantSignatory(formData.value as any, employees.value, previousId);
        loadSalary();
    }
});

/** 5. SALARY: the chosen employee's current monthly salary (from HRIS) */
const loadSalary = async () => {
    const employeeId = Number(formData.value.employee_id);
    if (!employeeId) {
        formData.value.salary = '';
        return;
    }
    try {
        const response = await fetch(`/api/leaves/employee-salary/${employeeId}`);
        if (!response.ok) return;
        const { salary } = await response.json();
        // The employee may have been changed again while loading
        if (Number(formData.value.employee_id) === employeeId) formData.value.salary = salary ?? '';
    } catch {
        // Leave the field for manual entry
    }
};

const openDeleteModal = (leave: any) => {
    currentDeleteRecord.value = leave;
    _openDeleteModal(leave);
};

const createLeave = async () => {
    await _createLeave();
};

// ============== CS Form No. 6 Preview ==============
const showPreviewModal = ref(false);
const previewMode = ref<'create' | 'view'>('view');
const previewLeave = ref<any>(null);
const previewLeaveId = ref<number | null>(null);
const previewEmployee = computed(() =>
    employees.value.find((emp: any) => emp.id === Number(previewLeave.value?.employee_id)) || previewLeave.value?.employee || null
);
const previewSignatories = computed(() => ({
    certified_by: resolveLeaveSignatory(employees.value, previewLeave.value, 'certified_by'),
    recommended_by: resolveLeaveSignatory(employees.value, previewLeave.value, 'recommended_by'),
    approved_by: resolveLeaveSignatory(employees.value, previewLeave.value, 'approved_by'),
}));

/** Create flow: form -> preview -> Confirm & Save */
const openCreatePreview = () => {
    previewMode.value = 'create';
    previewLeave.value = formData.value;
    previewLeaveId.value = null;
    showPreviewModal.value = true;
};

const confirmCreateFromPreview = async () => {
    await _createLeave();
    // Closes on success; on failure the error shows in the form underneath
    showPreviewModal.value = false;
};

/** A saved leave: print or download the filled Excel form */
const openRecordPreview = (leave: any) => {
    previewMode.value = 'view';
    previewLeave.value = leave;
    previewLeaveId.value = leave.id;
    showPreviewModal.value = true;
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
