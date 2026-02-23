<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
    position: string;
    salary: number;
}

interface Leave {
    id: number;
    control_no: string;
    employee_id: number;
    employee: Employee;
    date_of_filing: string;
    type_of_leave: string;
    number_of_working_days_applied_for: number;
    inclusive_dates: string[];
    off_days: string[];
    within_philippines: boolean | null;
    purpose: string | null;
    in_hospital: boolean | null;
    illness: string | null;
    completion_type: string | null;
    other_type: string | null;
    created_at: string;
    updated_at: string;
}

const leaves = ref<Leave[]>([]);
const employees = ref<Employee[]>([]);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref('id');
const sortOrder = ref<'asc' | 'desc'>('asc');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);

const leaveToEdit = ref<Leave | null>(null);
const leaveToDelete = ref<Leave | null>(null);

const formData = ref({
    employee_id: 0,
    date_of_filing: '',
    type_of_leave: 'Vacation Leave',
    number_of_working_days_applied_for: 1,
    inclusive_dates: [] as string[],
    off_days: [] as string[],
    within_philippines: true,
    purpose: '',
    in_hospital: false,
    illness: '',
    completion_type: '',
    other_type: '',
    newInclusiveDate: '',
    newInclusiveDateRange: '',
    newOffDay: '',
});

const formErrors = ref<Record<string, string>>({});
const toastRef = ref();

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

const hasPermission = (permission: string): boolean => {
    return true;
};

const generateControlNo = (dateString?: string): string => {
    // Use provided date or current date
    const dateToUse = dateString ? new Date(dateString) : new Date();
    const year = dateToUse.getFullYear();
    const prefix = 'L';
    
    // Count all leaves for the entire year (not just the month)
    const sameYearCount = leaves.value.filter(leave =>
        leave.control_no.startsWith(`${prefix}-${year}`)
    ).length;
    
    const series = String(sameYearCount + 1).padStart(4, '0');
    return `${prefix}-${year}-${series}`;
};

const filteredLeaves = computed(() => {
    let filtered = leaves.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(leave =>
            leave.control_no.toLowerCase().includes(query) ||
            leave.employee.name.toLowerCase().includes(query) ||
            leave.type_of_leave.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    filtered.sort((a, b) => {
        let aValue: any;
        let bValue: any;

        if (sortBy.value === 'id') {
            aValue = a.id;
            bValue = b.id;
        } else if (sortBy.value === 'date_of_filing') {
            aValue = a.date_of_filing;
            bValue = b.date_of_filing;
        } else if (sortBy.value === 'type_of_leave') {
            aValue = a.type_of_leave;
            bValue = b.type_of_leave;
        } else {
            aValue = a.employee.name;
            bValue = b.employee.name;
            aValue = aValue?.toString().toLowerCase() || '';
            bValue = bValue?.toString().toLowerCase() || '';
        }

        if (typeof aValue === 'string' && !aValue.match(/^\d{4}-\d{2}/)) {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }

        if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return filtered;
});

const paginatedLeaves = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredLeaves.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredLeaves.value.length / itemsPerPage.value);
});

const fetchLeaves = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch('/api/leaves');
        if (!response.ok) throw new Error('Failed to fetch leaves');
        leaves.value = await response.json();
    } catch (err: any) {
        error.value = err.message;
        showToast('Failed to load leaves', 'error');
    } finally {
        loading.value = false;
    }
};

const fetchEmployees = async () => {
    try {
        const response = await fetch('/api/employees');
        if (!response.ok) throw new Error('Failed to fetch employees');
        employees.value = await response.json();
    } catch (err: any) {
        showToast('Failed to load employees', 'error');
    }
};

const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    if (type === 'success') {
        toastRef.value?.add('success', 'Success', message, 3000);
    } else if (type === 'error') {
        toastRef.value?.add('error', 'Error', message, 4000);
    } else {
        toastRef.value?.add('info', 'Info', message, 3000);
    }
};

const formatDateForInput = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDate = (entry: string): string => {
    if (!entry) return '';
    
    if (entry.includes(' - ')) {
        // This is a date range
        const [startDate, endDate] = entry.split(' - ');
        return `${formatDateForDisplay(startDate.trim())} - ${formatDateForDisplay(endDate.trim())}`;
    } else {
        // This is a single date
        return formatDateForDisplay(entry);
    }
};

const toggleSort = (column: string) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    currentPage.value = 1;
};

const openCreateModal = () => {
    const today = new Date().toISOString().split('T')[0];
    formData.value = {
        control_no: generateControlNo(today),
        employee_id: 0,
        date_of_filing: today,
        type_of_leave: 'Vacation Leave',
        number_of_working_days_applied_for: 1,
        inclusive_dates: [],
        off_days: [],
        within_philippines: true,
        purpose: '',
        in_hospital: false,
        illness: '',
        completion_type: '',
        other_type: '',
        newInclusiveDate: '',
        newInclusiveDateRange: '',
        newOffDay: '',
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    formErrors.value = {};
};

const openEditModal = (leave: Leave) => {
    leaveToEdit.value = leave;
    formData.value = {
        control_no: leave.control_no,
        employee_id: leave.employee_id,
        date_of_filing: formatDateForInput(leave.date_of_filing),
        type_of_leave: leave.type_of_leave,
        number_of_working_days_applied_for: leave.number_of_working_days_applied_for,
        inclusive_dates: [...leave.inclusive_dates],
        off_days: [...(leave.off_days || [])],
        within_philippines: leave.within_philippines === true || leave.within_philippines === 1 || leave.within_philippines === "1",
        purpose: leave.purpose || '',
        in_hospital: leave.in_hospital === true || leave.in_hospital === 1 || leave.in_hospital === "1",
        illness: leave.illness || '',
        completion_type: leave.completion_type || '',
        other_type: leave.other_type || '',
        newInclusiveDate: '',
        newInclusiveDateRange: '',
        newOffDay: '',
    };
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    leaveToEdit.value = null;
    formErrors.value = {};
};

// Watch for changes to date_of_filing and regenerate control_no
watch(() => formData.value.date_of_filing, (newDate) => {
    if (showCreateModal.value && newDate) {
        // Regenerate control number based on the new filing date
        formData.value.control_no = generateControlNo(newDate);
    }
});

const addInclusiveDate = () => {
    if (formData.value.newInclusiveDate) {
        // If both start and end dates are provided, create a range
        if (formData.value.newInclusiveDateRange) {
            const rangeString = `${formData.value.newInclusiveDate} - ${formData.value.newInclusiveDateRange}`;
            if (!formData.value.inclusive_dates.includes(rangeString)) {
                formData.value.inclusive_dates.push(rangeString);
            }
        } else {
            // If only start date is provided, add as single date
            if (!formData.value.inclusive_dates.includes(formData.value.newInclusiveDate)) {
                formData.value.inclusive_dates.push(formData.value.newInclusiveDate);
            }
        }
        // Clear both fields
        formData.value.newInclusiveDate = '';
        formData.value.newInclusiveDateRange = '';
    }
};

const removeInclusiveDate = (index: number) => {
    formData.value.inclusive_dates.splice(index, 1);
};

const editInclusiveDate = (index: number) => {
    const entry = formData.value.inclusive_dates[index];
    
    if (entry.includes(' - ')) {
        // This is a date range
        const [startDate, endDate] = entry.split(' - ');
        formData.value.newInclusiveDate = startDate.trim();
        formData.value.newInclusiveDateRange = endDate.trim();
    } else {
        // This is a single date
        formData.value.newInclusiveDate = entry;
        formData.value.newInclusiveDateRange = '';
    }
    
    // Remove the entry from the list so user can edit and re-add
    formData.value.inclusive_dates.splice(index, 1);
};

const addOffDay = () => {
    if (formData.value.newOffDay && !formData.value.off_days.includes(formData.value.newOffDay)) {
        formData.value.off_days.push(formData.value.newOffDay);
        formData.value.newOffDay = '';
    }
};

const removeOffDay = (index: number) => {
    formData.value.off_days.splice(index, 1);
};

const createLeave = async () => {
    try {
        creating.value = true;
        formErrors.value = {};

        if (!formData.value.employee_id) {
            formErrors.value['employee_id'] = 'Employee is required';
            return;
        }

        if (!formData.value.date_of_filing) {
            formErrors.value['date_of_filing'] = 'Date of filing is required';
            return;
        }

        if (formData.value.inclusive_dates.length === 0) {
            formErrors.value['inclusive_dates'] = 'At least one inclusive date is required';
            return;
        }

        const response = await fetch('/api/leaves', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                control_no: formData.value.control_no,
                employee_id: formData.value.employee_id,
                date_of_filing: formData.value.date_of_filing,
                type_of_leave: formData.value.type_of_leave,
                number_of_working_days_applied_for: formData.value.number_of_working_days_applied_for,
                inclusive_dates: formData.value.inclusive_dates,
                off_days: formData.value.off_days.length > 0 ? formData.value.off_days : null,
                within_philippines: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.within_philippines : null,
                purpose: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.purpose : null,
                in_hospital: formData.value.type_of_leave === 'Sick Leave' ? formData.value.in_hospital : null,
                illness: ['Sick Leave', 'Special Leave Benefits for Women'].includes(formData.value.type_of_leave) ? formData.value.illness : null,
                completion_type: formData.value.type_of_leave === 'Study Leave' ? formData.value.completion_type : null,
                other_type: formData.value.type_of_leave === 'Others' ? formData.value.other_type : null,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || errorData.message || 'Failed to create leave');
        }

        const newLeave = await response.json();
        await fetchLeaves();
        closeCreateModal();

        toastRef.value?.add(
            'success',
            'Success',
            `Leave <strong>${newLeave.data.control_no}</strong> has been created successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        creating.value = false;
    }
};

const updateLeave = async () => {
    try {
        updating.value = true;
        formErrors.value = {};

        if (!leaveToEdit.value) return;

        if (!formData.value.employee_id) {
            formErrors.value['employee_id'] = 'Employee is required';
            return;
        }

        if (formData.value.inclusive_dates.length === 0) {
            formErrors.value['inclusive_dates'] = 'At least one inclusive date is required';
            return;
        }

        const response = await fetch(`/api/leaves/${leaveToEdit.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                control_no: formData.value.control_no,
                employee_id: formData.value.employee_id,
                date_of_filing: formData.value.date_of_filing,
                type_of_leave: formData.value.type_of_leave,
                number_of_working_days_applied_for: formData.value.number_of_working_days_applied_for,
                inclusive_dates: formData.value.inclusive_dates,
                off_days: formData.value.off_days.length > 0 ? formData.value.off_days : null,
                within_philippines: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.within_philippines : null,
                purpose: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.purpose : null,
                in_hospital: formData.value.type_of_leave === 'Sick Leave' ? formData.value.in_hospital : null,
                illness: ['Sick Leave', 'Special Leave Benefits for Women'].includes(formData.value.type_of_leave) ? formData.value.illness : null,
                completion_type: formData.value.type_of_leave === 'Study Leave' ? formData.value.completion_type : null,
                other_type: formData.value.type_of_leave === 'Others' ? formData.value.other_type : null,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || errorData.message || 'Failed to update leave');
        }

        const updatedLeave = await response.json();
        await fetchLeaves();
        closeEditModal();

        toastRef.value?.add(
            'info',
            'Updated',
            `Leave <strong>${updatedLeave.data.control_no}</strong> has been updated successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        updating.value = false;
    }
};

const openDeleteModal = (leave: Leave) => {
    leaveToDelete.value = leave;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    leaveToDelete.value = null;
};

const deleteLeave = async () => {
    try {
        deleting.value = true;

        if (!leaveToDelete.value) return;

        const response = await fetch(`/api/leaves/${leaveToDelete.value.id}`, {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new Error('Failed to delete leave');
        }

        const controlNo = leaveToDelete.value.control_no;
        await fetchLeaves();
        closeDeleteModal();

        toastRef.value?.add(
            'error',
            'Deleted',
            `Leave <strong>${controlNo}</strong> has been deleted successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};

onMounted(() => {
    fetchLeaves();
    fetchEmployees();
});
</script>

<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Leaves
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Leave Button -->
                        <button v-if="hasPermission('leaves.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Leave
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('leaves.create') && 'sm:ml-auto']">
                            <i class="fas fa-search text-gray-400"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search leaves..."
                                class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                            />
                            <select
                                v-model.number="itemsPerPage"
                                class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="999999">All</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading leaves...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-red-600 dark:text-red-400">Error loading leaves</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ error }}</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="paginatedLeaves.length === 0" class="px-6 py-12 text-center">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400 font-medium">No leaves found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500">Create your first leave to get started</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <colgroup>
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-28">
                            <col class="w-32">
                            <col class="w-28">
                            <col class="w-32">
                            <col class="w-20">
                        </colgroup>
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('id')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Control No
                                        <span v-if="sortBy === 'id'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('date_of_filing')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Filing Date
                                        <span v-if="sortBy === 'date_of_filing'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('employee_name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Employee
                                        <span v-if="sortBy === 'employee_name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('type_of_leave')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Leave Type
                                        <span v-if="sortBy === 'type_of_leave'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Days Applied</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Inclusive Dates</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="leave in paginatedLeaves" :key="leave.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-3 text-xs font-semibold text-gray-900 dark:text-gray-100">{{ leave.control_no }}</td>
                                <td class="px-6 py-3 text-xs text-gray-600 dark:text-gray-400">{{ formatDateForDisplay(leave.date_of_filing) }}</td>
                                <td class="px-6 py-3 text-xs text-gray-700 dark:text-gray-300">{{ leave.employee.name }}</td>
                                <td class="px-6 py-3 text-xs">
                                    <span class="inline-block px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs">
                                        {{ leave.type_of_leave }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-700 dark:text-gray-300">{{ leave.number_of_working_days_applied_for }}</td>
                                <td class="px-6 py-3 text-xs">
                                    <div class="flex flex-col gap-1">
                                        <span v-for="(date, idx) in leave.inclusive_dates" :key="idx" class="text-gray-600 dark:text-gray-400">
                                            {{ formatInclusiveDate(date) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button
                                            v-if="hasPermission('leaves.edit')"
                                            @click.stop="openEditModal(leave)"
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button -->
                                        <button
                                            v-if="hasPermission('leaves.delete')"
                                            @click.stop="openDeleteModal(leave)"
                                            class="relative p-2 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="!loading && !error && leaves.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredLeaves.length) }} of {{ filteredLeaves.length }} results
                    </p>
                    <div class="flex gap-1">
                        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1" class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
                            Previous
                        </button>
                        <button v-for="page in totalPages" :key="page" @click="currentPage = page" :class="['px-2 py-1 text-xs rounded transition-colors', currentPage === page ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600']">
                            {{ page }}
                        </button>
                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages" class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Leave Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 dark:border-emerald-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i class="fas fa-leaf text-emerald-600 dark:text-emerald-400"></i>
                            Create Leave
                        </h3>
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                            <div v-if="formErrors['submit']" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-sm">
                                {{ formErrors['submit'] }}
                            </div>

                            <div class="space-y-4">
                                <!-- Employee Selection -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Employee <span class="text-red-500">*</span></label>
                                    <select v-model.number="formData.employee_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                        <option value="0">Select Employee</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                    <p v-if="formErrors['employee_id']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['employee_id'] }}</p>
                                </div>

                                <!-- Date of Filing -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Filing <span class="text-red-500">*</span></label>
                                    <input v-model="formData.date_of_filing" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                </div>

                                <!-- Type of Leave -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type of Leave <span class="text-red-500">*</span></label>
                                    <select v-model="formData.type_of_leave" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                        <option v-for="type in leaveTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>

                                <!-- Number of Working Days Applied For -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Working Days Applied For <span class="text-red-500">*</span></label>
                                    <input v-model.number="formData.number_of_working_days_applied_for" type="number" min="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                </div>

                                <!-- Inclusive Dates -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Inclusive Dates <span class="text-red-500">*</span></label>
                                    <div class="space-y-2">
                                        <div class="flex gap-2 items-end">
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                                                <input v-model="formData.newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                                                <input v-model="formData.newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                            </div>
                                            <button @click.prevent="addInclusiveDate" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-xs font-medium">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="(entry, idx) in formData.inclusive_dates" :key="idx" @click.prevent="editInclusiveDate(idx)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs cursor-pointer hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">
                                                <i class="fas fa-edit text-emerald-600 dark:text-emerald-400"></i>
                                                {{ formatInclusiveDate(entry) }}
                                                <button @click.stop="removeInclusiveDate(idx)" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 font-bold">×</button>
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="formErrors['inclusive_dates']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['inclusive_dates'] }}</p>
                                </div>

                                <!-- Off Days -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Off Days</label>
                                    <input v-model="formData.off_days" type="text" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                </div>

                                <!-- Conditional Fields -->
                                <div v-if="['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.type_of_leave)" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Location Details</h3>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.within_philippines" type="radio" name="location" :value="true" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Within Philippines</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.within_philippines" type="radio" name="location" :value="false" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Abroad</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                                        <textarea v-model="formData.purpose" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Sick Leave'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Medical Details</h3>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.in_hospital" type="radio" name="hospital" :value="true" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">In Hospital</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.in_hospital" type="radio" name="hospital" :value="false" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Out Patient</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                        <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Special Leave Benefits for Women'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Health Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                        <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Study Leave'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Study Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Type</label>
                                        <select v-model="formData.completion_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                            <option value="">Select Type</option>
                                            <option value="Completion of Master's Degree">Completion of Master's Degree</option>
                                            <option value="BAR/Board Examination Review">BAR/Board Examination Review</option>
                                        </select>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Others'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Leave Type Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                        <select v-model="formData.other_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                            <option value="">Select Type</option>
                                            <option value="Monetization of Leave Credits">Monetization of Leave Credits</option>
                                            <option value="Terminal Leave">Terminal Leave</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                            <button @click="createLeave" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-check"></i>
                                Create
                            </button>
                            <button @click="closeCreateModal" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        <!-- Edit Leave Modal -->
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 dark:border-blue-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Leave
                        </h3>
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                            <div v-if="formErrors['submit']" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-sm">
                                {{ formErrors['submit'] }}
                            </div>

                            <div class="space-y-4">
                                <!-- Employee Selection -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Employee <span class="text-red-500">*</span></label>
                                    <select v-model.number="formData.employee_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500">
                                        <option value="0">Select Employee</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }} ({{ emp.employee_id }})</option>
                                    </select>
                                    <p v-if="formErrors['employee_id']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['employee_id'] }}</p>
                                </div>

                                <!-- Date of Filing (Disabled in Edit) -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Filing</label>
                                    <input v-model="formData.date_of_filing" type="date" disabled class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-75" />
                                </div>

                                <!-- Type of Leave -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type of Leave <span class="text-red-500">*</span></label>
                                    <select v-model="formData.type_of_leave" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500">
                                        <option v-for="type in leaveTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>

                                <!-- Number of Working Days Applied For -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Working Days Applied For <span class="text-red-500">*</span></label>
                                    <input v-model.number="formData.number_of_working_days_applied_for" type="number" min="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                </div>

                                <!-- Inclusive Dates -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Inclusive Dates <span class="text-red-500">*</span></label>
                                    <div class="space-y-2">
                                        <div class="flex gap-2 items-end">
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                                                <input v-model="formData.newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                                                <input v-model="formData.newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                            </div>
                                            <button @click.prevent="addInclusiveDate" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="(entry, idx) in formData.inclusive_dates" :key="idx" @click.prevent="editInclusiveDate(idx)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                                                <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                                                {{ formatInclusiveDate(entry) }}
                                                <button @click.stop="removeInclusiveDate(idx)" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-bold">×</button>
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="formErrors['inclusive_dates']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['inclusive_dates'] }}</p>
                                </div>

                                <!-- Off Days -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Off Days</label>
                                    <input v-model="formData.off_days" type="text" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500" />
                                </div>

                                <!-- Conditional Fields -->
                                <div v-if="['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.type_of_leave)" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Location Details</h3>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.within_philippines" type="radio" name="location_edit" :value="true" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Within Philippines</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.within_philippines" type="radio" name="location_edit" :value="false" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Abroad</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                                        <textarea v-model="formData.purpose" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Sick Leave'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Medical Details</h3>
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.in_hospital" type="radio" name="hospital_edit" :value="true" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">In Hospital</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input v-model="formData.in_hospital" type="radio" name="hospital_edit" :value="false" class="cursor-pointer" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">Out Patient</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                        <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Special Leave Benefits for Women'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Health Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                        <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500"></textarea>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Study Leave'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Study Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Type</label>
                                        <select v-model="formData.completion_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Type</option>
                                            <option value="Completion of Master's Degree">Completion of Master's Degree</option>
                                            <option value="BAR/Board Examination Review">BAR/Board Examination Review</option>
                                        </select>
                                    </div>
                                </div>

                                <div v-if="formData.type_of_leave === 'Others'" class="space-y-4 border-t pt-4">
                                    <h3 class="font-medium text-gray-700 dark:text-gray-300">Leave Type Details</h3>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                        <select v-model="formData.other_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500">
                                            <option value="">Select Type</option>
                                            <option value="Monetization of Leave Credits">Monetization of Leave Credits</option>
                                            <option value="Terminal Leave">Terminal Leave</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                            <button @click="updateLeave" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-save"></i>
                                Save
                            </button>
                            <button @click="closeEditModal" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

        <!-- Delete Leave Modal -->
        <Teleport to="body" v-if="showDeleteModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 dark:border-red-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Leave
                        </h3>
                        <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-900 dark:text-gray-100">
                                    Are you sure you want to delete leave <span class="font-semibold">{{ leaveToDelete?.control_no }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. All associated data will be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="deleteLeave" :disabled="deleting" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-trash-alt"></i>
                                {{ deleting ? 'Deleting...' : 'Delete' }}
                            </button>
                            <button @click="closeDeleteModal" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </AuthenticatedLayout>
    </template>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
