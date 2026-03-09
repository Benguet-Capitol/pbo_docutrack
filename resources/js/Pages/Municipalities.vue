<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Municipalities
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Municipality Button: Calls openCreateModal() to show the create form modal -->
                        <button v-if="canCreateMunicipalities" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Municipality
                        </button>
                        <div :class="['flex items-center gap-3', !canCreateMunicipalities && 'sm:ml-auto']">
                                <i class="fas fa-search text-gray-400"></i>
                                <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search municipalities..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading municipalities...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State: v-else-if displays error message if fetch fails -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Municipalities</h3>
                            <!-- Displays the error message from error ref -->
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State: v-else-if shows when no municipalities exist -->
                <div v-else-if="municipalities.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No municipalities found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new municipality</p>
                </div>

                <!-- Data Table: v-else shows when municipalities data is loaded -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-40">
                            <col class="w-40">
                            <col class="w-40">
                            <col class="w-40">
                            <col class="w-20">
                        </colgroup>
                        <!-- Table Header: Contains sortable column headers -->
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <!-- Municipality Name Header: Sortable, calls toggleSort('name') when clicked -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Municipality Name
                                        <span v-if="sortBy === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Code Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Code
                                        <span v-if="sortBy === 'code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Municipal Budget Officer Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('municipal_budget_officer')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Budget Officer
                                        <span v-if="sortBy === 'municipal_budget_officer'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Municipality Class Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('city_class')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Municipality Class
                                        <span v-if="sortBy === 'city_class'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body: Renders rows for each municipality in paginatedMunicipalities -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- v-for loops through paginatedMunicipalities (filtered, sorted, and paginated data) -->
                            <!-- :key uses municipality.id for efficient Vue rendering -->
                            <tr
                                v-for="municipality in paginatedMunicipalities"
                                :key="municipality.id"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                            >
                                <!-- Municipality Name Column -->
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ municipality.name }}
                                </td>
                                <!-- Code Column: Shows code or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-mono">
                                    <span v-if="municipality.code">{{ municipality.code }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Municipal Budget Officer Column: Shows value or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="municipality.municipal_budget_officer">{{ municipality.municipal_budget_officer }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Municipality Class Column: Shows value or dash (-) if not available -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="municipality.city_class">{{ municipality.city_class }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Actions Column: Contains edit/delete dropdown menu -->
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button 
                                            v-if="canEditMunicipalities"
                                            @click.stop="handleEditMunicipality(municipality)" 
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button -->
                                        <button 
                                            v-if="canDeleteMunicipalities"
                                            @click.stop="handleDeleteMunicipality(municipality)" 
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

                <!-- Pagination Controls: v-if shows only when data is loaded and exists -->
                <div v-if="!loading && municipalities.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Pagination Info: Displays current range and total count -->
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredMunicipalities.length) }}</span> of <span class="font-semibold">{{ filteredMunicipalities.length }}</span> municipalities
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

        <!-- Create Municipality Modal: Teleports to body when showCreateModal is true -->
        <!-- @click.self="closeCreateModal" closes modal when clicking outside the dialog -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-city text-emerald-600 dark:text-emerald-400"></i>
                            Create Municipality
                        </h3>
                        <!-- Close Button: Calls closeCreateModal() when clicked -->
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for creating a new municipality -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Municipality Name Field: Required field, v-model binds to formData.name -->
                            <div class="space-y-2">
                                <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-city absolute left-3 text-gray-400 text-sm"></i>
                                    <!-- v-model binds input to formData.name for form submission -->
                                    <input
                                        v-model="formData.name"
                                        id="name"
                                        type="text"
                                        placeholder="Municipality Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <!-- Shows validation error if field is invalid -->
                                <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                            </div>

                            <!-- Code Field: Required, v-model binds to formData.code -->
                            <div class="space-y-2">
                                <label for="code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Code</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.code"
                                        id="code"
                                        type="text"
                                        placeholder="Municipality Code"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <!-- Shows validation error if field is invalid -->
                                <span v-if="formErrors.code" class="text-red-500 text-xs">{{ formErrors.code }}</span>
                            </div>

                            <!-- Municipality Class Field: Optional -->
                            <div class="space-y-2">
                                <label for="municipality_class" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Class</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-tag absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.city_class"
                                        id="city_class"
                                        type="text"
                                        placeholder="e.g., 1st Class, 2nd Class"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>

                            <!-- Municipal Budget Officer Field: Optional -->
                            <div class="space-y-2">
                                <label for="municipal_budget_officer" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipal Budget Officer</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-tie absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.municipal_budget_officer"
                                        id="municipal_budget_officer"
                                        type="text"
                                        placeholder="Budget Officer Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>

                            <!-- Representative Field: Optional -->
                            <div class="space-y-2">
                                <label for="representative" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Representative</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-person-booth absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.representative"
                                        id="representative"
                                        type="text"
                                        placeholder="Representative Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer: Contains Save and Cancel buttons -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <!-- Save Button: Calls handleCreateMunicipality() to submit the form -->
                        <button
                            @click="handleCreateMunicipality"
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

        <!-- Edit Municipality Modal: Teleports to body when showEditModal is true -->
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Municipality
                        </h3>
                        <!-- Close Button: Calls closeEditModal() when clicked -->
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for editing a municipality -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Municipality Name Field -->
                            <div class="space-y-2">
                                <label for="edit_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Name</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-city absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.name"
                                        id="edit_name"
                                        type="text"
                                        placeholder="Municipality Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                            </div>

                            <!-- Code Field -->
                            <div class="space-y-2">
                                <label for="edit_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Code</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.code"
                                        id="edit_code"
                                        type="text"
                                        placeholder="Municipality Code"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <span v-if="formErrors.code" class="text-red-500 text-xs">{{ formErrors.code }}</span>
                            </div>

                            <!-- Municipality Class Field -->
                            <div class="space-y-2">
                                <label for="edit_city_class" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Class</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-tag absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.city_class"
                                        id="edit_city_class"
                                        type="text"
                                        placeholder="e.g., 1st Class, 2nd Class"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Municipal Budget Officer Field -->
                            <div class="space-y-2">
                                <label for="edit_municipal_budget_officer" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipal Budget Officer</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-tie absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.municipal_budget_officer"
                                        id="edit_municipal_budget_officer"
                                        type="text"
                                        placeholder="Budget Officer Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Representative Field -->
                            <div class="space-y-2">
                                <label for="edit_representative" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Representative</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-person-booth absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.representative"
                                        id="edit_representative"
                                        type="text"
                                        placeholder="Representative Name"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
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
                            @click="handleUpdateMunicipality"
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

        <!-- Delete Municipality Modal: Confirmation dialog for deleting a municipality -->
        <Teleport to="body" v-if="showDeleteModal && municipalityToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows warning icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Municipality
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
                                    Are you sure you want to delete <span class="font-semibold">{{ municipalityToDelete.name }}</span>?
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
                            @click="confirmDeleteMunicipality"
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
import Toast from '@/Components/Toast.vue';
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Municipality interface defines the structure of municipality data
 * Represents a single municipality entity with all its properties
 */
interface Municipality {
    id: number;
    name: string;
    code: string;
    city_class: string | null;
    municipal_budget_officer: string | null;
    representative: string | null;
}

// ============== Toast Component Reference ==============

/** Reference to the Toast component for displaying notifications */
const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Reactive State Management ==============

/** Stores the list of all municipalities fetched from the API */
const municipalities = ref<Municipality[]>([]);

/** Tracks the loading state of the page */
const loading = ref(true);

/** Stores error messages if any API call fails */
const error = ref<string | null>(null);

/** Stores the search query for filtering municipalities */
const searchQuery = ref('');

/** Current page number for pagination */
const currentPage = ref(1);

/** Number of items to display per page */
const itemsPerPage = ref(10);

/** The field to sort municipalities by */
const sortBy = ref<'id' | 'name' | 'code' | 'city_class' | 'municipal_budget_officer'>('id');

/** Sort direction: 'asc' for ascending, 'desc' for descending */
const sortOrder = ref<'asc' | 'desc'>('asc');

/** Controls visibility of the Create Municipality modal */
const showCreateModal = ref(false);

/** Controls visibility of the Edit Municipality modal */
const showEditModal = ref(false);

/** Controls visibility of the Delete Municipality modal */
const showDeleteModal = ref(false);

/** Stores the municipality being edited */
const editingMunicipality = ref<Municipality | null>(null);

/** Stores the municipality to be deleted */
const municipalityToDelete = ref<Municipality | null>(null);

// ============== Form Data & Validation ==============

/** Form data for creating a new municipality */
const formData = ref({
    name: '',
    code: '',
    city_class: '',
    municipal_budget_officer: '',
    representative: ''
});

/** Stores validation errors for form fields */
const formErrors = ref<Record<string, string>>({});

const page = usePage();
const usertype = computed(() => page.props.auth.user?.usertype || '');

/**
 * Check if current user can create municipalities
 * Only Developer and Administrator can create municipalities
 */
const canCreateMunicipalities = computed(() => {
    return ['Developer', 'Administrator'].includes(usertype.value);
});

/**
 * Check if current user can edit municipalities
 * All authenticated users can edit municipalities (they all have municipalities.edit permission)
 */
const canEditMunicipalities = computed(() => {
    return true;
});

/**
 * Check if current user can delete municipalities
 * Only Developer and Administrator can delete municipalities
 */
const canDeleteMunicipalities = computed(() => {
    return ['Developer', 'Administrator'].includes(usertype.value);
});

// ============== Computed Properties ==============

/**
 * filteredMunicipalities: Filters and sorts municipalities based on search query and sort settings
 * - Filters municipalities by multiple fields (name, code, budget officer, representative, etc.)
 * - Applies sorting based on sortBy and sortOrder
 * Returns the filtered and sorted array of municipalities
 */
const filteredMunicipalities = computed(() => {
    let filtered = municipalities.value.filter(municipality => 
        municipality.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        municipality.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        municipality.city_class?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        municipality.municipal_budget_officer?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        municipality.representative?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
    
    // Sort
    filtered.sort((a, b) => {
        let aVal: any = a[sortBy.value] || '';
        let bVal: any = b[sortBy.value] || '';
        
        // Handle numeric sorting for id
        if (sortBy.value === 'id') {
            aVal = Number(aVal);
            bVal = Number(bVal);
        } else {
            aVal = aVal.toString();
            bVal = bVal.toString();
        }
        
        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });
    
    return filtered;
});

/**
 * totalPages: Calculates the total number of pages needed for pagination
 * Based on the filtered municipalities count and items per page
 */
const totalPages = computed(() => {
    return Math.ceil(filteredMunicipalities.value.length / itemsPerPage.value);
});

/**
 * paginatedMunicipalities: Slices filtered municipalities to show only the current page
 * Calculates start and end indices based on currentPage and itemsPerPage
 * Returns the municipalities for the current page only
 */
const paginatedMunicipalities = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredMunicipalities.value.slice(start, end);
});

// ============== Lifecycle Hooks ==============

/**
 * onMounted: Fetches municipalities from the API when component is mounted
 * - Sets loading state to true initially
 * - Makes API request to /api/municipalities
 * - Populates municipalities.value with response data
 * - Handles errors and sets loading state to false on completion
 */
onMounted(async () => {
    try {
        const response = await fetch('/api/municipalities');
        if (!response.ok) {
            throw new Error('Failed to fetch municipalities');
        }
        municipalities.value = await response.json();
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
const toggleSort = (field: 'id' | 'name' | 'code' | 'city_class' | 'municipal_budget_officer') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

/**
 * handleEditMunicipality: Opens the Edit Municipality modal with the selected municipality data
 * @param {Municipality} municipality - The municipality object to edit
 * Populates formData with the current municipality values
 * Sets editingMunicipality to the municipality being edited
 * Opens the edit modal
 */
const handleEditMunicipality = (municipality: Municipality) => {
    editingMunicipality.value = municipality;
    formData.value = {
        name: municipality.name,
        code: municipality.code,
        city_class: municipality.city_class || '',
        municipal_budget_officer: municipality.municipal_budget_officer || '',
        representative: municipality.representative || ''
    };
    formErrors.value = {};
    showEditModal.value = true;
};

/**
 * closeEditModal: Closes the Edit Municipality modal
 * Clears the editingMunicipality reference
 */
const closeEditModal = () => {
    showEditModal.value = false;
    editingMunicipality.value = null;
};

/**
 * handleUpdateMunicipality: Submits the form to update an existing municipality
 * - Validates form data first
 * - Makes PUT request to /api/municipalities/{id} with updated data and Bearer token
 * - On success: updates the municipality in the municipalities array, closes the modal, and shows success toast with municipality details
 * - On error: sets formErrors['submit'] with the error message and shows error toast
 */
const handleUpdateMunicipality = async () => {
    if (!validateForm() || !editingMunicipality.value) return;
    
    try {
        const response = await fetch(`/api/municipalities/${editingMunicipality.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to update municipality');
        }
        
        const updatedMunicipality = await response.json();
        const index = municipalities.value.findIndex(m => m.id === editingMunicipality.value!.id);
        if (index !== -1) {
            municipalities.value[index] = updatedMunicipality;
        }
        closeEditModal();
        
        toastRef.value?.add(
            'info',
            'Success',
            `Municipality: <strong>${updatedMunicipality.name}</strong> has been updated successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleDeleteMunicipality: Opens the Delete Municipality confirmation modal
 * @param {Municipality} municipality - The municipality object to delete
 * Sets municipalityToDelete to the municipality being confirmed for deletion
 * Opens the delete confirmation modal
 */
const handleDeleteMunicipality = (municipality: Municipality) => {
    municipalityToDelete.value = municipality;
    showDeleteModal.value = true;
};

/**
 * closeDeleteModal: Closes the Delete Municipality modal
 * Clears the municipalityToDelete reference
 */
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    municipalityToDelete.value = null;
};

/**
 * confirmDeleteMunicipality: Confirms and executes the deletion of a municipality
 * - Makes DELETE request to /api/municipalities/{id} with Bearer token
 * - On success: removes the municipality from the municipalities array, closes the modal, and shows success toast with municipality details
 * - On error: shows an error toast with the error message
 */
const confirmDeleteMunicipality = async () => {
    if (!municipalityToDelete.value) return;
    
    const deletingMunicipality = municipalityToDelete.value;
    
    try {
        const response = await fetch(`/api/municipalities/${deletingMunicipality.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to delete municipality');
        }
        
        municipalities.value = municipalities.value.filter(m => m.id !== deletingMunicipality.id);
        closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `Municipality: <strong>${deletingMunicipality.name}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * openCreateModal: Opens the Create Municipality modal
 * - Resets formData to empty values
 * - Clears any existing form errors
 * - Sets showCreateModal to true
 */
const openCreateModal = () => {
    formData.value = {
        name: '',
        code: '',
        city_class: '',
        municipal_budget_officer: '',
        representative: ''
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

/**
 * closeCreateModal: Closes the Create Municipality modal
 * Sets showCreateModal to false
 */
const closeCreateModal = () => {
    showCreateModal.value = false;
};

/**
 * validateForm: Validates the form data for creating a municipality
 * Required fields: name, code
 * Returns true if all validations pass, false otherwise
 * Sets formErrors with specific error messages for invalid fields
 */
const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.name.trim()) {
        formErrors.value['name'] = 'Municipality Name is required';
    }
    
    if (!formData.value.code.trim()) {
        formErrors.value['code'] = 'Code is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

/**
 * handleCreateMunicipality: Submits the form to create a new municipality
 * - Validates form data first
 * - Makes POST request to /api/municipalities with form data and Bearer token
 * - On success: adds the new municipality to the municipalities array, closes the modal, and shows success toast with municipality details
 * - On error: sets formErrors['submit'] with the error message and shows error toast
 */
const handleCreateMunicipality = async () => {
    if (!validateForm()) return;
    
    try {
        const response = await fetch('/api/municipalities', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to create municipality');
        }
        
        const newMunicipality = await response.json();
        municipalities.value.push(newMunicipality);
        closeCreateModal();
        
        toastRef.value?.add(
            'success',
            'Success',
            `Municipality: <strong>${newMunicipality.name}</strong> has been created successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
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
