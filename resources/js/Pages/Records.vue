<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Records
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Record Button -->
                        <button v-if="hasPermission('records.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Record
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('records.create') && 'sm:ml-auto']">
                            <i class="fas fa-search text-gray-400"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search records..."
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

                <!-- Tab Navigation -->
                <div class="px-6 py-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <!-- Main Record Types -->
                    <div class="flex gap-2 overflow-x-auto border-b border-gray-100 dark:border-gray-700">
                        <button 
                            v-for="type in recordTypes" 
                            :key="type"
                            @click="activeTab = type; activeSubtype = null; currentPage = 1; searchQuery = ''"
                            :class="[
                                'px-4 py-3 text-xs font-medium whitespace-nowrap border-b-2 transition-colors',
                                activeTab === type
                                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                    : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'
                            ]"
                        >
                            {{ type }}
                        </button>
                    </div>

                    <!-- Sub-types for selected main type (if available) -->
                    <div v-if="recordTypesHierarchy[activeTab]?.length > 0" class="bg-gray-50 dark:bg-gray-900/30 px-4 py-3">
                        <!-- Button style for types with few subtypes (≤ 8) -->
                        <div v-if="recordTypesHierarchy[activeTab].length <= 8" class="flex gap-1 overflow-x-auto">
                            <button
                                @click="activeSubtype = null; currentPage = 1; searchQuery = ''"
                                :class="[
                                    'px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full transition-colors',
                                    activeSubtype === null
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                All
                            </button>
                            <button
                                v-for="subtype in recordTypesHierarchy[activeTab]"
                                :key="subtype"
                                @click="activeSubtype = subtype; currentPage = 1; searchQuery = ''"
                                :class="[
                                    'px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full transition-colors',
                                    activeSubtype === subtype
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                {{ subtype }}
                            </button>
                        </div>

                        <!-- Dropdown style for types with many subtypes (> 8) -->
                        <div v-else class="flex gap-2 items-center flex-wrap">
                            <button
                                @click="activeSubtype = null; currentPage = 1; searchQuery = ''"
                                :class="[
                                    'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                    activeSubtype === null
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                All
                            </button>

                            <div class="relative">
                                <select 
                                    :value="activeSubtype || ''"
                                    @change="(e) => {
                                        activeSubtype = e.target.value || null;
                                        currentPage = 1;
                                        searchQuery = '';
                                    }"
                                    class="appearance-none px-3 py-1 text-xs font-medium rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all width-auto pr-6"
                                >
                                    <option value="">Select a subtype...</option>
                                    <option v-for="subtype in recordTypesHierarchy[activeTab]" :key="subtype" :value="subtype">
                                        {{ subtype }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 pointer-events-none text-xs"></i>
                            </div>

                            <div v-if="activeSubtype" class="flex items-center gap-2 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ activeSubtype }}</span>
                                <button @click="activeSubtype = null; currentPage = 1; searchQuery = ''" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading records...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Records</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="records.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No {{ activeTab }} found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new record in this category</p>
                </div>

                <!-- No Records for Active Tab -->
                <div v-else-if="filteredRecords.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No {{ activeTab }} found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Try adjusting your search or create a new record</p>
                </div>

                <!-- Data Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-32">
                            <col class="w-32">
                            <col class="w-32">
                            <col class="w-20">
                            <col class="w-20">
                        </colgroup>
                        <!-- Table Header -->
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('record_no')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Record No
                                        <span v-if="sortBy === 'record_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('created_at')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Date
                                        <span v-if="sortBy === 'created_at'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('record_subtype')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Subtype
                                        <span v-if="sortBy === 'record_subtype'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('title')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Title
                                        <span v-if="sortBy === 'title'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('remarks')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Remarks
                                        <span v-if="sortBy === 'remarks'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('file_extension')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        File Details
                                        <span v-if="sortBy === 'file_extension'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="record in paginatedRecords" :key="record.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-3 text-xs font-medium text-gray-900 dark:text-gray-100">{{ record.record_no }}</td>
                                <td class="px-6 py-3 text-xs text-gray-600 dark:text-gray-400">{{ new Date(record.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-3 text-xs text-gray-600 dark:text-gray-400">{{ record.record_subtype || '-' }}</td>
                                <td class="px-6 py-3 text-xs text-gray-700 dark:text-gray-300">{{ record.title }}</td>
                                <td class="px-6 py-3 text-xs text-gray-700 dark:text-gray-300">{{ record.remarks || '-' }}</td>
                                <td class="px-6 py-3 text-xs">
                                    <div v-if="record.file_size" class="flex flex-col gap-1">
                                        <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded font-medium">
                                            {{ record.file_extension || 'FILE' }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ formatFileSize(record.file_size) }}</span>
                                    </div>
                                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                </td>
                                <td class="px-6 py-3 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- View Button: Visible if file exists -->
                                        <button 
                                            v-if="record.image_path"
                                            @click.stop="viewFile(record)" 
                                            class="relative p-2 text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-eye"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">View</span>
                                        </button>
                                        <!-- Download Button: Visible if file exists -->
                                        <button 
                                            v-if="record.image_path"
                                            @click.stop="downloadFile(record)" 
                                            class="relative p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-download"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Download</span>
                                        </button>
                                        <!-- Edit Button: Visible if user has permission -->
                                        <button 
                                            v-if="hasPermission('records.edit')"
                                            @click.stop="handleEditRecord(record)" 
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button: Visible if user has permission -->
                                        <button 
                                            v-if="hasPermission('records.delete')"
                                            @click.stop="openDeleteModal(record)" 
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

                <!-- Pagination Controls -->
                <div v-if="!loading && records.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredRecords.length) }}</span> of <span class="font-semibold">{{ filteredRecords.length }}</span> records
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

        <!-- Create Record Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-archive text-emerald-600 dark:text-emerald-400"></i>
                            Create Record
                        </h3>
                        <!-- Close Button: Calls closeCreateModal() when clicked -->
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for creating a new record -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Record No Field -->
                            <div class="space-y-2">
                                <label for="create_record_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record No. <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.record_no" id="create_record_no" type="text" placeholder="Auto-generated" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300 focus:border-gray-300" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generated based on Record Type</p>
                                <span v-if="formErrors.record_no" class="text-red-500 text-xs">{{ formErrors.record_no }}</span>
                            </div>

                            <!-- Record Type Field -->
                            <div class="space-y-2">
                                <label for="create_record_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Type <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-tags absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.record_type" @change="updateRecordNo" id="create_record_type" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.record_type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option value="" disabled>Select a record type</option>
                                        <option v-for="type in recordTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.record_type" class="text-red-500 text-xs">{{ formErrors.record_type }}</span>
                            </div>

                            <!-- Record Subtype Field -->
                            <div v-if="recordTypesHierarchy[formData.record_type]?.length > 0" class="space-y-2">
                                <label for="create_record_subtype" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Subtype</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-layer-group absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.record_subtype" id="create_record_subtype" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors appearance-none">
                                        <option value="" disabled>Select a subtype</option>
                                        <option v-for="subtype in recordTypesHierarchy[formData.record_type]" :key="subtype" :value="subtype">{{ subtype }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Title Field -->
                            <div class="space-y-2">
                                <label for="create_title" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-heading absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.title" id="create_title" type="text" placeholder="Enter record title" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.title ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.title" class="text-red-500 text-xs">{{ formErrors.title }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label for="create_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.remarks" id="create_remarks" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>

                            <!-- File Upload Field -->
                            <div class="space-y-2">
                                <label for="create_file" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Scanned Image / File <span class="text-red-600">*</span></label>
                                <div :class="['border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors', formErrors.file ? 'border-red-500 dark:border-red-600 hover:border-red-600 dark:hover:border-red-500' : 'border-gray-300 dark:border-gray-600 hover:border-emerald-500 dark:hover:border-emerald-400']" @click="fileInputCreate?.click()">
                                    <input 
                                        ref="fileInputCreate"
                                        id="create_file"
                                        type="file" 
                                        @change="handleFileUpload($event, 'create')" 
                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"
                                        class="hidden"
                                    />
                                    <div v-if="!formData.selectedFileName">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 dark:text-gray-600 mb-2"></i>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Click to upload or drag and drop</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, Images, or Documents (max 200MB)</p>
                                    </div>
                                    <div v-else class="text-sm">
                                        <i class="fas fa-file text-emerald-600 dark:text-emerald-400 text-2xl mb-2"></i>
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ formData.selectedFileName }}</p>
                                    </div>
                                </div>
                                <span v-if="formErrors.file" class="text-red-500 text-xs">{{ formErrors.file }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button type="submit" @click="submitCreateForm" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ creating ? 'Saving...' : 'Create' }}
                        </button>
                        <!-- Cancel Button: Calls closeCreateModal() to close the form -->
                        <button @click="closeCreateModal" type="button" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Record Modal -->
        <Teleport to="body" v-if="showEditModal && recordToEdit">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Record
                        </h3>
                        <!-- Close Button: Calls closeEditModal() when clicked -->
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for editing a record -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Record No Field -->
                            <div class="space-y-2">
                                <label for="edit_record_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record No. <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.record_no" id="edit_record_no" type="text" placeholder="Enter record number" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.record_no ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.record_no" class="text-red-500 text-xs">{{ formErrors.record_no }}</span>
                            </div>

                            <!-- Record Type Field -->
                            <div class="space-y-2">
                                <label for="edit_record_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Type <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-tags absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.record_type" id="edit_record_type" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.record_type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']">
                                        <option value="" disabled>Select a record type</option>
                                        <option v-for="type in recordTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.record_type" class="text-red-500 text-xs">{{ formErrors.record_type }}</span>
                            </div>

                            <!-- Record Subtype Field -->
                            <div v-if="recordTypesHierarchy[formData.record_type]?.length > 0" class="space-y-2">
                                <label for="edit_record_subtype" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Subtype</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-layer-group absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.record_subtype" id="edit_record_subtype" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 transition-colors appearance-none">
                                        <option value="" disabled>Select a subtype</option>
                                        <option v-for="subtype in recordTypesHierarchy[formData.record_type]" :key="subtype" :value="subtype">{{ subtype }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Title Field -->
                            <div class="space-y-2">
                                <label for="edit_title" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-heading absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.title" id="edit_title" type="text" placeholder="Enter record title" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.title ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.title" class="text-red-500 text-xs">{{ formErrors.title }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label for="edit_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.remarks" id="edit_remarks" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>

                            <!-- File Upload Field -->
                            <div class="space-y-2">
                                <label for="edit_file" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Scanned Image / File</label>
                                <div v-if="recordToEdit?.image_path" class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-900/40 rounded-lg">
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-2">Current file:</p>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-file text-blue-600 dark:text-blue-400"></i>
                                        <a :href="`/storage/${recordToEdit.image_path}`" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">View File</a>
                                    </div>
                                </div>
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-colors" @click="fileInputEdit?.click()">
                                    <input 
                                        ref="fileInputEdit"
                                        id="edit_file"
                                        type="file" 
                                        @change="handleFileUpload($event, 'edit')" 
                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"
                                        class="hidden"
                                    />
                                    <div v-if="!formData.selectedFileName">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 dark:text-gray-600 mb-2"></i>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">Click to upload or drag and drop</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, Images, or Documents (max 200MB)</p>
                                    </div>
                                    <div v-else class="text-sm">
                                        <i class="fas fa-file text-blue-600 dark:text-blue-400 text-2xl mb-2"></i>
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ formData.selectedFileName }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="submitEditForm" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ updating ? 'Updating...' : 'Update' }}
                        </button>
                        <!-- Cancel Button: Calls closeEditModal() to close the form -->
                        <button @click="closeEditModal" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Record Modal: Confirmation dialog for deleting a record -->
        <Teleport to="body" v-if="showDeleteModal && recordToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows warning icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Record
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
                                    Are you sure you want to delete record <span class="font-semibold">{{ recordToDelete.record_no }}</span>?
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
                            @click="confirmDelete"
                            :disabled="deleting"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-trash-alt"></i>
                            {{ deleting ? 'Deleting...' : 'Delete' }}
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

interface Record {
    id: number;
    record_no: string;
    record_type: string;
    record_subtype: string | null;
    title: string;
    remarks: string | null;
    image_path: string | null;
    file_extension?: string;
    file_size?: number;
    user_id: number;
    created_at: string;
    updated_at: string;
}

const records = ref<Record[]>([]);

// Record Types with Hierarchical Structure (Types and Sub-types)
const recordTypesHierarchy = ref({
    'Provincial Budget': [],
    'Municipal Budget': ['Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
    'Issuances / Circulars / Other References and Documents': [
        'DILG Memorandum Circulars',
        'DOF Department Orders',
        'Memorandum Circulars (Office of the President)',
        'PAG-IBIG',
        'COMELEC Resolutions',
        'DBM Budget Circulars',
        'Local Budget Circulars',
        'Local Budget Memorandums',
        'DBM Orders / Circular Letters',
        'Joint Circulars',
        'Executive Orders (Office of the President)',
        'Presidential Decrees',
        'Republic Acts',
        'GPPB Circulars',
        'GSIS Memorandum Circulars',
        'DOH Circulars / Administrative Orders',
        'PHIC Circulars',
        'National Budget Circulars / Memorandums',
        'CHED Memorandum Circulars',
        'Budget Call',
        'Queries',
        'Annual Budget Transmittal / Indorsement to SPO',
        'Transmitted PPMPs to BAC',
        'SP Indorsement to DBM (APB)',
        'PBO Certifications to Plans / Other Reports',
        'DBM Letters / Reports / Matters',
        'List of Documentary Requirements',
        'Provincial NTAs',
        'Municipal NTAs',
        'PLGU Annual Budget Review'
    ]
});

const recordTypes = computed(() => Object.keys(recordTypesHierarchy.value));

const recordTypeAbbreviations = {
    'Provincial Budget': 'PB',
    'Municipal Budget': 'MB',
    'Issuances / Circulars / Other References and Documents': 'ISO',
};
const activeTab = ref('Provincial Budget');
const activeSubtype = ref<string | null>(null);
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref<'id' | 'record_no' | 'title' | 'created_at' | 'record_subtype' | 'remarks' | 'file_extension'>('id');
const sortOrder = ref<'asc' | 'desc'>('desc');
const loading = ref(true);
const error = ref<string | null>(null);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);

const recordToEdit = ref<Record | null>(null);
const recordToDelete = ref<Record | null>(null);

const formData = ref({
    record_no: '',
    record_type: '',
    record_subtype: '',
    title: '',
    remarks: '',
    selectedFile: null as File | null,
    selectedFileName: '',
});

const formErrors = ref<Record<string, string>>({});

const toastRef = ref();
const fileInputCreate = ref();
const fileInputEdit = ref();

const page = usePage();

const hasPermission = (permission: string): boolean => {
    const permissions = (page.props.auth as any)?.permissions || [];
    return permissions.includes(permission);
};

/**
 * Format file size in human-readable format
 */
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const filteredRecords = computed(() => {
    let filtered = records.value.filter(record => {
        const typeMatches = record.record_type === activeTab.value;
        const subtypeMatches = activeSubtype.value === null || record.record_subtype === activeSubtype.value;
        return typeMatches && subtypeMatches;
    });

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(record =>
            record.record_no.toLowerCase().includes(query) ||
            record.record_type.toLowerCase().includes(query) ||
            (record.record_subtype?.toLowerCase() || '').includes(query) ||
            record.title.toLowerCase().includes(query) ||
            (record.file_extension?.toLowerCase() || '').includes(query) ||
            (record.file_size?.toString() || '').includes(query) ||
            (record.remarks?.toLowerCase() || '').includes(query)
        );
    }

    filtered.sort((a, b) => {
        let aVal: any = a[sortBy.value as keyof Record];
        let bVal: any = b[sortBy.value as keyof Record];

        // Handle numeric fields
        if (sortBy.value === 'id') {
            const aNum = Number(aVal) || 0;
            const bNum = Number(bVal) || 0;
            return sortOrder.value === 'asc' ? aNum - bNum : bNum - aNum;
        }

        aVal = aVal?.toString().toLowerCase() || '';
        bVal = bVal?.toString().toLowerCase() || '';

        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });

    return filtered;
});

const totalPages = computed(() => {
    return Math.ceil(filteredRecords.value.length / itemsPerPage.value);
});

const paginatedRecords = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredRecords.value.slice(start, end);
});

const fetchRecords = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch('/api/records');
        if (!response.ok) throw new Error('Failed to fetch records');
        records.value = await response.json();
    } catch (err: any) {
        error.value = err.message;
        showToast('Failed to load records', 'error');
    } finally {
        loading.value = false;
    }
};

/**
 * generateRecordNo: Auto-generates Record No based on Record Type
 * Format: ABBR-YYYY-MM-NNN (e.g., DBM-2026-02-001)
 * Counts existing records of same type in same month and increments
 */
const generateRecordNo = async (recordType: string): Promise<string> => {
    const abbr = recordTypeAbbreviations[recordType as keyof typeof recordTypeAbbreviations];
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const yearMonth = `${year}-${month}`;
    
    // Count records of same type with same year-month
    const sameTypeCount = records.value.filter(r => 
        r.record_type === recordType && 
        r.record_no.includes(yearMonth)
    ).length;
    
    const series = String(sameTypeCount + 1).padStart(3, '0');
    return `${abbr}-${yearMonth}-${series}`;
};

const updateRecordNo = async () => {
    if (formData.value.record_type) {
        formData.value.record_no = await generateRecordNo(formData.value.record_type);
    }
};

/**
 * validateForm: Validates the form data
 * Required fields: record_type, title, file (for create modal)
 * Returns true if all validations pass, false otherwise
 * Sets formErrors with specific error messages for invalid fields
 */
const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.record_type.trim()) {
        formErrors.value['record_type'] = 'Record Type is required';
    }
    
    if (!formData.value.title.trim()) {
        formErrors.value['title'] = 'Title is required';
    }

    if (showCreateModal.value && !formData.value.selectedFile) {
        formErrors.value['file'] = 'File upload is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const openCreateModal = async () => {
    const subtypes = recordTypesHierarchy.value[activeTab.value as keyof typeof recordTypesHierarchy.value] || [];
    const initialSubtype = subtypes.length > 0 ? subtypes[0] : null;
    
    formData.value = { 
        record_no: '', 
        record_type: activeTab.value, 
        record_subtype: initialSubtype || '',
        title: '', 
        remarks: '', 
        selectedFile: null, 
        selectedFileName: '' 
    };
    formErrors.value = {};
    await updateRecordNo();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitCreateForm = async () => {
    if (!validateForm()) return;
    
    try {
        creating.value = true;
        
        const formDataObj = new FormData();
        formDataObj.append('record_no', formData.value.record_no);
        formDataObj.append('record_type', formData.value.record_type);
        formDataObj.append('record_subtype', formData.value.record_subtype);
        formDataObj.append('title', formData.value.title);
        formDataObj.append('remarks', formData.value.remarks);
        if (formData.value.selectedFile) {
            formDataObj.append('file', formData.value.selectedFile);
        }

        const response = await fetch('/api/records', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formDataObj,
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to create record');
        }

        const newRecord = await response.json();
        await fetchRecords();
        closeCreateModal();
        
        toastRef.value?.add(
            'success',
            'Success',
            `<strong>${newRecord.record_type}</strong> ${newRecord.record_subtype ? `(${newRecord.record_subtype}): ` : ': '}<strong>${newRecord.record_no}</strong> has been saved successfully!`,
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

const handleEditRecord = (record: Record) => {
    recordToEdit.value = record;
    formData.value = { 
        record_no: record.record_no,
        record_type: record.record_type,
        record_subtype: record.record_subtype || '',
        title: record.title,
        remarks: record.remarks || '',
        selectedFile: null,
        selectedFileName: ''
    };
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    recordToEdit.value = null;
};

const submitEditForm = async () => {
    if (!recordToEdit.value) return;
    
    if (!validateForm()) return;

    try {
        updating.value = true;
        
        const formDataObj = new FormData();
        formDataObj.append('record_no', formData.value.record_no);
        formDataObj.append('record_type', formData.value.record_type);
        formDataObj.append('record_subtype', formData.value.record_subtype);
        formDataObj.append('title', formData.value.title);
        formDataObj.append('remarks', formData.value.remarks);
        if (formData.value.selectedFile) {
            formDataObj.append('file', formData.value.selectedFile);
        }

        const response = await fetch(`/api/records/${recordToEdit.value.id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-HTTP-Method-Override': 'PUT',
            },
            body: formDataObj,
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to update record');
        }

        const updatedRecord = await response.json();
        await fetchRecords();
        closeEditModal();
        
        toastRef.value?.add(
            'info',
            'Updated',
            `<strong>${updatedRecord.record_type}</strong> ${updatedRecord.record_subtype ? `(${updatedRecord.record_subtype}): ` : ': '}<strong>${updatedRecord.record_no}</strong> has been updated successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        updating.value = false;
    }
}

const openDeleteModal = (record: Record) => {
    recordToDelete.value = record;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    recordToDelete.value = null;
};

const confirmDelete = async () => {
    if (!recordToDelete.value) return;

    const deletingRecord = recordToDelete.value;

    try {
        deleting.value = true;
        const response = await fetch(`/api/records/${deletingRecord.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to delete record');
        }

        await fetchRecords();
        closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Deleted',
            `<strong>${deletingRecord.record_type}:</strong> ${deletingRecord.record_no} (<strong>${deletingRecord.title}</strong>) has been deleted successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};

const viewFile = (record: Record) => {
    if (record.image_path) {
        window.open(`/api/records/${record.id}/view`, '_blank');
    }
};

const downloadFile = (record: Record) => {
    if (record.image_path) {
        const link = document.createElement('a');
        link.href = `/api/records/${record.id}/download`;
        link.download = record.image_path.split('/').pop() || 'document';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const toggleSort = (field: 'record_no' | 'record_type' | 'title' | 'created_at' | 'record_subtype' | 'remarks' | 'file_extension') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const handleFileUpload = (event: Event, modalType: 'create' | 'edit') => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        formData.value.selectedFile = file;
        formData.value.selectedFileName = file.name;
    }
};

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    if (toastRef.value) {
        toastRef.value.show(message, type);
    }
};

onMounted(() => {
    fetchRecords();
});
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
