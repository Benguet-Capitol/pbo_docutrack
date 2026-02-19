<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Documents Processing Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <!-- Header Section: Contains search bar and items-per-page selector -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-clipboard-list text-teal-600 dark:text-teal-400 mr-2"></i>
                                Documents Summary
                            </h3>
                            <div class="flex items-center gap-3">
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

                        <!-- Filter Section -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <!-- Year Filter -->
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Year</label>
                                <select
                                    v-model.number="selectedYear"
                                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                >
                                    <option :value="null">All Years</option>
                                    <option v-for="year in availableYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>

                            <!-- Semester Filter -->
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Semester</label>
                                <select
                                    v-model.number="selectedSemester"
                                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                >
                                    <option :value="null">All Semesters</option>
                                    <option :value="1">1st Semester (Jan - Jun)</option>
                                    <option :value="2">2nd Semester (Jul - Dec)</option>
                                </select>
                            </div>

                            <!-- User Filter -->
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">User</label>
                                <select
                                    v-model.number="selectedUser"
                                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                >
                                    <option :value="null">All Users</option>
                                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Reset Filters Button and Generate Report Button -->
                            <div class="flex items-end pt-1 gap-2 w-full">
                                <button
                                    @click="selectedYear = null; selectedSemester = null; selectedUser = null"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-xs font-medium"
                                >
                                    <i class="fas fa-times"></i>
                                    Reset
                                </button>
                                <button
                                    @click="showReportModal = true"
                                    class="ml-auto inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors text-xs font-medium"
                                >
                                    <i class="fas fa-file-pdf"></i>
                                    Budget Proposal Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        <button @click="sortBy = 'tracking_no'; sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                            Tracking No
                                            <span v-if="sortBy === 'tracking_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        <button @click="sortBy = 'date'; sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                            Date
                                            <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Document Type
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Source
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Particulars
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Custodian
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Processing Time
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Remaining Duration
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Transactions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template v-if="paginatedDocuments.length > 0">
                                    <template v-for="document in paginatedDocuments" :key="document.id">
                                        <tr @click="toggleExpanded(document.id)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentId === document.id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                            <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                                {{ document.tracking_no }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                                {{ new Date(document.date).toLocaleDateString() }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.document_type">{{ document.document_type }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.source">{{ document.source }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.particulars" :title="document.particulars">{{ document.particulars }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                                {{ getCustodianName(document) }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center">
                                                <span v-if="document.status === 'created'" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">Created</span>
                                                <span v-else-if="document.status === 'forwarded'" class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded-full text-xs font-medium">Forwarded</span>
                                                <span v-else-if="document.status === 'pending'" class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-medium">Pending</span>
                                                <span v-else-if="document.status === 'finalized'" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium">Transaction Ended</span>
                                                <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">{{ document.status }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center text-gray-600 dark:text-gray-400 font-medium">
                                                {{ calculateProcessingTime(document) }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center font-medium">
                                                <div :class="getTimeLeftStyles(document)">
                                                    {{ getTimeLeftText(document) }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center">
                                                <button
                                                    @click.stop="toggleExpanded(document.id)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                                >
                                                    <i :class="['fas', expandedDocumentId === document.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                    {{ document.transactions?.length || 0 }}
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Expanded Transactions Row -->
                                        <tr v-if="expandedDocumentId === document.id" :key="`transactions-${document.id}`" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="10" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
                                                    Transaction History
                                                </h4>
                                                
                                                <!-- No Transactions State -->
                                                <div v-if="document.transactions.length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No transactions found</p>
                                                </div>

                                                <!-- Transactions Timeline (Grid) -->
                                                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-4">
                                                    <div v-for="(transaction, index) in [...document.transactions].reverse()" :key="transaction.id" class="relative">
                                                        <!-- Timeline Dot -->
                                                        <div class="absolute -top-3 left-3 z-10">
                                                            <div v-if="getActionType(transaction.action) === 'created'" class="w-5 h-5 rounded-full bg-green-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-check text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'forwarded'" class="w-5 h-5 rounded-full bg-cyan-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-arrow-right text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'received'" class="w-5 h-5 rounded-full bg-orange-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-inbox text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'finalized'" class="w-5 h-5 rounded-full bg-purple-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-check-double text-white text-xs"></i>
                                                            </div>
                                                        </div>

                                                        <!-- Transaction Card -->
                                                        <div class="pt-6 p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm h-full">
                                                            <!-- Action Badge -->
                                                            <div class="mb-2">
                                                                <span v-if="getActionType(transaction.action) === 'created'" class="inline-block px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Created</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'forwarded'" class="inline-block px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'received'" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">Received</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Transaction Ended</span>
                                                            </div>

                                                            <!-- Date/Time -->
                                                            <div class="flex gap-3 mb-2">
                                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-calendar text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                    {{ new Date(transaction.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                                                </p>
                                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-clock text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                    {{ new Date(transaction.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                                                                </p>
                                                            </div>

                                                            <!-- User and Duration -->
                                                            <div class="flex gap-2 mb-2 items-start">
                                                                <p class="text-xs text-gray-700 dark:text-gray-300 break-words font-semibold flex-1">
                                                                    {{ transaction.user?.name || 'Unknown User' }}
                                                                </p>
                                                                <p v-if="transaction.duration_hours !== null && transaction.duration_hours !== undefined" class="text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">
                                                                    <i class="fas fa-hourglass-end mr-1"></i>
                                                                    <span v-if="transaction.duration_hours >= 24">{{ Math.floor(transaction.duration_hours / 24) }}d {{ transaction.duration_hours % 24 }}h</span>
                                                                    <span v-else>{{ transaction.duration_hours }}h</span>
                                                                </p>
                                                            </div>

                                                            <!-- Action -->
                                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                                                <i class="fas fa-tasks text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                {{ transaction.action }}
                                                            </p>

                                                            <!-- Recipient (if forwarded) -->
                                                            <p v-if="getActionType(transaction.action) === 'forwarded' && (transaction.forwardedToUser || transaction.forwardedToOffice || transaction.forwardedToMunicipality)" class="text-xs text-gray-600 dark:text-gray-400 mb-2 break-words">
                                                                <i class="fas fa-arrow-right text-cyan-500 mr-1"></i>
                                                                <span v-if="transaction.forwardedToUser">{{ transaction.forwardedToUser.name }}</span>
                                                                <span v-else-if="transaction.forwardedToOffice">{{ transaction.forwardedToOffice.office_name }}</span>
                                                                <span v-else-if="transaction.forwardedToMunicipality">{{ transaction.forwardedToMunicipality.name }}</span>
                                                            </p>

                                                            <!-- Remarks -->
                                                            <p v-if="transaction.remarks" class="text-xs text-gray-600 dark:text-gray-400 italic border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                                                                {{ transaction.remarks }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </template>
                                </template>
                                <tr v-else>
                                    <td :colspan="10" class="px-6 py-8 text-center">
                                        <div v-if="loading" class="text-gray-500 dark:text-gray-400 space-y-2">
                                            <i class="fas fa-spinner fa-spin text-2xl opacity-30"></i>
                                            <p>Loading documents...</p>
                                        </div>
                                        <div v-else-if="error" class="text-red-600 dark:text-red-400">
                                            <i class="fas fa-exclamation-circle text-2xl opacity-30 block mb-2"></i>
                                            <p>{{ error }}</p>
                                        </div>
                                        <div v-else class="text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                            <p>No documents found</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div v-if="!loading && documents.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Pagination Info -->
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredDocuments.length) }}</span> of <span class="font-semibold">{{ filteredDocuments.length }}</span> documents
                        </div>
                        <!-- Pagination Buttons -->
                        <div class="flex items-center gap-1">
                            <!-- First Page Button -->
                            <button
                                @click="changePage(1)"
                                :disabled="currentPage === 1"
                                class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <!-- Previous Page Button -->
                            <button
                                @click="changePage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Prev
                            </button>
                            <!-- Page Number Buttons -->
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
                            <!-- Next Page Button -->
                            <button
                                @click="changePage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                            <!-- Last Page Button -->
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

            <!-- Statistics Panels Grid: Side-by-side layout for Users and Documents statistics -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Users Summary Section: Average Duration per Transaction -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <!-- Header Section -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                            Summary of Users Average Time per Transaction
                        </h3>
                    </div>

                <!-- User Statistics Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                    User Name
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Total Transactions
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Average Duration
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="filteredUserStatistics.length > 0">
                                <template v-for="(user, userIndex) in filteredUserStatistics" :key="`user-${userIndex}`">
                                    <tr 
                                        @click="toggleUserExpanded(user.userId)"
                                        :class="['transition-colors duration-150 cursor-pointer', expandedUserId === user.userId ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']"
                                    >
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <i :class="['fas', expandedUserId === user.userId ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                            {{ user.name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex items-center justify-center px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                                {{ user.count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center font-medium">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                                <i class="fas fa-hourglass-end"></i>
                                                {{ formatHours(user.averageHours) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <!-- Expanded User Details Row -->
                                    <tr v-if="expandedUserId === user.userId" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="3" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                                                    Detailed Breakdown
                                                </h4>
                                                
                                                <!-- No Details State -->
                                                <div v-if="selectedUserDetails.length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No document details found</p>
                                                </div>

                                                <!-- Details Table -->
                                                <div v-else class="overflow-x-auto">
                                                    <table class="w-full text-xs">
                                                        <thead class="bg-gray-200 dark:bg-gray-600">
                                                            <tr>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Document</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Received</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Completed</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Pending Time</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                            <tr v-for="detail in selectedUserDetails" :key="`detail-${detail.documentId}`" class="bg-white dark:bg-gray-800">
                                                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                                    {{ detail.trackingNo }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate" :title="detail.particulars">
                                                                    {{ detail.particulars }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ detail.startDate }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ detail.endDate }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center">
                                                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium whitespace-nowrap">
                                                                        <i class="fas fa-hourglass-end"></i>
                                                                        {{ formatHours(detail.pendingHours) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td :colspan="3" class="px-6 py-8 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                        <p class="text-sm">No user data available</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents Processing Time Section: Average Duration per document type -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
                        Average Processing Time for Documents
                    </h3>
                </div>

                <!-- Document Statistics Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                    Document Type
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Total Documents
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Average Duration
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="documentProcessingStatistics.length > 0">
                                <template v-for="docStat in documentProcessingStatistics" :key="`doc-${docStat.documentType}`">
                                    <tr @click="toggleDocumentTypeExpanded(docStat.documentType)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentType === docStat.documentType ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <i :class="['fas', expandedDocumentType === docStat.documentType ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                            {{ docStat.documentType }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex items-center justify-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-xs font-medium">
                                                {{ docStat.count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center font-medium">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                                <i class="fas fa-stopwatch"></i>
                                                {{ formatHours(docStat.averageHours) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <!-- Expanded Document Type Details Row -->
                                    <tr v-if="expandedDocumentType === docStat.documentType" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="3" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-list text-purple-600 dark:text-purple-400"></i>
                                                    Documents of Type: {{ docStat.documentType }}
                                                </h4>
                                                
                                                <!-- No Documents State -->
                                                <div v-if="getDocumentsForType(docStat.documentType).length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No documents found</p>
                                                </div>

                                                <!-- Documents Table -->
                                                <div v-else class="overflow-x-auto">
                                                    <table class="w-full text-xs">
                                                        <thead class="bg-gray-200 dark:bg-gray-600">
                                                            <tr>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Tracking No</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Processing Time</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Remaining Duration</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                            <tr v-for="doc in getDocumentsForType(docStat.documentType)" :key="`doc-detail-${doc.id}`" class="bg-white dark:bg-gray-800">
                                                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                                    {{ doc.tracking_no }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate" :title="doc.particulars || '-'">
                                                                    {{ doc.particulars || '-' }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                                                    <span v-if="doc.status === 'created'" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">Created</span>
                                                                    <span v-else-if="doc.status === 'forwarded'" class="px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                                    <span v-else-if="doc.status === 'pending'" class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded text-xs font-medium">Pending</span>
                                                                    <span v-else-if="doc.status === 'finalized'" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Ended</span>
                                                                    <span v-else class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded text-xs font-medium">{{ doc.status }}</span>
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ calculateProcessingTime(doc) }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center">
                                                                    <div :class="getTimeLeftStyles(doc)">
                                                                        {{ getTimeLeftText(doc) }}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td :colspan="3" class="px-6 py-8 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                        <p class="text-sm">No document data available</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

        <!-- Report Generation Modal -->
        <Teleport to="body" v-if="showReportModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showReportModal = false">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-blue-600 dark:text-blue-400"></i>
                            Generate Report
                        </h3>
                        <button @click="showReportModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-4">
                        <!-- As of Date -->
                        <div class="space-y-2">
                            <label for="report_as_of_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                            <input
                                v-model="reportData.asOfDate"
                                id="report_as_of_date"
                                type="date"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <!-- Reviewed By -->
                        <div class="space-y-2">
                            <label for="report_reviewed_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reviewed By</label>
                            <select
                                v-model.number="reportData.reviewedBy"
                                id="report_reviewed_by"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            >
                                <option :value="null">Select Supervisor</option>
                                <option v-for="user in supervisorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Certified Correct -->
                        <div class="space-y-2">
                            <label for="report_certified_correct" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Certified Correct</label>
                            <select
                                v-model.number="reportData.certifiedCorrect"
                                id="report_certified_correct"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            >
                                <option :value="null">Select Administrator</option>
                                <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="reportErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-800 dark:text-red-300 text-xs">{{ reportErrors.submit }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="generateReport"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Generate
                        </button>
                        <button
                            @click="showReportModal = false"
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
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';

interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id: number | null;
    forwarded_to_office_id: number | null;
    forwarded_to_municipality_id: number | null;
    action: string;
    remarks: string;
    created_at: string;
    duration_hours: number;
    user?: { id: number; name: string; email: string } | null;
    forwardedToUser?: { id: number; name: string; email: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
}

interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string;
    source: string;
    status: string;
    remarks: string;
    user_id: number;
    user?: { id: number; name: string; email: string } | null;
    transactions: DocumentTransaction[];
}

// ============== State ==============
const documents = ref<Document[]>([]);
const searchQuery = ref('');
const sortBy = ref('id');
const sortOrder = ref<'asc' | 'desc'>('desc');
const itemsPerPage = ref(10);
const currentPage = ref(1);
const expandedDocumentId = ref<number | null>(null);
const expandedUserId = ref<number | null>(null);
const expandedDocumentType = ref<string | null>(null);
const loading = ref(true);
const error = ref('');
const selectedYear = ref<number | null>(new Date().getFullYear());
const selectedSemester = ref<number | null>(null);
const selectedUser = ref<number | null>(null);

/** Reactive current time for real-time updates of Processing Time and Remaining Duration */
const currentTime = ref(new Date());

/** Stores the interval ID for cleanup on component unmount */
let timeUpdateInterval: NodeJS.Timeout | null = null;

// ============== Report Generation ==============
const showReportModal = ref(false);
const reportData = ref({
    asOfDate: new Date().toISOString().split('T')[0],
    reviewedBy: null as number | null,
    certifiedCorrect: null as number | null,
});
const reportErrors = ref<Record<string, string>>({});

// ============== Office/Municipality/User Lists ==============
const offices = ref<Array<{id: number; office_name: string}>>([]);
const municipalities = ref<Array<{id: number; name: string}>>([]);
const users = ref<Array<{id: number; name: string; usertype?: string}>>([]);
const employees = ref<Array<{id: number; employee_id: string; name: string; office_id: number; designation: string}>>([]);

// ============== Fetch Documents ==============
const fetchDocuments = async () => {
    try {
        loading.value = true;
        error.value = '';
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
        
        // Fetch users for lookup
        const usersResponse = await fetch('/api/users');
        if (usersResponse.ok) {
            users.value = await usersResponse.json();
        }
        
        // Fetch employees for designations
        const employeesResponse = await fetch('/api/employees');
        if (employeesResponse.ok) {
            employees.value = await employeesResponse.json();
        }
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'An error occurred while fetching documents';
        console.error('Error fetching documents:', error.value);
    } finally {
        loading.value = false;
    }
};

// ============== Computed Properties ==============

/**
 * Extract unique years from documents
 */
const availableYears = computed(() => {
    const years = new Set<number>();
    documents.value.forEach(doc => {
        const year = new Date(doc.date).getFullYear();
        years.add(year);
    });
    return Array.from(years).sort((a, b) => b - a);
});

/**
 * Extract users who have transactions
 */
const availableUsers = computed(() => {
    const usersMap = new Map<number, string>();
    documents.value.forEach(doc => {
        doc.transactions?.forEach(transaction => {
            if (transaction.user && !usersMap.has(transaction.user.id)) {
                usersMap.set(transaction.user.id, transaction.user.name);
            }
        });
    });
    return Array.from(usersMap, ([id, name]) => ({ id, name }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

/**
 * Get semester from date (1 = Jan-Jun, 2 = Jul-Dec)
 */
const getSemester = (date: string | Date): number => {
    const d = new Date(date);
    const month = d.getMonth() + 1;
    return month <= 6 ? 1 : 2;
};

/**
 * Check if document falls within selected year and semester
 */
const matchesYearAndSemester = (date: string): boolean => {
    if (!selectedYear.value && !selectedSemester.value) {
        return true;
    }
    
    const docDate = new Date(date);
    const docYear = docDate.getFullYear();
    const docSemester = getSemester(date);
    
    if (selectedYear.value && docYear !== selectedYear.value) {
        return false;
    }
    if (selectedSemester.value && docSemester !== selectedSemester.value) {
        return false;
    }
    
    return true;
};

const filteredDocuments = computed(() => {
    let filtered = documents.value.filter(document => {
        // Apply search query filter
        const matchesSearch = document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        if (!matchesSearch) return false;
        
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return false;
        
        // Apply user filter (filter documents where the selected user has transactions)
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return false;
        }
        
        return true;
    });

    // Sort
    filtered.sort((a, b) => {
        let aValue = a[sortBy.value as keyof Document];
        let bValue = b[sortBy.value as keyof Document];

        if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
        }
        if (typeof bValue === 'string') {
            bValue = bValue.toLowerCase();
        }

        if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return filtered;
});

const paginatedDocuments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredDocuments.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
});

/**
 * Filter users by role: Supervisor
 */
const supervisorUsers = computed(() => {
    return users.value.filter(user => user.usertype === 'Supervisor');
});

/**
 * Filter users by role: Administrator
 */
const administratorUsers = computed(() => {
    return users.value.filter(user => user.usertype === 'Administrator');
});

/**
 * Filter user statistics based on selected user
 */
const filteredUserStatistics = computed(() => {
    let filtered = userStatistics.value;
    
    // If a user is selected in the Users Summary section, only show that user
    if (selectedUser.value) {
        filtered = filtered.filter(user => user.userId === selectedUser.value);
    }
    
    return filtered;
});

/**
 * Calculate user statistics: average pending time per document
 * Timer starts when user receives the document and stops when they forward/finalize it
 */
const userStatistics = computed(() => {
    const stats = new Map<number, { userId: number; name: string; totalHours: number; count: number; averageHours: number }>();
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Apply user filter - only include documents where selected user (if any) has transactions
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return;
        }
        
        // Sort transactions by created_at to process in chronological order
        const sortedTransactions = [...(document.transactions || [])].sort(
            (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
        
        let currentOwner: { userId: number; userName: string; startTime: Date } | null = null;
        
        sortedTransactions.forEach((transaction) => {
            const action = getActionType(transaction.action);
            const transactionTime = new Date(transaction.created_at);
            
            if (action === 'created') {
                // Document created - start timer for creator
                currentOwner = {
                    userId: transaction.user_id,
                    userName: transaction.user?.name || 'Unknown',
                    startTime: transactionTime
                };
            } else if (action === 'forwarded' && currentOwner) {
                // Document forwarded - stop timer for current owner and calculate pending time
                const pendingMs = transactionTime.getTime() - currentOwner.startTime.getTime();
                const pendingHours = pendingMs / (1000 * 60 * 60);
                
                const existing = stats.get(currentOwner.userId) || {
                    userId: currentOwner.userId,
                    name: currentOwner.userName,
                    totalHours: 0,
                    count: 0,
                    averageHours: 0
                };
                
                existing.totalHours += pendingHours;
                existing.count += 1;
                existing.averageHours = existing.totalHours / existing.count;
                stats.set(currentOwner.userId, existing);
                
                // Transfer ownership to the forwarded recipient
                if (transaction.forwardedToUser) {
                    currentOwner = {
                        userId: transaction.forwardedToUser.id,
                        userName: transaction.forwardedToUser.name,
                        startTime: transactionTime
                    };
                } else {
                    // Forwarded to office or municipality, not tracked as user time
                    currentOwner = null;
                }
            } else if (action === 'received') {
                // Someone received the document - start timer for them
                currentOwner = {
                    userId: transaction.user_id,
                    userName: transaction.user?.name || 'Unknown',
                    startTime: transactionTime
                };
            } else if (action === 'finalized' && currentOwner) {
                // Document finalized - stop timer for current owner and calculate pending time
                const pendingMs = transactionTime.getTime() - currentOwner.startTime.getTime();
                const pendingHours = pendingMs / (1000 * 60 * 60);
                
                const existing = stats.get(currentOwner.userId) || {
                    userId: currentOwner.userId,
                    name: currentOwner.userName,
                    totalHours: 0,
                    count: 0,
                    averageHours: 0
                };
                
                existing.totalHours += pendingHours;
                existing.count += 1;
                existing.averageHours = existing.totalHours / existing.count;
                stats.set(currentOwner.userId, existing);
                
                currentOwner = null;
            }
        });
    });
    
    return Array.from(stats.values())
        .sort((a, b) => b.averageHours - a.averageHours); // Sort by average hours descending
});

/**
 * Calculate document processing statistics: average processing time per document type
 */
const documentProcessingStatistics = computed(() => {
    const stats = new Map<string, { documentType: string; totalHours: number; count: number; averageHours: number }>();
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Include all documents with transactions (regardless of status)
        if (!document.transactions || document.transactions.length === 0) return;
        
        const docType = document.document_type || 'Other';
        
        // Get the creation timestamp from the first transaction (has the actual time)
        const creationTransaction = document.transactions[document.transactions.length - 1];
        const createdAt = new Date(creationTransaction.created_at);
        // If document is still being processed, use current time; otherwise use latest transaction time
        const endTime = (document.status !== 'finalized' && document.status !== 'ended') 
            ? new Date() 
            : new Date(document.transactions[0].created_at);
        
        // Calculate business hours elapsed (excluding weekends and office/municipality forwarding time)
        const processingHours = calculateElapsedTimeExcluding(document, createdAt, endTime);
        
        const existing = stats.get(docType) || {
            documentType: docType,
            totalHours: 0,
            count: 0,
            averageHours: 0
        };
        
        existing.totalHours += processingHours;
        existing.count += 1;
        existing.averageHours = existing.totalHours / existing.count;
        stats.set(docType, existing);
    });
    
    return Array.from(stats.values())
        .sort((a, b) => b.averageHours - a.averageHours); // Sort by average hours descending
});

/**
 * Get detailed breakdown for selected user
 */
const selectedUserDetails = computed(() => {
    if (!expandedUserId.value && expandedUserId.value !== 0) return [];
    
    const currentSelectedUserStat = userStatistics.value.find(u => u.userId === expandedUserId.value);
    if (!currentSelectedUserStat) return [];
    
    const details: Array<{ documentId: number; trackingNo: string; particulars: string; pendingHours: number; startDate: string; endDate: string }> = [];
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Apply user filter - only include documents where selected user (if any) has transactions
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return;
        }
        
        const sortedTransactions = [...(document.transactions || [])].sort(
            (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
        
        let currentOwner: { userId: number; startTime: Date } | null = null;
        
        sortedTransactions.forEach((transaction) => {
            const action = getActionType(transaction.action);
            const transactionTime = new Date(transaction.created_at);
            
            if (action === 'created') {
                currentOwner = {
                    userId: transaction.user_id,
                    startTime: transactionTime
                };
            } else if (action === 'forwarded' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                const pendingMs = transactionTime.getTime() - currentOwner.startTime.getTime();
                const pendingHours = pendingMs / (1000 * 60 * 60);
                
                details.push({
                    documentId: document.id,
                    trackingNo: document.tracking_no,
                    particulars: document.particulars || '-',
                    pendingHours,
                    startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }),
                    endDate: new Date(transactionTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })
                });
                
                if (transaction.forwardedToUser) {
                    currentOwner = {
                        userId: transaction.forwardedToUser.id,
                        startTime: transactionTime
                    };
                } else {
                    currentOwner = null;
                }
            } else if (action === 'received') {
                currentOwner = {
                    userId: transaction.user_id,
                    startTime: transactionTime
                };
            } else if (action === 'finalized' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                const pendingMs = transactionTime.getTime() - currentOwner.startTime.getTime();
                const pendingHours = pendingMs / (1000 * 60 * 60);
                
                details.push({
                    documentId: document.id,
                    trackingNo: document.tracking_no,
                    particulars: document.particulars || '-',
                    pendingHours,
                    startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }),
                    endDate: new Date(transactionTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })
                });
                
                currentOwner = null;
            }
        });
    });
    
    return details;
});

/**
 * Calculate processing time from first 'created' transaction to latest transaction
 */
const calculateProcessingTime = (document: Document): string => {
    if (!document.transactions || document.transactions.length === 0) {
        return '-';
    }

    // Get the creation timestamp from the first transaction (has the actual time)
    const creationTransaction = document.transactions[document.transactions.length - 1];
    const createdAt = new Date(creationTransaction.created_at);
    const latestTransaction = document.transactions[0];
    
    // If document is still being processed, use current time; otherwise use latest transaction time
    const endTime = (document.status !== 'finalized' && document.status !== 'ended') 
        ? new Date() 
        : new Date(latestTransaction.created_at);

    // Calculate business hours elapsed (excluding weekends and office/municipality forwarding time)
    const businessHoursElapsed = calculateElapsedTimeExcluding(document, createdAt, endTime);
    
    // Format using existing formatter
    return formatHours(businessHoursElapsed);
};

/**
 * Get action type from action string
 */
const getActionType = (action: string): string => {
    if (action.toLowerCase().includes('created')) return 'created';
    if (action.toLowerCase().includes('forwarded')) return 'forwarded';
    if (action.toLowerCase().includes('received')) return 'received';
    if (action.toLowerCase().includes('finalized')) return 'finalized';
    return action;
};

/**
 * Get custodian display name: office/municipality/user based on latest transaction
 */
const getCustodianName = (document: Document): string => {
    if (document.transactions.length === 0) {
        return document.user?.name || 'Unknown';
    }

    const latestTransaction = document.transactions[0];
    
    // Check for office forwarding (by ID field)
    if (latestTransaction.forwarded_to_office_id) {
        const office = offices.value.find(o => o.id === latestTransaction.forwarded_to_office_id);
        if (office) {
            return office.office_name;
        }
    }
    
    // Check for municipality forwarding (by ID field)
    if (latestTransaction.forwarded_to_municipality_id) {
        const municipality = municipalities.value.find(m => m.id === latestTransaction.forwarded_to_municipality_id);
        if (municipality) {
            return municipality.name;
        }
    }
    
    // Check for user-to-user forwarding
    if (latestTransaction.forwarded_to_user_id) {
        // Check if the forwarded-to user has received the document
        const hasReceived = document.transactions.some(t => 
            t.forwarded_to_user_id === latestTransaction.forwarded_to_user_id &&
            t.action.toLowerCase().includes('received')
        );
        
        if (hasReceived) {
            // User has received it, show that user as custodian
            const forwardedUser = users.value.find(u => u.id === latestTransaction.forwarded_to_user_id);
            if (forwardedUser) {
                return forwardedUser.name;
            }
        } else {
            // User hasn't received it yet, show the forwarder as custodian
            return latestTransaction.user?.name || document.user?.name || 'Unknown';
        }
    }
    
    // Fallback to the user who performed the transaction
    return latestTransaction.user?.name || document.user?.name || 'Unknown';
};

/**
 * Get processing time limit in days based on document type
 */
const getProcessingTimeLimit = (docType: string): number => {
    const type = docType.toLowerCase().trim();
    if (type.includes('annual')) return 25; // Annual Budget: 25 days
    if (type.includes('supplemental')) return 12; // Supplemental Budget: 12 days
    if (type.includes('proposal') || type.includes('proposals')) return 12; // Budget Proposals: 12 days
    if (type.includes('referral')) {
        if (type.includes('simple')) return 3; // Referral - Simple: 3 days
        if (type.includes('complex')) return 7; // Referral - Complex: 7 days
        if (type.includes('highly technical')) return 20; // Referral - Highly Technical: 20 days
    }
    return 30; // Default: 30 days for other types
};

/**
 * Calculate business days (excluding weekends) between two dates
 */
const calculateBusinessDays = (startDate: Date, endDate: Date): number => {
    let count = 0;
    const current = new Date(startDate);
    
    while (current <= endDate) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            count++;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return count;
};

/**
 * Calculate business hours elapsed (excluding weekends)
 */
const calculateBusinessHoursElapsed = (startDate: Date, endDate: Date): number => {
    let businessHours = 0;
    let current = new Date(startDate); // Keep actual start time (including hours/minutes)
    const end = new Date(endDate);
    
    // Iterate through each day
    while (current < end) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            const nextDay = new Date(current);
            nextDay.setDate(nextDay.getDate() + 1);
            nextDay.setHours(0, 0, 0, 0); // Next day at midnight
            
            if (nextDay <= end) {
                // Calculate hours from current time to next midnight
                const msToMidnight = nextDay.getTime() - current.getTime();
                businessHours += msToMidnight / (1000 * 60 * 60);
            } else {
                // Partial day - calculate hours from current time to end time
                const msElapsed = end.getTime() - current.getTime();
                businessHours += msElapsed / (1000 * 60 * 60);
            }
        }
        current.setDate(current.getDate() + 1);
        current.setHours(0, 0, 0, 0); // Set to midnight for next iteration
    }
    
    return businessHours;
};

/**
 * Calculate remaining business days and hours (excluding weekends)
 */
const calculateRemainingBusinessTime = (remainingHours: number): { days: number; hours: number } => {
    let remainingBusinessHours = remainingHours;
    let businessDays = 0;
    const now = new Date();
    let current = new Date(now);
    
    // Count how many business hours we can fit in the calendar
    while (remainingBusinessHours > 0) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            if (remainingBusinessHours >= 24) {
                remainingBusinessHours -= 24;
                businessDays += 1;
            } else {
                // Remaining hours are less than a full day
                break;
            }
        }
        current.setDate(current.getDate() + 1);
    }
    
    return { days: businessDays, hours: Math.round(remainingBusinessHours) };
};

/**
 * Calculate elapsed time excluding periods when document was with office or municipality
 */
const calculateElapsedTimeExcluding = (document: Document, startDate: Date, endDate: Date): number => {
    if (!document.transactions || document.transactions.length === 0) {
        return calculateBusinessHoursElapsed(startDate, endDate);
    }
    
    // Find all office/municipality forward transactions and their corresponding receive/return
    const excludePeriods: Array<{start: Date; end: Date}> = [];
    
    for (let i = 0; i < document.transactions.length; i++) {
        const transaction = document.transactions[i];
        
        // Check if this is a forward to office or municipality
        if ((transaction.forwarded_to_office_id || transaction.forwarded_to_municipality_id) &&
            transaction.action.toLowerCase().includes('forward')) {
            
            const forwardStart = new Date(transaction.created_at);
            
            // Find the corresponding received transaction or next user-to-user forward
            let forwardEnd = endDate; // Default to end date
            for (let j = i - 1; j >= 0; j--) {
                const nextTransaction = document.transactions[j];
                const isReceived = nextTransaction.action.toLowerCase().includes('received');
                const isUserForward = nextTransaction.forwarded_to_user_id && 
                                    !nextTransaction.forwarded_to_office_id && 
                                    !nextTransaction.forwarded_to_municipality_id &&
                                    nextTransaction.action.toLowerCase().includes('forward');
                
                if (isReceived || isUserForward) {
                    forwardEnd = new Date(nextTransaction.created_at);
                    break;
                }
            }
            
            // Only exclude if the period is within our time range
            if (forwardStart < endDate && forwardEnd > startDate) {
                excludePeriods.push({
                    start: new Date(Math.max(forwardStart.getTime(), startDate.getTime())),
                    end: new Date(Math.min(forwardEnd.getTime(), endDate.getTime()))
                });
            }
        }
    }
    
    // Calculate total elapsed time
    let totalElapsed = calculateBusinessHoursElapsed(startDate, endDate);
    
    // Subtract excluded periods
    for (const period of excludePeriods) {
        const excludedHours = calculateBusinessHoursElapsed(period.start, period.end);
        totalElapsed -= excludedHours;
    }
    
    return Math.max(0, totalElapsed);
};

/**
 * Calculate time left for processing and determine color
 */
const getTimeLeftInfo = (document: Document): { daysLeft: number; hoursLeft: number; isLapsed: boolean; percentage: number } => {
    if (!document.transactions || document.transactions.length === 0) {
        return { daysLeft: 0, hoursLeft: 0, isLapsed: false, percentage: 0 };
    }
    
    // Get limit based on document type (in business days)
    const limit = getProcessingTimeLimit(document.document_type);
    
    // Get the creation timestamp from the first transaction (has the actual time)
    // Find the creation transaction (typically the oldest/last in the array)
    const creationTransaction = document.transactions[document.transactions.length - 1];
    const createdDate = new Date(creationTransaction.created_at);
    
    // If document is ended (finalized), freeze the time at the moment of ending
    let now = currentTime.value; // Use currentTime for real-time updates
    if (document.status === 'ended' || document.status === 'finalized') {
        // Use the last transaction date as the reference point (when it was ended)
        const lastTransaction = document.transactions[0];
        now = new Date(lastTransaction.created_at);
    }
    
    // Calculate remaining business days in hours
    const totalHoursLimit = limit * 24; // Convert business days to hours
    
    // Calculate business hours elapsed (only counting weekdays), excluding office/municipality forwarding time
    const hoursElapsed = calculateElapsedTimeExcluding(document, createdDate, now);
    
    // Calculate total hours left
    const totalHoursLeft = Math.max(0, totalHoursLimit - hoursElapsed);
    
    // Calculate remaining business days and hours (excluding weekends)
    const { days: daysLeft, hours: hoursLeft } = calculateRemainingBusinessTime(totalHoursLeft);
    
    const isLapsed = hoursElapsed > totalHoursLimit;
    const percentage = (hoursElapsed / totalHoursLimit) * 100;
    
    return { daysLeft, hoursLeft, isLapsed, percentage };
};

/**
 * Get the text to display for time left
 */
const getTimeLeftText = (document: Document): string => {
    const { daysLeft, hoursLeft, isLapsed } = getTimeLeftInfo(document);
    
    if (isLapsed) {
        return `Overdue`;
    }
    
    if (daysLeft === 0 && hoursLeft === 0) {
        return `No time left`;
    }
    
    if (daysLeft === 0) {
        return `${hoursLeft} hrs`;
    }
    
    if (hoursLeft === 0) {
        return `${daysLeft} days`;
    }
    
    return `${daysLeft} days ${hoursLeft} hrs`;
};

/**
 * Get the style classes for time left cell based on remaining time
 */
const getTimeLeftStyles = (document: Document): object => {
    const { daysLeft, isLapsed, percentage } = getTimeLeftInfo(document);
    
    let bgColor = '';
    let textColor = '';
    let borderColor = '';
    
    if (isLapsed) {
        // Red: lapsed
        bgColor = 'bg-red-100 dark:bg-red-900/30';
        textColor = 'text-red-800 dark:text-red-300';
        borderColor = 'border-red-200 dark:border-red-800';
    } else if (percentage >= 75) {
        // Red: very urgent (75%+ of time used)
        bgColor = 'bg-red-100 dark:bg-red-900/30';
        textColor = 'text-red-800 dark:text-red-300';
        borderColor = 'border-red-200 dark:border-red-800';
    } else if (percentage >= 50) {
        // Orange: urgent (50-75% of time used)
        bgColor = 'bg-orange-100 dark:bg-orange-900/30';
        textColor = 'text-orange-800 dark:text-orange-300';
        borderColor = 'border-orange-200 dark:border-orange-800';
    } else if (percentage >= 25) {
        // Yellow: moderate (25-50% of time used)
        bgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
        textColor = 'text-yellow-800 dark:text-yellow-300';
        borderColor = 'border-yellow-200 dark:border-yellow-800';
    } else {
        // Green: plenty of time (< 25% of time used)
        bgColor = 'bg-green-100 dark:bg-green-900/30';
        textColor = 'text-green-800 dark:text-green-300';
        borderColor = 'border-green-200 dark:border-green-800';
    }
    
    return {
        'inline-flex': true,
        'items-center': true,
        'gap-1': true,
        'px-3': true,
        'py-1': true,
        [bgColor]: true,
        [textColor]: true,
        'rounded-full': true,
        'border': true,
        [borderColor]: true,
        'whitespace-nowrap': true
    };
};

/**
 * Format hours into readable string
 */
const formatHours = (hours: number): string => {
    if (hours < 1) {
        return Math.round(hours * 60) + ' min';
    }
    if (hours < 24) {
        return hours.toFixed(1) + ' hrs';
    }
    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;
    return `${days} day${days > 1 ? 's' : ''} ${remainingHours.toFixed(1)} hrs`;
};

/**
 * Toggle document expansion
 */
const toggleExpanded = (documentId: number) => {
    expandedDocumentId.value = expandedDocumentId.value === documentId ? null : documentId;
};

/**
 * Toggle user expansion
 */
const toggleUserExpanded = (userIdOrIndex: number | { userId: number }) => {
    const userId = typeof userIdOrIndex === 'object' ? userIdOrIndex.userId : userIdOrIndex;
    expandedUserId.value = expandedUserId.value === userId ? null : userId;
};

/**
 * Toggle document type expansion
 */
const toggleDocumentTypeExpanded = (documentType: string) => {
    expandedDocumentType.value = expandedDocumentType.value === documentType ? null : documentType;
};

/**
 * Get documents for a specific document type
 */
const getDocumentsForType = (documentType: string): Document[] => {
    return filteredDocuments.value.filter(doc => {
        return doc.document_type === documentType;
    });
};

/**
 * Generate Budget Proposals Report
 */
const generateReport = async () => {
    reportErrors.value = {};
    
    // Validate form
    if (!reportData.value.reviewedBy) {
        reportErrors.value.submit = 'Please select a Supervisor (Reviewed By)';
        return;
    }
    if (!reportData.value.certifiedCorrect) {
        reportErrors.value.submit = 'Please select an Administrator (Certified Correct)';
        return;
    }
    
    try {
        // Get year from as of date
        const asOfDate = new Date(reportData.value.asOfDate);
        const selectedYear = asOfDate.getFullYear();
        
        // Filter documents for Budget Proposals created in the selected year
        const budgetProposals = documents.value.filter(doc => {
            // Check if document type is Budget Proposal
            if (!doc.document_type.toLowerCase().includes('proposal')) return false;
            
            // Check if document date year matches selected year
            const docDate = new Date(doc.date);
            return docDate.getFullYear() === selectedYear;
        });
        
        // Get created and received transactions for each document
        const reportData_items: Array<{
            dateReceived: string;
            trackingNo: string;
            source: string;
            particulars: string;
        }> = [];
        
        budgetProposals.forEach(doc => {
            if (!doc.transactions || doc.transactions.length === 0) return;
            
            // Find creation transaction only
            const createdTx = doc.transactions[doc.transactions.length - 1];
            
            // Add creation record only
            reportData_items.push({
                dateReceived: formatDateToWords(createdTx.created_at),
                trackingNo: doc.tracking_no,
                source: doc.source || 'N/A',
                particulars: doc.particulars || 'N/A'
            });
        });
        
        // Get signatory names and designations
        const reviewedByUser = users.value.find(u => u.id === reportData.value.reviewedBy);
        const certifiedByUser = users.value.find(u => u.id === reportData.value.certifiedCorrect);
        const reviewedByDesignation = getEmployeeDesignation(reportData.value.reviewedBy);
        const certifiedCorrectDesignation = getEmployeeDesignation(reportData.value.certifiedCorrect);
        
        // Generate HTML report
        const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Budget Proposals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .header h1 { margin: 5px 0; font-size: 16px; font-weight: bold; }
        .as-of { text-center: right; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-around; }
        .signature-box { width: 30%; text-align: center; }
        .signature-title { font-size: 12px; margin-bottom: 50px; text-align: left; }
        .signature-line { border-top: 1px solid #000; margin: 40px 0 5px 0; }
        .signature-name { font-weight: bold; font-size: 12px; margin-top: 5px; }
        .signature-designation { font-size: 11px; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <p style="text-align: center; font-size: 12px; margin: 0;">Republic of the Philippines</p>
        <p style="text-align: center; font-size: 12px; margin: 0; font-weight: bold;">PROVINCIAL GOVERNMENT OF BENGUET</p>
        <p style="text-align: center; font-size: 12px; margin: 0;">La Trinidad, Benguet</p>
        <p style="text-align: center; font-size: 12px; margin: 0; font-weight: bold;">Provincial Budget Office</p>
    </div>
    
    <p style="text-align: center; font-size: 12px; margin-bottom: 0; margin-top: 10px; font-weight: bold;">BUDGET PROPOSALS</p>
    <p style="text-align: center; font-size: 12px; margin-top:0; margin-bottom: 20px;">As of ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Date Received</th>
                <th style="text-align: center;">Tracking No</th>
                <th style="text-align: center;">Source</th>
                <th style="text-align: center;">Particulars</th>
            </tr>
        </thead>
        <tbody>
            ${reportData_items.length > 0 ? reportData_items.map(item => `
            <tr>
                <td>${item.dateReceived}</td>
                <td>${item.trackingNo}</td>
                <td>${item.source}</td>
                <td>${item.particulars}</td>
            </tr>
            `).join('') : '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>'}
        </tbody>
    </table>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Reviewed By:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(reviewedByUser?.name || 'Not Selected')}</div>
            <div class="signature-designation">${reviewedByDesignation}</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-title">Certified Correct:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(certifiedByUser?.name || 'Not Selected')}</div>
            <div class="signature-designation">${certifiedCorrectDesignation}</div>
        </div>
    </div>
</body>
</html>
        `;
        
        // Open print dialog
        const reportWindow = window.open('', 'PRINT_REPORT', 'height=900,width=900');
        if (reportWindow) {
            reportWindow.document.write(htmlContent);
            reportWindow.document.close();
            reportWindow.print();
        }
        
        showReportModal.value = false;
    } catch (e) {
        reportErrors.value.submit = e instanceof Error ? e.message : 'An error occurred';
    }
};

/**
 * Format date and time
 */
const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

/**
 * Format date to words (e.g., February 12, 2026)
 */
const formatDateToWords = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

/**
 * Get employee designation by user ID (match by user name)
 */
const getEmployeeDesignation = (userId: number | null | undefined): string => {
    if (!userId) return 'N/A';
    const user = users.value.find(u => u.id === userId);
    if (!user) return 'N/A';
    const employee = employees.value.find(e => e.name === user.name);
    return employee?.designation || 'N/A';
};

/**
 * Capitalize first letter of each word
 */
const capitalizeWords = (str: string): string => {
    return str.toUpperCase();
};

/**
 * Change page
 */
const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// ============== Lifecycle ==============
onMounted(async () => {
    // Set up real-time updates for Processing Time and Remaining Duration
    timeUpdateInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000); // Update every second
    
    await fetchDocuments();
});

/**
 * onUnmounted: Clean up the time update interval when component is destroyed
 */
onUnmounted(() => {
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
        timeUpdateInterval = null;
    }
});
</script>

<!-- Scoped Styles: Modal animations and transitions -->
<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(10px);
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