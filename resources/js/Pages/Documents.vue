<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Documents
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Document Button: Calls openCreateModal() to show the create form modal -->
                        <button v-if="hasPermission('documents.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Document
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('documents.create') && 'sm:ml-auto']">
                                <i class="fas fa-search text-gray-400"></i>
                                <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search documents..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading documents...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State: v-else-if displays error message if fetch fails -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Documents</h3>
                            <!-- Displays the error message from error ref -->
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State: v-else-if shows when no documents exist -->
                <div v-else-if="documents.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No documents found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new document</p>
                </div>

                <!-- Data Table: v-else shows when documents data is loaded -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-32">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                        </colgroup>
                        <!-- Table Header: Contains sortable column headers -->
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <!-- Tracking No Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('tracking_no')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Tracking No
                                        <span v-if="sortBy === 'tracking_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Date Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('date')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Date
                                        <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Document Type Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('document_type')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Type
                                        <span v-if="sortBy === 'document_type'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Source Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('source')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Source
                                        <span v-if="sortBy === 'source'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Particulars Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('particulars')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Particulars
                                        <span v-if="sortBy === 'particulars'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Remarks Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('remarks')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Remarks
                                        <span v-if="sortBy === 'remarks'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Custodian Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('user_id')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Custodian
                                        <span v-if="sortBy === 'user_id'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <!-- Status Header: Sortable -->
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('status')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Status
                                        <span v-if="sortBy === 'status'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body: Renders rows for each document in paginatedDocuments -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- v-for loops through paginatedDocuments (filtered, sorted, and paginated data) -->
                            <!-- :key uses document.id for efficient Vue rendering -->
                            <tr
                                v-for="document in paginatedDocuments"
                                :key="document.id"
                                @click="viewDocumentTransactions(document)"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150 cursor-pointer"
                            >
                                <!-- Tracking No Column -->
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ document.tracking_no }}
                                </td>
                                <!-- Date Column -->
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ new Date(document.date).toLocaleDateString() }}
                                </td>
                                <!-- Document Type Column -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="document.document_type">{{ document.document_type }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Source Column -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="document.source">{{ document.source }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Particulars Column: Truncate if too long -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="document.particulars" :title="document.particulars">{{ document.particulars }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Remarks Column: Truncate if too long -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="document.remarks" :title="document.remarks">{{ document.remarks }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                </td>
                                <!-- Custodian Column: Shows office/municipality if forwarded, otherwise current user -->
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ getCustodianDisplay(document) }}
                                </td>
                                <!-- Status Column -->
                                <td class="px-4 py-2 text-xs text-center">
                                    <span v-if="getDisplayStatus(document) === 'created'" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">Created</span>
                                    <span v-else-if="getDisplayStatus(document) === 'forwarded'" class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded-full text-xs font-medium">Forwarded</span>
                                    <span v-else-if="getDisplayStatus(document) === 'to be received'" class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded-full text-xs font-medium">To Be Received</span>
                                    <span v-else-if="getDisplayStatus(document) === 'pending'" class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-medium">Pending</span>
                                    <span v-else-if="getDisplayStatus(document) === 'finalized'" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium">Finalized</span>
                                    <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">{{ getDisplayStatus(document) }}</span>
                                </td>
                                <!-- Actions Column: Contains edit/delete dropdown menu -->
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Forward Button: Visible if created or pending and user has permission -->
                                        <button 
                                            v-if="(document.status === 'created' || document.status === 'pending') && hasPermission('documents.forward')"
                                            @click.stop="handleForwardDocument(document)" 
                                            class="relative p-2 text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-900/30 hover:text-cyan-700 dark:hover:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-share"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Forward</span>
                                        </button>
                                        <!-- Receive Button: Visible if forwarded and current user didn't forward it -->
                                        <button 
                                            v-if="document.status === 'forwarded' && hasPermission('documents.receive') && document.user_id !== currentUser.id"w
                                            @click.stop="handleReceiveDocument(document)" 
                                            class="relative p-2 text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-folder-open"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Receive</span>
                                        </button>
                                        <!-- Finalize Button: Visible if pending and user has permission -->
                                        <button 
                                            v-if="document.status === 'pending' && hasPermission('documents.finalize')"
                                            @click.stop="handleFinalizeDocument(document)" 
                                            class="relative p-2 text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-check-double"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Finalize</span>
                                        </button>
                                        <!-- Edit Button: Visible if user has permission -->
                                        <button 
                                            v-if="hasPermission('documents.edit')"
                                            @click.stop="handleEditDocument(document)" 
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button: Visible only for Developer and Administrator roles -->
                                        <button 
                                            v-if="hasPermission('documents.delete')"
                                            @click.stop="handleDeleteDocument(document)" 
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
                <div v-if="!loading && documents.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Pagination Info: Displays current range and total count -->
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredDocuments.length) }}</span> of <span class="font-semibold">{{ filteredDocuments.length }}</span> documents
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

        <!-- Create Document Modal: Teleports to body when showCreateModal is true -->
        <!-- @click.self="closeCreateModal" closes modal when clicking outside the dialog -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file text-emerald-600 dark:text-emerald-400"></i>
                            Create Document
                        </h3>
                        <!-- Close Button: Calls closeCreateModal() when clicked -->
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for creating a new document -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Tracking No Field: Auto-generated with YYYY-MM- prefix -->
                            <div class="space-y-2">
                                <label for="tracking_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tracking No</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        :value="trackingNoPrefix + (formData.tracking_no.replace(trackingNoPrefix, '') || '')"
                                        @input="formData.tracking_no = trackingNoPrefix + ($event.target as HTMLInputElement).value.replace(trackingNoPrefix, '')"
                                        id="tracking_no"
                                        type="text"
                                        :placeholder="trackingNoPrefix + 'XXX'"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.tracking_no" class="text-red-500 text-xs">{{ formErrors.tracking_no }}</span>
                            </div>

                            <!-- Date Field: Set to current date, max is current date -->
                            <div class="space-y-2">
                                <label for="date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.date"
                                        id="date"
                                        type="date"
                                        :max="todayDate"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Document Type Field: Select from predefined list -->
                            <div class="space-y-2">
                                <label for="document_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.document_type"
                                        id="document_type"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Document Type</option>
                                        <option value="Annual Budget">Annual Budget</option>
                                        <option value="Supplemental Budget">Supplemental Budget</option>
                                        <option value="Budget Proposals">Budget Proposals</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.document_type" class="text-red-500 text-xs">{{ formErrors.document_type }}</span>
                            </div>

                            <!-- Source Type Field: Choose between Internal and External -->
                            <div class="space-y-2">
                                <label for="source_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source Type</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-network-wired absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.sourceType"
                                        id="source_type"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="internal">Internal (Offices)</option>
                                        <option value="external">External (Municipalities)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Source Field: Select from Offices or Municipalities based on sourceType -->
                            <div class="space-y-2">
                                <label for="source" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-link absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.source"
                                        id="source"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Source</option>
                                        <option v-if="formData.sourceType === 'internal'" v-for="office in offices" :key="office.id" :value="office.office_name">
                                            {{ office.office_name }}
                                        </option>
                                        <option v-if="formData.sourceType === 'external'" v-for="municipality in municipalities" :key="municipality.id" :value="municipality.name">
                                            {{ municipality.name }}
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.source" class="text-red-500 text-xs">{{ formErrors.source }}</span>
                            </div>

                            <!-- Particulars Field: Required -->
                            <div class="space-y-2">
                                <label for="particulars" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Particulars</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-pen-fancy absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea
                                        v-model="formData.particulars"
                                        id="particulars"
                                        placeholder="Document Particulars"
                                        rows="4"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                                    ></textarea>
                                </div>
                                <span v-if="formErrors.particulars" class="text-red-500 text-xs">{{ formErrors.particulars }}</span>
                            </div>

                            <!-- Remarks Field: Optional -->
                            <div class="space-y-2">
                                <label for="remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea
                                        v-model="formData.remarks"
                                        id="remarks"
                                        placeholder="Additional remarks..."
                                        rows="3"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                                    ></textarea>
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
                            @click="handleCreateDocument"
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

        <!-- Edit Document Modal: Teleports to body when showEditModal is true -->
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows title and close button -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Document
                        </h3>
                        <!-- Close Button: Calls closeEditModal() when clicked -->
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Contains form inputs for editing a document -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Tracking No Field: Auto-generated with YYYY-MM- prefix -->
                            <div class="space-y-2">
                                <label for="edit_tracking_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tracking No</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        :value="trackingNoPrefix + (formData.tracking_no.replace(trackingNoPrefix, '') || '')"
                                        @input="formData.tracking_no = trackingNoPrefix + ($event.target as HTMLInputElement).value.replace(trackingNoPrefix, '')"
                                        id="edit_tracking_no"
                                        type="text"
                                        :placeholder="trackingNoPrefix + 'XXX'"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <span v-if="formErrors.tracking_no" class="text-red-500 text-xs">{{ formErrors.tracking_no }}</span>
                            </div>

                            <!-- Date Field: Set to current date, max is current date -->
                            <div class="space-y-2">
                                <label for="edit_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.date"
                                        id="edit_date"
                                        type="date"
                                        :max="todayDate"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Document Type Field: Select from predefined list -->
                            <div class="space-y-2">
                                <label for="edit_document_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.document_type"
                                        id="edit_document_type"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                    >
                                        <option value="">Select Document Type</option>
                                        <option value="Annual Budget">Annual Budget</option>
                                        <option value="Supplemental Budget">Supplemental Budget</option>
                                        <option value="Budget Proposals">Budget Proposals</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.document_type" class="text-red-500 text-xs">{{ formErrors.document_type }}</span>
                            </div>

                            <!-- Source Type Field: Choose between Internal and External -->
                            <div class="space-y-2">
                                <label for="edit_source_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source Type</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-network-wired absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.sourceType"
                                        id="edit_source_type"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                    >
                                        <option value="internal">Internal (Offices)</option>
                                        <option value="external">External (Municipalities)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Source Field: Select from Offices or Municipalities based on sourceType -->
                            <div class="space-y-2">
                                <label for="edit_source" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Source</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-link absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model="formData.source"
                                        id="edit_source"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                    >
                                        <option value="">Select Source</option>
                                        <option v-if="formData.sourceType === 'internal'" v-for="office in offices" :key="office.id" :value="office.office_name">
                                            {{ office.office_name }}
                                        </option>
                                        <option v-if="formData.sourceType === 'external'" v-for="municipality in municipalities" :key="municipality.id" :value="municipality.name">
                                            {{ municipality.name }}
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.source" class="text-red-500 text-xs">{{ formErrors.source }}</span>
                            </div>

                            <!-- Particulars Field: Required -->
                            <div class="space-y-2">
                                <label for="edit_particulars" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Particulars</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-pen-fancy absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea
                                        v-model="formData.particulars"
                                        id="edit_particulars"
                                        placeholder="Document Particulars"
                                        rows="4"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"
                                    ></textarea>
                                </div>
                                <span v-if="formErrors.particulars" class="text-red-500 text-xs">{{ formErrors.particulars }}</span>
                            </div>

                            <!-- Remarks Field: Optional -->
                            <div class="space-y-2">
                                <label for="edit_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea
                                        v-model="formData.remarks"
                                        id="edit_remarks"
                                        placeholder="Additional remarks..."
                                        rows="3"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"
                                    ></textarea>
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
                            @click="handleUpdateDocument"
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

        <!-- Delete Document Modal: Confirmation dialog for deleting a document -->
        <Teleport to="body" v-if="showDeleteModal && documentToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows warning icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Document
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
                                    Are you sure you want to delete document <span class="font-semibold">{{ documentToDelete.tracking_no }}</span>?
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
                            @click="confirmDeleteDocument"
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

        <!-- Forward Document Modal: Modal for forwarding document to User, Office, or Municipality -->
        <Teleport to="body" v-if="showForwardModal && documentToForward">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeForwardModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-share-alt text-blue-600 dark:text-blue-400"></i>
                            Forward Document
                        </h3>
                        <button @click="closeForwardModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-4">
                        <p class="text-sm text-gray-900 dark:text-gray-100">
                            Forwarding document: <span class="font-semibold">{{ documentToForward.tracking_no }}</span>
                        </p>

                        <!-- Forward To Type Selection -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Forward To Type</label>
                            <div class="flex gap-2">
                                <button
                                    @click="forwardData.forward_to_type = 'user'; forwardData.forward_to_id = null;"
                                    :class="forwardData.forward_to_type === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                    :disabled="false"
                                    class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="fas fa-user mr-1"></i>User
                                </button>
                                <button
                                    @click="selectForwardOffice"
                                    :class="forwardData.forward_to_type === 'office' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                    :disabled="forwardDocumentSourceType === 'municipality'"
                                    class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="fas fa-building mr-1"></i>Office
                                </button>
                                <button
                                    @click="selectForwardMunicipality"
                                    :class="forwardData.forward_to_type === 'municipality' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                                    :disabled="forwardDocumentSourceType === 'office'"
                                    class="flex-1 px-3 py-2 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="fas fa-map mr-1"></i>Municipality
                                </button>
                            </div>
                        </div>

                        <!-- User Selection -->
                        <div v-if="forwardData.forward_to_type === 'user'" class="space-y-2">
                            <label for="forward_user_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select User</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="forwardData.forward_to_id"
                                    id="forward_user_id"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                >
                                    <option :value="null">Select User</option>
                                    <option v-for="user in availableUsersForForward" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                        </div>

                        <!-- Office Selection -->
                        <div v-if="forwardData.forward_to_type === 'office'" class="space-y-2">
                            <label for="forward_office_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select Office</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="forwardData.forward_to_id"
                                    id="forward_office_id"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                >
                                    <option :value="null">Select Office</option>
                                    <option v-for="office in offices" :key="office.id" :value="office.id">
                                        {{ office.office_name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                        </div>

                        <!-- Municipality Selection -->
                        <div v-if="forwardData.forward_to_type === 'municipality'" class="space-y-2">
                            <label for="forward_municipality_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Select Municipality</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-map absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="forwardData.forward_to_id"
                                    id="forward_municipality_id"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                >
                                    <option :value="null">Select Municipality</option>
                                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                        {{ municipality.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="forwardErrors.forward_to_id" class="text-red-500 text-xs">{{ forwardErrors.forward_to_id }}</span>
                        </div>

                        <!-- Remarks: Optional -->
                        <div class="space-y-2">
                            <label for="forward_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                            <div class="relative flex items-start">
                                <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea
                                    v-model="forwardData.remarks"
                                    id="forward_remarks"
                                    placeholder="Add remarks for forwarding..."
                                    rows="3"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <div v-if="forwardErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ forwardErrors.submit }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="handleSubmitForward"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Forward
                        </button>
                        <button
                            @click="closeForwardModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Finalize Document Modal: Confirmation dialog for finalizing a document -->
        <Teleport to="body" v-if="showFinalizeModal && documentToFinalize">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeFinalizeModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows success icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-green-50 to-green-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                            Finalize Document
                        </h3>
                        <!-- Close Button -->
                        <button @click="closeFinalizeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Confirmation message -->
                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    Are you sure you want to finalize document <span class="font-semibold">{{ documentToFinalize.tracking_no }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. The document status will be changed to "Finalized" immediately.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer: Confirm and Cancel buttons -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="confirmFinalizeDocument"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Finalize
                        </button>
                        <button
                            @click="closeFinalizeModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Receive Document Modal: Confirmation dialog for receiving a document -->
        <Teleport to="body" v-if="showReceiveModal && documentToReceive">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeReceiveModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header: Shows inbox icon and title -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-purple-50 to-purple-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-inbox text-purple-600 dark:text-purple-400"></i>
                            Receive Document
                        </h3>
                        <!-- Close Button -->
                        <button @click="closeReceiveModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body: Confirmation message -->
                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    Are you sure you want to receive document <span class="font-semibold">{{ documentToReceive.tracking_no }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This will log your receipt of the document.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer: Confirm and Cancel buttons -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="confirmReceiveDocument"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Receive
                        </button>
                        <button
                            @click="closeReceiveModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Document Transactions Modal: Displays all transactions for a document -->
        <Teleport to="body" v-if="showTransactionsModal && documentViewingTransactions">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeTransactionsModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-purple-50 to-purple-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
                            Document Transactions
                        </h3>
                        <button @click="closeTransactionsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Tracking No.: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.tracking_no }}</span>
                            </p>
                            <p v-if="documentViewingTransactions.source" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Source: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.source }}</span>
                            </p>
                            <p v-if="documentViewingTransactions.particulars" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Particulars: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ documentViewingTransactions.particulars }}</span>
                            </p>
                        </div>

                        <!-- No Transactions State -->
                        <div v-if="documentTransactions.length === 0" class="py-8 text-center">
                            <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-3xl mb-2 block"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-400">No transactions found</p>
                        </div>

                        <!-- Transactions Timeline -->
                        <div v-else class="space-y-3">
                            <div v-for="transaction in documentTransactions" :key="transaction.id" class="flex gap-4 pb-4 border-l-2 border-gray-300 dark:border-gray-600 pl-4">
                                <!-- Timeline Dot -->
                                <div class="flex-shrink-0 mt-1">
                                    <div v-if="getActionType(transaction.action) === 'created'" class="w-3 h-3 rounded-full bg-green-500 ring-2 ring-green-200 dark:ring-green-900/30 -ml-5"></div>
                                    <div v-else-if="getActionType(transaction.action) === 'forwarded'" class="w-3 h-3 rounded-full bg-cyan-500 ring-2 ring-cyan-200 dark:ring-cyan-900/30 -ml-5"></div>
                                    <div v-else-if="getActionType(transaction.action) === 'received'" class="w-3 h-3 rounded-full bg-orange-500 ring-2 ring-orange-200 dark:ring-orange-900/30 -ml-5"></div>
                                    <div v-else-if="getActionType(transaction.action) === 'finalized'" class="w-3 h-3 rounded-full bg-purple-500 ring-2 ring-purple-200 dark:ring-purple-900/30 -ml-5"></div>
                                </div>

                                <!-- Transaction Details -->
                                <div class="flex-grow">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-grow">
                                            <!-- Action Badge -->
                                            <div class="mb-1">
                                                <span v-if="getActionType(transaction.action) === 'created'" class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Created</span>
                                                <span v-else-if="getActionType(transaction.action) === 'forwarded'" class="inline-block px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                <span v-else-if="getActionType(transaction.action) === 'received'" class="inline-block px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">Received</span>
                                                <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Finalized</span>
                                            </div>
                                            
                                            <!-- Action Details -->
                                            <p class="text-xs text-gray-700 dark:text-gray-300 mt-1">{{ transaction.action }}</p>

                                            <!-- User, timestamp, and recipient info -->
                                            <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded text-xs">
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.user?.name || 'Unknown User' }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">
                                                        — {{ new Date(transaction.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                                        at {{ new Date(transaction.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                                                    </span>
                                                </p>
                                                <p v-if="transaction.duration_hours !== null && transaction.duration_hours !== undefined" class="text-gray-600 dark:text-gray-400 mt-1">
                                                    <i class="fas fa-hourglass-end text-blue-600 dark:text-blue-400 mr-1"></i>
                                                    Duration: <span class="font-semibold">
                                                        <span v-if="transaction.duration_hours >= 24">{{ Math.floor(transaction.duration_hours / 24) }} days {{ transaction.duration_hours % 24 }} hours</span>
                                                        <span v-else>{{ transaction.duration_hours }} hours</span>
                                                    </span>
                                                </p>
                                                <p v-if="getActionType(transaction.action) === 'forwarded' && (transaction.forwardedToUser || transaction.forwardedToOffice || transaction.forwardedToMunicipality)" class="text-gray-600 dark:text-gray-400 mt-1">
                                                    <span v-if="transaction.forwardedToUser">
                                                        Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToUser.name }}</span>
                                                    </span>
                                                    <span v-else-if="transaction.forwardedToOffice">
                                                        Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToOffice.office_name }}</span>
                                                    </span>
                                                    <span v-else-if="transaction.forwardedToMunicipality">
                                                        Forwarded to: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ transaction.forwardedToMunicipality.name }}</span>
                                                    </span>
                                                </p>
                                            </div>

                                            <!-- Remarks -->
                                            <p v-if="transaction.remarks" class="text-xs text-gray-700 dark:text-gray-300 mt-2 italic">
                                                "{{ transaction.remarks }}"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="closeTransactionsModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Close
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

/**
 * Document interface defines the structure of document data
 * Represents a single document entity with all its properties
 */
interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string | null;
    source: string | null;
    status: string;
    remarks: string | null;
    user_id: number;
    user?: { id: number; name: string } | null;
    transactions?: DocumentTransaction[];
}

// ============== Toast Component Reference ==============

/** Reference to the Toast component for displaying notifications */
const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Helper Functions ==============

/** Get CSRF token from meta tag for form submissions */
const getCsrfToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    return token;
};

/** Check if current user has a specific permission */
const hasPermission = (permission: string): boolean => {
    return permissions.value.includes(permission);
};

// ============== Reactive State Management ==============

/** Stores the list of all documents fetched from the API */
const documents = ref<Document[]>([]);

/** Tracks the loading state of the page */
const loading = ref(true);

/** Stores error messages if any API call fails */
const error = ref<string | null>(null);

/** Stores the search query for filtering documents */
const searchQuery = ref('');

/** Current page number for pagination */
const currentPage = ref(1);

/** Number of items to display per page */
const itemsPerPage = ref(10);

/** The field to sort documents by */
const sortBy = ref<'id' | 'tracking_no' | 'date' | 'document_type' | 'source' | 'particulars' | 'remarks' | 'user_id' | 'status'>('id');

/** Sort direction: 'asc' for ascending, 'desc' for descending */
const sortOrder = ref<'asc' | 'desc'>('asc');

/** Tracks which document's dropdown menu is currently open (by document ID) */
const activeDropdown = ref<number | null>(null);

/** Controls visibility of the Create Document modal */
const showCreateModal = ref(false);

/** Controls visibility of the Edit Document modal */
const showEditModal = ref(false);

/** Controls visibility of the Delete Document modal */
const showDeleteModal = ref(false);

/** Controls visibility of the Forward Document modal */
const showForwardModal = ref(false);

/** Stores the document being edited */
const editingDocument = ref<Document | null>(null);

/** Stores the document to be deleted */
const documentToDelete = ref<Document | null>(null);

/** Stores the document to be forwarded */
const documentToForward = ref<Document | null>(null);

/** Forward form data */
const forwardData = ref({
    forward_to_type: 'user', // 'user', 'office', or 'municipality'
    forward_to_id: null as number | null,
    remarks: '',
});

/** Stores forward form errors */
const forwardErrors = ref<Record<string, string>>({});

/** Controls visibility of the Finalize Document modal */
const showFinalizeModal = ref(false);

/** Stores the document to be finalized */
const documentToFinalize = ref<Document | null>(null);

/** Controls visibility of the Receive Document modal */
const showReceiveModal = ref(false);

/** Stores the document to be received */
const documentToReceive = ref<Document | null>(null);

/** Stores the list of offices for internal source */
const offices = ref<Array<{id: number; office_name: string}>>([]);

/** Stores the list of municipalities for external source */
const municipalities = ref<Array<{id: number; name: string}>>([]);

/** Stores the list of users for document forwarding */
const users = ref<Array<{id: number; name: string}>>([]);

// ============== User & Permissions ==============

/** Stores the current authenticated user information */
const currentUser = ref<any>(null);

/** Stores the current user's permissions */
const permissions = ref<string[]>([]);

/** Controls visibility of the Document Transactions modal */
const showTransactionsModal = ref(false);

/** Stores the document whose transactions are being viewed */
const documentViewingTransactions = ref<Document | null>(null);

/** Stores the transactions for the selected document */
interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id?: number | null;
    action: string;
    remarks: string | null;
    created_at: string;
    duration_hours?: number | null;
    user?: { id: number; name: string };
    forwardedToUser?: { id: number; name: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
}

/**
 * Helper function to determine action type from action string
 */
const getActionType = (action: string): 'created' | 'forwarded' | 'finalized' | 'received' => {
    if (action.toLowerCase().includes('created')) return 'created';
    if (action.toLowerCase().includes('forwarded')) return 'forwarded';
    if (action.toLowerCase().includes('received')) return 'received';
    if (action.toLowerCase().includes('finalized')) return 'finalized';
    return 'created'; // default
};

/**
 * canReceiveDocument: Check if the current user can receive the document
 * Returns true if:
 * - Document is forwarded
 * - Document hasn't been received yet
 * - Current user is NOT the one who forwarded it OR is an Administrator/Developer
 */
const canReceiveDocument = (document: Document): boolean => {
    if (document.status !== 'forwarded') {
        return false;
    }

    // Administrator and Developer can receive any forwarded document
    if (currentUser.value?.usertype === 'Administrator' || currentUser.value?.usertype === 'Developer') {
        // Check if document has been received
        if (document.transactions && document.transactions.length > 0) {
            const hasBeenReceived = document.transactions.some(transaction => 
                getActionType(transaction.action) === 'received'
            );
            if (hasBeenReceived) {
                return false;
            }
        }
        return true;
    }

    // Check if document has been received
    if (document.transactions && document.transactions.length > 0) {
        const hasBeenReceived = document.transactions.some(transaction => 
            getActionType(transaction.action) === 'received'
        );
        if (hasBeenReceived) {
            return false;
        }

        // Check if current user is the one who forwarded it
        const latestForwardedTransaction = [...document.transactions]
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .find(transaction => getActionType(transaction.action) === 'forwarded');
        
        if (latestForwardedTransaction && latestForwardedTransaction.user_id === currentUser.value?.id) {
            return false; // Current user forwarded it, so they can't receive it
        }
    }

    return true;
};

/**
 * getDisplayStatus: Get the display status based on current user's perspective
 * For forwarded documents:
 * - If forwarded to current user: "to be received"
 * - If forwarded by current user: "forwarded"
 * Otherwise: return the actual status
 */
const getDisplayStatus = (document: Document): string => {
    if (document.status !== 'forwarded') {
        return document.status;
    }

    // Check if document is forwarded to current user
    const latestTransaction = document.transactions && document.transactions.length > 0
        ? document.transactions[0]
        : null;
    
    if (latestTransaction && latestTransaction.forwarded_to_user_id === currentUser.value?.id) {
        return 'to be received';
    }

    // It's forwarded by current user
    return 'forwarded';
};

const documentTransactions = ref<DocumentTransaction[]>([]);

// ============== Form Data & Validation ==============

/** Form data for creating a new document */
const formData = ref({
    tracking_no: '',
    date: '',
    document_type: '',
    particulars: '',
    source: '',
    sourceType: 'internal', // 'internal' or 'external'
    status: 'pending',
    remarks: ''
}); 
/** Get today's date in YYYY-MM-DD format */
const todayDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

/** Get tracking number prefix (YYYY-MM-) based on selected date */
const trackingNoPrefix = computed(() => {
    if (!formData.value.date) return '';
    return formData.value.date.substring(0, 7) + '-';
});

/** Stores validation errors for form fields */
const formErrors = ref<Record<string, string>>({});

// ============== Computed Properties ==============

/**
 * filteredDocuments: Filters and sorts documents based on search query and sort settings
 * - Filters documents by multiple fields (tracking_no, date, document_type, source, particulars, etc.)
 * - Applies sorting based on sortBy and sortOrder
 * - Filters based on user viewing permissions
 * Returns the filtered and sorted array of documents
 */
const filteredDocuments = computed(() => {
    let filtered = documents.value.filter(document => {
        // Check if user can view this document
        if (permissions.value.includes('documents.view.all')) {
            // Can view all documents
            return true;
        } else if (permissions.value.includes('documents.view.assigned')) {
            // Can view documents assigned to them (user_id matches)
            if (document.user_id === currentUser.value?.id) {
                return true;
            }
            
            // Can view documents forwarded to them as a user
            const latestTransaction = document.transactions && document.transactions.length > 0 
                ? document.transactions[0] 
                : null;
            if (latestTransaction && latestTransaction.forwarded_to_user_id === currentUser.value?.id) {
                return true;
            }
            
            return false;
        }
        return false;
    });

    // Apply search filter
    filtered = filtered.filter(document => 
        document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
    
    // Sort
    filtered.sort((a, b) => {
        let aVal: any;
        let bVal: any;
        
        if (sortBy.value === 'user_id') {
            // Sort by user_id (numeric)
            aVal = a.user_id || 0;
            bVal = b.user_id || 0;
            aVal = Number(aVal);
            bVal = Number(bVal);
        } else if (sortBy.value === 'id') {
            // Handle numeric sorting for id
            aVal = Number(a[sortBy.value]) || 0;
            bVal = Number(b[sortBy.value]) || 0;
        } else {
            // Handle string sorting
            aVal = a[sortBy.value] || '';
            bVal = b[sortBy.value] || '';
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
 * Based on the filtered documents count and items per page
 */
const totalPages = computed(() => {
    return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
});

/**
 * paginatedDocuments: Slices filtered documents to show only the current page
 * Calculates start and end indices based on currentPage and itemsPerPage
 * Returns the documents for the current page only
 */
const paginatedDocuments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredDocuments.value.slice(start, end);
});

/**
 * Determine the source type (office or municipality) of the document being forwarded
 * by checking if the document source exists in the offices or municipalities lists
 */
const forwardDocumentSourceType = computed(() => {
    if (!documentToForward.value) return null;
    const source = documentToForward.value.source;
    
    // Check if source is an office
    if (offices.value.some(office => office.office_name === source)) {
        return 'office';
    }
    
    // Check if source is a municipality
    if (municipalities.value.some(municipality => municipality.name === source)) {
        return 'municipality';
    }
    
    return null;
});

/**
 * Filter users list to exclude the current document holder
 * Prevents forwarding document to the user who currently has it
 */
const availableUsersForForward = computed(() => {
    if (!documentToForward.value) return users.value;
    return users.value.filter(user => user.id !== documentToForward.value!.user_id);
});

/**
 * Helper: Check if document was forwarded to office or municipality
 * Users can directly receive documents with this status
 */
const isDocumentForwardedToOfficeOrMunicipality = (document: Document): boolean => {
    return document.status === 'forwarded';
};

/**
 * Get custodian display name: office/municipality if forwarded to them, otherwise current user
 */
const getCustodianDisplay = (document: Document): string => {
    // If no transactions, show document creator
    if (!document.transactions || document.transactions.length === 0) {
        return document.user?.name || 'Unknown';
    }

    const latestTransaction = document.transactions[0];
    
    // Check for forwarded destinations in order of priority
    if (latestTransaction.forwardedToOffice) {
        return latestTransaction.forwardedToOffice.office_name;
    }
    if (latestTransaction.forwardedToMunicipality) {
        return latestTransaction.forwardedToMunicipality.name;
    }
    if (latestTransaction.forwardedToUser) {
        return latestTransaction.forwardedToUser.name;
    }
    
    // Fallback to the user who performed the transaction
    return latestTransaction.user?.name || document.user?.name || 'Unknown';
};

// ============== Lifecycle Hooks ==============

/**
 * onMounted: Fetches documents from the API when component is mounted
 * - Sets loading state to true initially
 * - Makes API request to /api/documents
 * -    
        // Fetch offices and municipalities
        const officesResponse = await fetch('/api/offices');
        if (officesResponse.ok) {
            offices.value = await officesResponse.json();
        }
        
        const municipalitiesResponse = await fetch('/api/municipalities');
        if (municipalitiesResponse.ok) {
            municipalities.value = await municipalitiesResponse.json();
        }
     Populates documents.value with response data
 * - Handles errors and sets loading state to false on completion
 */
onMounted(async () => {
    try {
        // Fetch current user with permissions
        const currentUserResponse = await fetch('/api/user/current');
        if (currentUserResponse.ok) {
            const userData = await currentUserResponse.json();
            currentUser.value = userData;
            permissions.value = userData.permissions || [];
        }

        const response = await fetch('/api/documents');
        if (!response.ok) {
            throw new Error('Failed to fetch documents');
        }
        documents.value = await response.json();
        
        // Fetch offices and municipalities
        const officesResponse = await fetch('/api/offices');
        if (officesResponse.ok) {
            offices.value = await officesResponse.json();
        }
        
        const municipalitiesResponse = await fetch('/api/municipalities');
        if (municipalitiesResponse.ok) {
            municipalities.value = await municipalitiesResponse.json();
        }

        // Fetch users for forwarding
        const usersResponse = await fetch('/api/users');
        if (usersResponse.ok) {
            users.value = await usersResponse.json();
        }
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
const toggleSort = (field: 'id' | 'tracking_no' | 'date' | 'document_type' | 'source' | 'particulars' | 'remarks' | 'user_id' | 'status') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

/**
 * toggleDropdown: Toggle the action dropdown menu for a document
 * @param {number} documentId - The ID of the document
 * If dropdown is already open for this document, close it
 * Otherwise, open the dropdown for this document (closes any other open dropdowns)
 */
const toggleDropdown = (documentId: number) => {
    activeDropdown.value = activeDropdown.value === documentId ? null : documentId;
};

/**
 * handleEditDocument: Opens the Edit Document modal with the selected document data
 * @param {Document} document - The document object to edit
 * Populates formData with the current document values
 * Sets editingDocument to the document being edited
 * Opens the edit modal
 */
const handleEditDocument = (document: Document) => {
    editingDocument.value = document;
    // Determine sourceType based on whether source is an office or municipality
    let sourceType = 'internal';
    if (document.source && municipalities.value.some(m => m.name === document.source)) {
        sourceType = 'external';
    }
    
    formData.value = {
        tracking_no: document.tracking_no,
        date: document.date,
        document_type: document.document_type,
        particulars: document.particulars || '',
        source: document.source || '',
        sourceType: sourceType,
        status: document.status || 'pending',
        remarks: document.remarks || ''
    };
    formErrors.value = {};
    showEditModal.value = true;
};

/**
 * closeEditModal: Closes the Edit Document modal
 * Clears the editingDocument reference
 */
const closeEditModal = () => {
    showEditModal.value = false;
    editingDocument.value = null;
};

/**
 * handleUpdateDocument: Submits the form to update an existing document
 * - Validates form data first
 * - Makes PUT request to /api/documents/{id} with updated data and Bearer token
 * - On success: updates the document in the documents array, closes the modal, and shows success toast with document details
 * - On error: sets formErrors['submit'] with the error message and shows error toast
 */
const handleUpdateDocument = async () => {
    if (!validateForm() || !editingDocument.value) return;
    
    try {
        // Prepare data without sourceType and user_id (set server-side)
        const submitData = {
            tracking_no: formData.value.tracking_no,
            date: formData.value.date,
            document_type: formData.value.document_type,
            particulars: formData.value.particulars,
            source: formData.value.source,
            status: formData.value.status,
            remarks: formData.value.remarks
        };
        
        const response = await fetch(`/api/documents/${editingDocument.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify(submitData),
            credentials: 'include'
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to update document');
        }
        
        const updatedDocument = await response.json();
        const index = documents.value.findIndex(d => d.id === editingDocument.value!.id);
        if (index !== -1) {
            documents.value[index] = updatedDocument;
        }
        closeEditModal();
        
        toastRef.value?.add(
            'info',
            'Success',
            `Document: <strong>${updatedDocument.tracking_no}</strong> has been updated successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleDeleteDocument: Opens the Delete Document confirmation modal
 * @param {Document} document - The document object to delete
 * Sets documentToDelete to the document being confirmed for deletion
 * Opens the delete confirmation modal
 */
const handleDeleteDocument = (document: Document) => {
    documentToDelete.value = document;
    showDeleteModal.value = true;
};

/**
 * closeDeleteModal: Closes the Delete Document modal
 * Clears the documentToDelete reference
 */
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    documentToDelete.value = null;
};

/**
 * confirmDeleteDocument: Confirms and executes the deletion of a document
 * - Makes DELETE request to /api/documents/{id} with CSRF token
 * - On success: removes the document from the documents array, closes the modal, and shows success toast with document details
 * - On error: shows an error toast with the error message
 */
const confirmDeleteDocument = async () => {
    if (!documentToDelete.value) return;
    
    const deletingDocument = documentToDelete.value;
    
    try {
        const response = await fetch(`/api/documents/${deletingDocument.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            credentials: 'include'
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to delete document');
        }
        
        documents.value = documents.value.filter(d => d.id !== deletingDocument.id);
        closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Success',
            `Document: <strong>${deletingDocument.tracking_no}</strong> has been deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleForwardDocument: Opens the Forward Document modal
 * @param {Document} document - The document to forward
 * Default button selection is User, but source office/municipality can be auto-selected when switching buttons
 */
const handleForwardDocument = (document: Document) => {
    documentToForward.value = document;
    forwardData.value = {
        forward_to_type: 'user',
        forward_to_id: null,
        remarks: '',
    };
    forwardErrors.value = {};
    showForwardModal.value = true;
};

/**
 * selectForwardOffice: Selects office type and auto-selects document source office if available
 */
const selectForwardOffice = () => {
    forwardData.value.forward_to_type = 'office';
    
    // Auto-select the document source if it's an office
    if (documentToForward.value?.source) {
        const officeMatch = offices.value.find(office => office.office_name === documentToForward.value!.source);
        if (officeMatch) {
            forwardData.value.forward_to_id = officeMatch.id;
        } else {
            forwardData.value.forward_to_id = null;
        }
    } else {
        forwardData.value.forward_to_id = null;
    }
};

/**
 * selectForwardMunicipality: Selects municipality type and auto-selects document source municipality if available
 */
const selectForwardMunicipality = () => {
    forwardData.value.forward_to_type = 'municipality';
    
    // Auto-select the document source if it's a municipality
    if (documentToForward.value?.source) {
        const municipalityMatch = municipalities.value.find(municipality => municipality.name === documentToForward.value!.source);
        if (municipalityMatch) {
            forwardData.value.forward_to_id = municipalityMatch.id;
        } else {
            forwardData.value.forward_to_id = null;
        }
    } else {
        forwardData.value.forward_to_id = null;
    }
};

/**
 * closeForwardModal: Closes the Forward Document modal
 */
const closeForwardModal = () => {
    showForwardModal.value = false;
    documentToForward.value = null;
    forwardData.value = {
        forward_to_type: 'user',
        forward_to_id: null,
        remarks: '',
    };
    forwardErrors.value = {};
};

/**
 * handleSubmitForward: Submits the forward form to forward a document to another user, office, or municipality
 */
const handleSubmitForward = async () => {
    if (!documentToForward.value || !forwardData.value.forward_to_id) {
        forwardErrors.value['forward_to_id'] = 'Please select a recipient';
        return;
    }

    try {
        const response = await fetch(`/api/documents/${documentToForward.value.id}/forward`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({
                forward_to_type: forwardData.value.forward_to_type,
                forward_to_id: forwardData.value.forward_to_id,
                remarks: forwardData.value.remarks || null,
            }),
            credentials: 'include'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to forward document');
        }

        const updatedDocument = await response.json();
        const index = documents.value.findIndex(d => d.id === documentToForward.value!.id);
        if (index !== -1) {
            documents.value[index] = updatedDocument;
        }

        closeForwardModal();

        toastRef.value?.add(
            'success',
            'Success',
            `Document: <strong>${updatedDocument.tracking_no}</strong> has been forwarded successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        forwardErrors.value['submit'] = errorMsg;

        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleFinalizeDocument: Opens the Finalize Document modal
 * @param {Document} document - The document to finalize
 */
const handleFinalizeDocument = (document: Document) => {
    if (document.status !== 'pending') {
        toastRef.value?.add('error', 'Error', 'Only pending documents can be finalized', 4000);
        return;
    }

    documentToFinalize.value = document;
    showFinalizeModal.value = true;
};

/**
 * closeFinalizeModal: Closes the Finalize Document modal
 */
const closeFinalizeModal = () => {
    showFinalizeModal.value = false;
    documentToFinalize.value = null;
};

/**
 * confirmFinalizeDocument: Confirms and executes the finalization of a document
 */
const confirmFinalizeDocument = async () => {
    if (!documentToFinalize.value) return;

    const finalizingDocument = documentToFinalize.value;

    try {
        const response = await fetch(`/api/documents/${finalizingDocument.id}/finalize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            credentials: 'include'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to finalize document');
        }

        const updatedDocument = await response.json();
        const index = documents.value.findIndex(d => d.id === finalizingDocument.id);
        if (index !== -1) {
            documents.value[index] = updatedDocument;
        }

        closeFinalizeModal();

        toastRef.value?.add(
            'success',
            'Success',
            `Document: <strong>${updatedDocument.tracking_no}</strong> has been finalized successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';

        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * handleReceiveDocument: Opens the Receive Document modal
 * @param {Document} document - The document to receive
 */
const handleReceiveDocument = (document: Document) => {
    documentToReceive.value = document;
    showReceiveModal.value = true;
};

/**
 * closeReceiveModal: Closes the Receive Document modal
 */
const closeReceiveModal = () => {
    showReceiveModal.value = false;
    documentToReceive.value = null;
};

/**
 * confirmReceiveDocument: Confirms and executes the receipt of a document
 */
const confirmReceiveDocument = async () => {
    if (!documentToReceive.value) return;

    const receivingDocument = documentToReceive.value;

    try {
        const response = await fetch(`/api/documents/${receivingDocument.id}/receive`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            credentials: 'include'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to receive document');
        }

        const updatedDocument = await response.json();
        const index = documents.value.findIndex(d => d.id === receivingDocument.id);
        if (index !== -1) {
            documents.value[index] = updatedDocument;
        }

        closeReceiveModal();

        toastRef.value?.add(
            'success',
            'Success',
            `Document: <strong>${updatedDocument.tracking_no}</strong> has been received successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';

        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    }
};

/**
 * openCreateModal: Opens the Create Document modal
 * - Resets formData to empty values (except date which is set to today)
 * - Clears any existing form errors
 * - Sets showCreateModal to true
 */
const openCreateModal = () => {
    formData.value = {
        tracking_no: '',
        date: todayDate.value,
        document_type: '',
        particulars: '',
        source: '',
        sourceType: 'internal',
        status: 'pending',
        remarks: ''
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

/**
 * closeCreateModal: Closes the Create Document modal
 * Sets showCreateModal to false
 */
const closeCreateModal = () => {
    showCreateModal.value = false;
};

/**
 * validateForm: Validates the form data for creating a document
 * Required fields: tracking_no, date, document_type
 * Returns true if all validations pass, false otherwise
 * Sets formErrors with specific error messages for invalid fields
 */
const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.tracking_no.trim()) {
        formErrors.value['tracking_no'] = 'Tracking No is required';
    }
    
    if (!formData.value.date.trim()) {
        formErrors.value['date'] = 'Date is required';
    }
    
    if (!formData.value.document_type.trim()) {
        formErrors.value['document_type'] = 'Document Type is required';
    }
    
    if (!formData.value.source.trim()) {
        formErrors.value['source'] = 'Source is required';
    }
    
    if (!formData.value.particulars.trim()) {
        formErrors.value['particulars'] = 'Particulars is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

/**
 * viewDocumentTransactions: Opens the transactions view modal for a document
 * - Fetches all transactions for the selected document
 * - Displays them in a modal with user info and timestamp
 */
const viewDocumentTransactions = async (document: Document) => {
    documentViewingTransactions.value = document;
    showTransactionsModal.value = true;
    
    try {
        const response = await fetch(`/api/documents/${document.id}/transactions`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'include'
        });
        
        if (response.ok) {
            documentTransactions.value = await response.json();
        }
    } catch (e) {
        console.error('Failed to fetch transactions:', e);
    }
};

/**
 * closeTransactionsModal: Closes the Document Transactions modal
 */
const closeTransactionsModal = () => {
    showTransactionsModal.value = false;
    documentViewingTransactions.value = null;
    documentTransactions.value = [];
};

/**
 * handleCreateDocument: Submits the form to create a new document
 * - Validates form data first
 * - Makes POST request to /api/documents with form data and CSRF token
 * - On success: adds the new document to the documents array, closes the modal, and shows success toast with document details
 * - On error: sets formErrors['submit'] with the error message and shows error toast
 */
const handleCreateDocument = async () => {
    if (!validateForm()) return;
    
    try {
        // Prepare data without sourceType
        const submitData = {
            tracking_no: formData.value.tracking_no,
            date: formData.value.date,
            document_type: formData.value.document_type,
            particulars: formData.value.particulars,
            source: formData.value.source,
            status: formData.value.status,
            remarks: formData.value.remarks
        };
        
        const response = await fetch('/api/documents', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify(submitData),
            credentials: 'include'
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || 'Failed to create document');
        }
        
        const newDocument = await response.json();
        documents.value.push(newDocument);
        closeCreateModal();
        
        toastRef.value?.add(
            'success',
            'Success',
            `Document: <strong>${newDocument.tracking_no}</strong> has been created successfully!`,
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
