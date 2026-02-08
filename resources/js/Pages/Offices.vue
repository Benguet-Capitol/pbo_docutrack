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
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Office Button: Calls openCreateModal() to show the create form modal -->
                        <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Office
                        </button>
                        <div class="flex items-center gap-3">
                                <i class="fas fa-search text-gray-400"></i>
                                <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search offices..."
                                    class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                                />
                            <!-- Items Per Page Selector: Controls number of items displayed per page -->
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

                <!-- Loading State: v-if shows spinner while data is being fetched -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading offices...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State: v-else-if displays error message if fetch fails -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Offices</h3>
                            <!-- Displays the error message from error ref -->
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State: v-else-if shows when no offices exist -->
                <div v-else-if="offices.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No offices found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new office</p>
                </div>

                <!-- Data Table: v-else shows when offices data is loaded -->
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
                        <!-- Table Header: Contains sortable column headers -->
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <!-- Office Name Header: Sortable, calls toggleSort('office_name') when clicked -->
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
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('fund')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Fund
                                        <span v-if="sortBy === 'fund'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('fpp_code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        FPP Code
                                        <span v-if="sortBy === 'fpp_code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('responsibility_code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Responsibility Code
                                        <span v-if="sortBy === 'responsibility_code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('branch')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Branch
                                        <span v-if="sortBy === 'branch'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body: Renders rows for each office in paginatedOffices -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- v-for loops through paginatedOffices (filtered, sorted, and paginated data) -->
                            <!-- :key uses office.id for efficient Vue rendering -->
                            <tr
                                v-for="office in paginatedOffices"
                                :key="office.id"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                            >
                                <!-- Office Name Column: Displays the office_name property -->
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ office.office_name }}
                                </td>
                                <!-- Abbreviation Column: Displays the office_abbreviation property -->
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ office.office_abbreviation }}
                                </td>
                                <!-- Fund Column: Shows fund or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="office.fund">{{ office.fund }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- FPP Code Column: Shows code or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                    <span v-if="office.fpp_code">{{ office.fpp_code }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Responsibility Code Column: Shows code or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                                    <span v-if="office.responsibility_code">{{ office.responsibility_code }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Branch Column: Shows branch or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="office.branch">{{ office.branch }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Actions Column: Contains edit/delete dropdown menu -->
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="relative inline-block">
                                        <!-- Action Menu Button: Calls toggleDropdown(office.id) to show/hide dropdown -->
                                        <button 
                                            @click="toggleDropdown(office.id)" 
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                                        >
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <!-- Dropdown Menu: v-if shows menu only when activeDropdown === office.id -->
                                        <div 
                                            v-if="activeDropdown === office.id" 
                                            class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-600 overflow-hidden"
                                        >
                                            <!-- Edit Button: Calls handleEditOffice(office) and closes the dropdown -->
                                            <button 
                                                @click="handleEditOffice(office); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 flex items-center gap-2"
                                            >
                                                <i class="fas fa-pencil-alt"></i>Edit
                                            </button>
                                            <!-- Delete Button: Calls handleDeleteOffice(office) and closes the dropdown -->
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

                <!-- Pagination Controls: v-if shows only when data is loaded and exists -->
                <div v-if="!loading && offices.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Pagination Info: Displays current range and total count -->
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredOffices.length) }}</span> of <span class="font-semibold">{{ filteredOffices.length }}</span> offices
                    </div>
                    <!-- Pagination Buttons -->
                    <div class="flex items-center gap-1">
                        <!-- First Page Button: Navigates to page 1, disabled when on page 1 -->
                        <button
                            @click="changePage(1)"
                            :disabled="currentPage === 1"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <!-- Previous Page Button: Navigates to previous page, disabled on page 1 -->
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Prev
                        </button>
                        <!-- Page Number Buttons: v-for loops through all pages, highlights current page -->
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

        <!-- Create Office Modal: Teleports to body when showCreateModal is true -->
        <!-- @click.self="closeCreateModal" closes modal when clicking outside the dialog -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-building text-emerald-600 dark:text-emerald-400"></i>
                            Create Office
                        </h3>
                        <!-- Close Button: Calls closeCreateModal() when clicked -->
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for creating a new office -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Office Name Field: Required field, v-model binds to formData.office_name -->
                            <div class="space-y-2">
                                <label for="office_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-qrcode absolute left-3 text-gray-400 text-sm"></i>
                                    <!-- v-model binds input to formData.office_name for form submission -->
                                    <input
                                        v-model="formData.office_name"
                                        id="office_name"
                                        type="text"
                                        placeholder="Office Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <!-- Shows validation error if field is invalid -->
                                <span v-if="formErrors.office_name" class="text-red-500 text-xs">{{ formErrors.office_name }}</span>
                            </div>

                            <!-- Abbreviation and Sub Office: Two-column grid layout -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Abbreviation Field: Required, v-model binds to formData.office_abbreviation -->
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
                                    <!-- Shows validation error if field is invalid -->
                                    <span v-if="formErrors.office_abbreviation" class="text-red-500 text-xs">{{ formErrors.office_abbreviation }}</span>
                                </div>

                                <!-- Sub Office Field: Optional, v-model binds to formData.sub_office -->
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

                            <!-- Fund Field: Required dropdown field, v-model binds to formData.fund -->
                            <div class="space-y-2">
                                <label for="fund" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Fund</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-money-bill absolute left-3 text-gray-400 text-sm"></i>
                                    <!-- v-model binds select to formData.fund -->
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

                    <!-- Modal Footer: Contains Save and Cancel buttons -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <!-- Save Button: Calls handleCreateOffice() to submit the form -->
                        <button
                            @click="handleCreateOffice"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-save"></i>
                            Save
                        </button>
                        <!-- Cancel Button: Calls closeCreateModal() to close the form -->
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

        <!-- Edit Office Modal: Teleports to body when showEditModal is true -->
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Office
                        </h3>
                        <!-- Close Button: Calls closeEditModal() when clicked -->
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for editing an office -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Office Name Field -->
                            <div class="space-y-2">
                                <label for="edit_office_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-qrcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.office_name"
                                        id="edit_office_name"
                                        type="text"
                                        placeholder="Office Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <span v-if="formErrors.office_name" class="text-red-500 text-xs">{{ formErrors.office_name }}</span>
                            </div>

                            <!-- Abbreviation and Sub Office -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-2">
                                    <label for="edit_office_abbreviation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Abbreviation</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.office_abbreviation"
                                            id="edit_office_abbreviation"
                                            type="text"
                                            placeholder="Abbreviation"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                        />
                                    </div>
                                    <span v-if="formErrors.office_abbreviation" class="text-red-500 text-xs">{{ formErrors.office_abbreviation }}</span>
                                </div>

                                <div class="space-y-2">
                                    <label for="edit_sub_office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Sub Office</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-window-restore absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.sub_office"
                                            id="edit_sub_office"
                                            type="text"
                                            placeholder="Sub Office"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Fund Field -->
                            <div class="space-y-2">
                                <label for="edit_fund" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Fund</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-money-bill absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.fund"
                                        id="edit_fund"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
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
                                <div class="space-y-2">
                                    <label for="edit_fpp_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">FPP Code</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-invoice absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.fpp_code"
                                            id="edit_fpp_code"
                                            type="text"
                                            placeholder="FPP Code"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label for="edit_responsibility_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Responsibility Code</label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-file-lines absolute left-3 text-gray-400 text-sm"></i>
                                        <input
                                            v-model="formData.responsibility_code"
                                            id="edit_responsibility_code"
                                            type="text"
                                            placeholder="Responsibility Code"
                                            class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Branch -->
                            <div class="space-y-2">
                                <label for="edit_branch" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Branch</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-sitemap absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.branch"
                                        id="edit_branch"
                                        type="text"
                                        placeholder="Branch"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
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

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="handleUpdateOffice"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-save"></i>
                            Update
                        </button>
                        <button
                            @click="closeEditModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Office Modal: Confirmation dialog for deleting an office -->
        <Teleport to="body" v-if="showDeleteModal && officeToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows warning icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Office
                        </h3>
                        <!-- Close Button -->
                        <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Confirmation message -->
                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    Are you sure you want to delete <span class="font-semibold">{{ officeToDelete.office_name }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. All associated data will be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer: Confirm and Cancel buttons -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="confirmDeleteOffice"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-trash-alt"></i>
                            Delete
                        </button>
                        <button
                            @click="closeDeleteModal"
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

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import { ref, onMounted, computed } from 'vue';

/**
 * Office interface defines the structure of office data
 * Represents a single office entity with all its properties
 */
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

// ============== Reactive State Management ==============

/** Stores the list of all offices fetched from the API */
const offices = ref<Office[]>([]);

/** Tracks the loading state of the page */
const loading = ref(true);

/** Stores error messages if any API call fails */
const error = ref<string | null>(null);

/** Stores the search query for filtering offices */
const searchQuery = ref('');

/** Current page number for pagination */
const currentPage = ref(1);

/** Number of items to display per page */
const itemsPerPage = ref(10);

/** The field to sort offices by */
const sortBy = ref<'id' | 'office_name' | 'office_abbreviation' | 'fpp_code' | 'responsibility_code' | 'branch' | 'fund' | 'sub_office'>('id');

/** Sort direction: 'asc' for ascending, 'desc' for descending */
const sortOrder = ref<'asc' | 'desc'>('asc');

/** Tracks which office's dropdown menu is currently open (by office ID) */
const activeDropdown = ref<number | null>(null);

/** Controls visibility of the Create Office modal */
const showCreateModal = ref(false);

/** Controls visibility of the Edit Office modal */
const showEditModal = ref(false);

/** Controls visibility of the Delete Office modal */
const showDeleteModal = ref(false);

/** Stores the office being edited */
const editingOffice = ref<Office | null>(null);

/** Stores the office to be deleted */
const officeToDelete = ref<Office | null>(null);

// ============== Form Data & Validation ==============

/** Form data for creating a new office */
const formData = ref({
    office_name: '',
    office_abbreviation: '',
    sub_office: '',
    fund: '',
    fpp_code: '',
    responsibility_code: '',
    branch: ''
});

/** Stores validation errors for form fields */
const formErrors = ref<Record<string, string>>({});

// ============== Computed Properties ==============

/**
 * filteredOffices: Filters and sorts offices based on search query and sort settings
 * - Filters offices by multiple fields (abbreviation, name, fund, codes, branch, etc.)
 * - Applies sorting based on sortBy and sortOrder
 * Returns the filtered and sorted array of offices
 */
const filteredOffices = computed(() => {
    let filtered = offices.value.filter(office => 
        office.office_abbreviation.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.office_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.fund?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.fpp_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.responsibility_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        office.branch?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
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

/**
 * totalPages: Calculates the total number of pages needed for pagination
 * Based on the filtered offices count and items per page
 */
const totalPages = computed(() => {
    return Math.ceil(filteredOffices.value.length / itemsPerPage.value);
});

/**
 * paginatedOffices: Slices filtered offices to show only the current page
 * Calculates start and end indices based on currentPage and itemsPerPage
 * Returns the offices for the current page only
 */
const paginatedOffices = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredOffices.value.slice(start, end);
});

// ============== Lifecycle Hooks ==============

/**
 * onMounted: Fetches offices from the API when component is mounted
 * - Sets loading state to true initially
 * - Makes API request to /api/offices
 * - Populates offices.value with response data
 * - Handles errors and sets loading state to false on completion
 */
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

// ============== Utility Functions ==============

/**
 * changePage: Navigate to a specific page
 * @param {number} page - The page number to navigate to
 * Validates that page is within valid range (1 to totalPages)
 */
const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

/**
 * toggleSort: Toggle sorting by a specific field
 * @param {string} field - The field to sort by
 * If already sorting by this field, toggles between asc/desc
 * If sorting by a different field, sets it as the new sort and resets to asc
 */
const toggleSort = (field: 'id' | 'office_name' | 'office_abbreviation' | 'fpp_code' | 'responsibility_code' | 'branch' | 'fund' | 'sub_office') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

/**
 * toggleDropdown: Toggle the action dropdown menu for an office
 * @param {number} officeId - The ID of the office
 * If dropdown is already open for this office, close it
 * Otherwise, open the dropdown for this office (closes any other open dropdowns)
 */
const toggleDropdown = (officeId: number) => {
    activeDropdown.value = activeDropdown.value === officeId ? null : officeId;
};

/**
 * handleEditOffice: Opens the Edit Office modal with the selected office data
 * @param {Office} office - The office object to edit
 * Populates formData with the current office values
 * Sets editingOffice to the office being edited
 * Opens the edit modal
 */
const handleEditOffice = (office: Office) => {
    editingOffice.value = office;
    formData.value = {
        office_name: office.office_name,
        office_abbreviation: office.office_abbreviation,
        sub_office: office.sub_office || '',
        fund: office.fund || '',
        fpp_code: office.fpp_code || '',
        responsibility_code: office.responsibility_code || '',
        branch: office.branch || ''
    };
    formErrors.value = {};
    showEditModal.value = true;
};

/**
 * closeEditModal: Closes the Edit Office modal
 * Clears the editingOffice reference
 */
const closeEditModal = () => {
    showEditModal.value = false;
    editingOffice.value = null;
};

/**
 * handleUpdateOffice: Submits the form to update an existing office
 * - Validates form data first
 * - Makes PUT request to /api/offices/{id} with updated data
 * - On success: updates the office in the offices array and closes the modal
 * - On error: sets formErrors['submit'] with the error message
 */
const handleUpdateOffice = async () => {
    if (!validateForm() || !editingOffice.value) return;
    
    try {
        const response = await fetch(`/api/offices/${editingOffice.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            throw new Error('Failed to update office');
        }
        
        const updatedOffice = await response.json();
        const index = offices.value.findIndex(o => o.id === editingOffice.value!.id);
        if (index !== -1) {
            offices.value[index] = updatedOffice;
        }
        closeEditModal();
    } catch (e) {
        formErrors.value['submit'] = e instanceof Error ? e.message : 'An error occurred';
    }
};

/**
 * handleDeleteOffice: Opens the Delete Office confirmation modal
 * @param {Office} office - The office object to delete
 * Sets officeToDelete to the office being confirmed for deletion
 * Opens the delete confirmation modal
 */
const handleDeleteOffice = (office: Office) => {
    officeToDelete.value = office;
    showDeleteModal.value = true;
};

/**
 * closeDeleteModal: Closes the Delete Office modal
 * Clears the officeToDelete reference
 */
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    officeToDelete.value = null;
};

/**
 * confirmDeleteOffice: Confirms and executes the deletion of an office
 * - Makes DELETE request to /api/offices/{id}
 * - On success: removes the office from the offices array and closes the modal
 * - On error: shows an error message
 */
const confirmDeleteOffice = async () => {
    if (!officeToDelete.value) return;
    
    try {
        const response = await fetch(`/api/offices/${officeToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to delete office');
        }
        
        offices.value = offices.value.filter(o => o.id !== officeToDelete.value!.id);
        closeDeleteModal();
    } catch (e) {
        alert(e instanceof Error ? e.message : 'An error occurred');
    }
};

/**
 * openCreateModal: Opens the Create Office modal
 * - Resets formData to empty values
 * - Clears any existing form errors
 * - Sets showCreateModal to true
 */
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

/**
 * closeCreateModal: Closes the Create Office modal
 * Sets showCreateModal to false
 */
const closeCreateModal = () => {
    showCreateModal.value = false;
};

/**
 * validateForm: Validates the form data for creating an office
 * Required fields: office_name, office_abbreviation, fund, branch
 * Returns true if all validations pass, false otherwise
 * Sets formErrors with specific error messages for invalid fields
 */
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

/**
 * handleCreateOffice: Submits the form to create a new office
 * - Validates form data first
 * - Makes POST request to /api/offices with form data
 * - On success: adds the new office to the offices array and closes the modal
 * - On error: sets formErrors['submit'] with the error message
 */
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

<!-- Scoped Styles: Modal animations and transitions -->
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
