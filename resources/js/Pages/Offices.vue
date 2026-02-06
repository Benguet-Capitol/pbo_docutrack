<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import { ref, onMounted, computed } from 'vue';

interface Office {
    id: number;
    office_abbreviation: string;
    office_name: string;
    sub_office: string | null;
    fund: string | null;
    fpp_code: string | null;
    responsibility_code: string | null;
    branch: string | null;
}

const offices = ref<Office[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref<'id' | 'office_name' | 'office_abbreviation' | 'fpp_code'>('id');
const sortOrder = ref<'asc' | 'desc'>('asc');
const activeDropdown = ref<number | null>(null);
const showCreateModal = ref(false);

const formData = ref({
    office_name: '',
    office_abbreviation: '',
    sub_office: '',
    fund: '',
    fpp_code: '',
    responsibility_code: '',
    branch: ''
});

const formErrors = ref<Record<string, string>>({});

const filteredOffices = computed(() => {
    let filtered = offices.value.filter(office => 
        office.office_abbreviation.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.office_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (office.sub_office?.toLowerCase().includes(searchQuery.value.toLowerCase()) ?? false)
    );
    
    // Sort
    filtered.sort((a, b) => {
        let aVal = a[sortBy.value] || '';
        let bVal = b[sortBy.value] || '';
        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });
    
    return filtered;
});

const totalPages = computed(() => {
    return Math.ceil(filteredOffices.value.length / itemsPerPage.value);
});

const paginatedOffices = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredOffices.value.slice(start, end);
});

onMounted(async () => {
    try {
        const response = await fetch('/api/offices');
        if (!response.ok) {
            throw new Error('Failed to fetch offices');
        }
        offices.value = await response.json();
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'An error occurred';
    } finally {
        loading.value = false;
    }
});

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const toggleSort = (field: 'id' | 'office_name' | 'office_abbreviation' | 'fpp_code') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const toggleDropdown = (officeId: number) => {
    activeDropdown.value = activeDropdown.value === officeId ? null : officeId;
};

const handleEditOffice = (office: Office) => {
    console.log('Edit office:', office);
};

const handleDeleteOffice = (office: Office) => {
    if (confirm(`Are you sure you want to delete ${office.office_name}?`)) {
        console.log('Delete office:', office);
    }
};

const openCreateModal = () => {
    formData.value = {
        office_name: '',
        office_abbreviation: '',
        sub_office: '',
        fund: '',
        fpp_code: '',
        responsibility_code: '',
        branch: ''
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.office_name.trim()) {
        formErrors.value['office_name'] = 'Office Name is required';
    }
    
    if (!formData.value.office_abbreviation.trim()) {
        formErrors.value['office_abbreviation'] = 'Abbreviation is required';
    }
    
    if (!formData.value.fund.trim()) {
        formErrors.value['fund'] = 'Fund is required';
    }
    
    if (!formData.value.branch.trim()) {
        formErrors.value['branch'] = 'Branch is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const handleCreateOffice = async () => {
    if (!validateForm()) return;
    
    try {
        const response = await fetch('/api/offices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            throw new Error('Failed to create office');
        }
        
        const newOffice = await response.json();
        offices.value.push(newOffice);
        closeCreateModal();
    } catch (e) {
        formErrors.value['submit'] = e instanceof Error ? e.message : 'An error occurred';
    }
};
</script>

<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Offices
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Office
                        </button>
                        <div class="flex items-center gap-3">
                                <i class="fas fa-search text-gray-400"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search offices..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading offices...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Offices</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="offices.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No offices found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new office</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-40">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-10">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('office_name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Office Name
                                        <span v-if="sortBy === 'office_name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('office_abbreviation')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Abbreviation
                                        <span v-if="sortBy === 'office_abbreviation'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Fund</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('fpp_code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        FPP Code
                                        <span v-if="sortBy === 'fpp_code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Responsibility Code</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Branch</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="office in paginatedOffices"
                                :key="office.id"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                            >
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ office.office_name }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ office.office_abbreviation }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="office.fund">{{ office.fund }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                    <span v-if="office.fpp_code">{{ office.fpp_code }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                    <span v-if="office.responsibility_code">{{ office.responsibility_code }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="office.branch">{{ office.branch }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="relative inline-block">
                                        <button 
                                            @click="toggleDropdown(office.id)" 
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                                        >
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div 
                                            v-if="activeDropdown === office.id" 
                                            class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-600 overflow-hidden"
                                        >
                                            <button 
                                                @click="handleEditOffice(office); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 flex items-center gap-2"
                                            >
                                                <i class="fas fa-pencil-alt"></i>Edit
                                            </button>
                                            <button 
                                                @click="handleDeleteOffice(office); activeDropdown = null" 
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
                <div v-if="!loading && offices.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredOffices.length) }}</span> of <span class="font-semibold">{{ filteredOffices.length }}</span> offices
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

        <!-- Create Office Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-building text-emerald-600 dark:text-emerald-400"></i>
                            Create Office
                        </h3>
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Office Name -->
                            <div class="space-y-2">
                                <label for="office_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-qrcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.office_name"
                                        id="office_name"
                                        type="text"
                                        placeholder="Office Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.office_name" class="text-red-500 text-xs">{{ formErrors.office_name }}</span>
                            </div>

                            <!-- Abbreviation and Sub Office -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Abbreviation -->
                                <div class="space-y-2">
                                    <label for="office_abbreviation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Abbreviation</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.office_abbreviation"
                                            id="office_abbreviation"
                                            type="text"
                                            placeholder="Abbreviation"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                        />
                                    </div>
                                    <span v-if="formErrors.office_abbreviation" class="text-red-500 text-xs">{{ formErrors.office_abbreviation }}</span>
                                </div>

                                <!-- Sub Office -->
                                <div class="space-y-2">
                                    <label for="sub_office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Sub Office</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-window-restore absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.sub_office"
                                            id="sub_office"
                                            type="text"
                                            placeholder="Sub Office"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Fund -->
                            <div class="space-y-2">
                                <label for="fund" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Fund</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-money-bill absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.fund"
                                        id="fund"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Fund</option>
                                        <option value="General Fund">General Fund</option>
                                        <option value="Provincial Development Fund">Provincial Development Fund</option>
                                        <option value="Benguet General Hospital Economic Enterprise">Benguet General Hospital Economic Enterprise</option>
                                        <option value="Special Education Fund">Special Education Fund</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.fund" class="text-red-500 text-xs">{{ formErrors.fund }}</span>
                            </div>

                            <!-- FPP Code and Responsibility Code -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- FPP Code -->
                                <div class="space-y-2">
                                    <label for="fpp_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">FPP Code</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-invoice absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.fpp_code"
                                            id="fpp_code"
                                            type="text"
                                            placeholder="FPP Code"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                        />
                                    </div>
                                </div>

                                <!-- Responsibility Code -->
                                <div class="space-y-2">
                                    <label for="responsibility_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Responsibility Code</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-lines absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.responsibility_code"
                                            id="responsibility_code"
                                            type="text"
                                            placeholder="Responsibility Code"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Branch -->
                            <div class="space-y-2">
                                <label for="branch" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Branch</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-sitemap absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.branch"
                                        id="branch"
                                        type="text"
                                        placeholder="Branch"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.branch" class="text-red-500 text-xs">{{ formErrors.branch }}</span>
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
                            @click="handleCreateOffice"
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
