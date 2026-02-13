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

                            <!-- Reset Filters Button -->
                            <div class="flex items-end pt-1">
                                <button
                                    @click="selectedYear = null; selectedSemester = null; selectedUser = null"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-xs font-medium"
                                >
                                    <i class="fas fa-times"></i>
                                    Reset
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
                                        Transactions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template v-if="paginatedDocuments.length > 0">
                                    <template v-for="document in paginatedDocuments" :key="document.id">
                                        <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
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
                                                <span v-else-if="document.status === 'finalized'" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium">Finalized</span>
                                                <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">{{ document.status }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center text-gray-600 dark:text-gray-400 font-medium">
                                                {{ calculateProcessingTime(document) }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center">
                                                <button
                                                    @click="toggleExpanded(document.id)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                                >
                                                    <i :class="['fas', expandedDocumentId === document.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                    {{ document.transactions?.length || 0 }}
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Expanded Transactions Row -->
                                        <tr v-if="expandedDocumentId === document.id" :key="`transactions-${document.id}`" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="9" class="px-6 py-6">
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
                                                                <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Finalized</span>
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
                                    <td :colspan="9" class="px-6 py-8 text-center">
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

            <!-- Users Summary Section: Average time per transaction -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                        Summary of Users' Average time per transaction
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
                                    Average Time
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="filteredUserStatistics.length > 0">
                                <template v-for="(user, userIndex) in filteredUserStatistics" :key="`user-${userIndex}`">
                                    <tr 
                                        @click="toggleUserExpanded(user.userId)"
                                        class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150 cursor-pointer"
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
        </div>
    </AuthenticatedLayout>
</template>


<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
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
const loading = ref(true);
const error = ref('');
const selectedYear = ref<number | null>(new Date().getFullYear());
const selectedSemester = ref<number | null>(null);
const selectedUser = ref<number | null>(null);

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

    // Find the first transaction (should be 'created' action)
    const firstTransaction = document.transactions[document.transactions.length - 1];
    const latestTransaction = document.transactions[0];

    const createdAt = new Date(firstTransaction.created_at);
    const latestAt = new Date(latestTransaction.created_at);

    const diffMs = latestAt.getTime() - createdAt.getTime();
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);

    if (diffDays > 0) {
        return `${diffDays} day${diffDays > 1 ? 's' : ''} ${diffHours % 24} hr${(diffHours % 24) !== 1 ? 's' : ''}`;
    }

    return `${diffHours} hr${diffHours !== 1 ? 's' : ''}`;
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
 * Change page
 */
const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// ============== Lifecycle ==============
onMounted(async () => {
    await fetchDocuments();
});
</script>