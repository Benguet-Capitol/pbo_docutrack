<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    fk_office_id: number;
    designation: string;
}

const employees = ref<Employee[]>([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref<'id' | 'employee_id' | 'name' | 'designation'>('id');
const sortOrder = ref<'asc' | 'desc'>('asc');
const activeDropdown = ref<number | null>(null);
const showCreateModal = ref(false);

const formData = ref({
    employee_id: '',
    name: '',
    designation: '',
    fk_office_id: ''
});

const formErrors = ref<Record<string, string>>({});

const filteredEmployees = computed(() => {
    let result = employees.value.filter((employee) => {
        const query = searchQuery.value.toLowerCase();
        return (
            employee.employee_id.toLowerCase().includes(query) ||
            employee.name.toLowerCase().includes(query) ||
            employee.designation.toLowerCase().includes(query)
        );
    });

    result.sort((a, b) => {
        let aVal: any = a[sortBy.value];
        let bVal: any = b[sortBy.value];

        if (aVal === null || aVal === undefined) aVal = '';
        if (bVal === null || bVal === undefined) bVal = '';

        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const totalPages = computed(() => Math.ceil(filteredEmployees.value.length / itemsPerPage.value));

const paginatedEmployees = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredEmployees.value.slice(start, end);
});

onMounted(async () => {
    try {
        const response = await fetch('/api/employees', {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
            },
        });
        if (!response.ok) throw new Error('Failed to fetch employees');
        const data = await response.json();
        employees.value = data.data || data;
    } catch (err: any) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
});

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const toggleSort = (field: 'id' | 'employee_id' | 'name' | 'designation') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const toggleDropdown = (employeeId: number) => {
    activeDropdown.value = activeDropdown.value === employeeId ? null : employeeId;
};

const handleEditEmployee = (employeeId: number) => {
    console.log('Edit employee:', employeeId);
};

const handleDeleteEmployee = (employeeId: number) => {
    console.log('Delete employee:', employeeId);
};

const openCreateModal = () => {
    formData.value = {
        employee_id: '',
        name: '',
        designation: '',
        fk_office_id: ''
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.employee_id.trim()) {
        formErrors.value['employee_id'] = 'Employee ID is required';
    }
    
    if (!formData.value.name.trim()) {
        formErrors.value['name'] = 'Name is required';
    }
    
    if (!formData.value.designation.trim()) {
        formErrors.value['designation'] = 'Designation is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const handleCreateEmployee = async () => {
    if (!validateForm()) return;
    
    try {
        const response = await fetch('/api/employees', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                Authorization: `Bearer ${localStorage.getItem('token')}`,
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            throw new Error('Failed to create employee');
        }
        
        const newEmployee = await response.json();
        employees.value.push(newEmployee);
        closeCreateModal();
    } catch (e) {
        formErrors.value['submit'] = e instanceof Error ? e.message : 'An error occurred';
    }
};
</script>

<template>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Employees
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Add Employee
                        </button>
                        <div class="flex items-center gap-3">
                                <i class="fas fa-search text-gray-400"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search employees..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading employees...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Employees</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="employees.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No employees found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by adding a new employee</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-10">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('employee_id')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Employee ID
                                        <span v-if="sortBy === 'employee_id'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Name
                                        <span v-if="sortBy === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('designation')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Designation
                                        <span v-if="sortBy === 'designation'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="employee in paginatedEmployees"
                                :key="employee.id"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                            >
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ employee.employee_id }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ employee.name }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ employee.designation }}
                                </td>
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="relative inline-block">
                                        <button 
                                            @click="toggleDropdown(employee.id)" 
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                                        >
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div 
                                            v-if="activeDropdown === employee.id" 
                                            class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-600 overflow-hidden"
                                        >
                                            <button 
                                                @click="handleEditEmployee(employee.id); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 flex items-center gap-2"
                                            >
                                                <i class="fas fa-pencil-alt"></i>Edit
                                            </button>
                                            <button 
                                                @click="handleDeleteEmployee(employee.id); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 border-t border-gray-200 dark:border-gray-600 flex items-center gap-2"
                                            >
                                                <i class="fas fa-trash-alt"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="!loading && employees.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredEmployees.length) }}</span> of <span class="font-semibold">{{ filteredEmployees.length }}</span> employees
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="changePage(1)"
                            :disabled="currentPage === 1"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Prev
                        </button>
                        <div class="flex gap-0.5">
                            <button
                                v-for="page in totalPages"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-2 py-1 text-xs rounded transition-colors',
                                    currentPage === page
                                        ? 'bg-emerald-600 text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                        <button
                            @click="changePage(totalPages)"
                            :disabled="currentPage === totalPages"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Employee Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-user-plus text-emerald-600 dark:text-emerald-400"></i>
                            Add Employee
                        </h3>
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Employee ID -->
                            <div class="space-y-2">
                                <label for="employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee ID</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-id-card absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.employee_id"
                                        id="employee_id"
                                        type="text"
                                        placeholder="Employee ID"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                            </div>

                            <!-- Name -->
                            <div class="space-y-2">
                                <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.name"
                                        id="name"
                                        type="text"
                                        placeholder="Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                            </div>

                            <!-- Designation -->
                            <div class="space-y-2">
                                <label for="designation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Designation</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-briefcase absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.designation"
                                        id="designation"
                                        type="text"
                                        placeholder="Designation"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.designation" class="text-red-500 text-xs">{{ formErrors.designation }}</span>
                            </div>

                            <!-- Office -->
                            <div class="space-y-2">
                                <label for="fk_office_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.fk_office_id"
                                        id="fk_office_id"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Office</option>
                                        <option value="1">Main Office</option>
                                        <option value="2">Branch Office</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="handleCreateEmployee"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-save"></i>
                            Save
                        </button>
                        <button
                            @click="closeCreateModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
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
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
