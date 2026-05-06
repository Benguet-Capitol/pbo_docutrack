<template>
    <!-- Documents Processing Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-8">
        <!-- Header Section: Contains search bar and items-per-page selector -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-clipboard-list text-teal-600 dark:text-teal-400 mr-2"></i>
                    Document Tracking Summary
                </h3>
                <div class="flex items-center gap-3">
                    <i class="fas fa-search text-gray-400"></i>
                    <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                    <input
                        v-model="searchQueryLocal"
                        type="text"
                        placeholder="Search documents..."
                        class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                    />
                    <!-- Items Per Page Selector: Controls number of items displayed per page -->
                    <select
                        :value="itemsPerPageLocal"
                        @change="(e) => emit('update:itemsPerPage', Number((e.target as HTMLSelectElement).value))"
                        class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                        <option :value="999999">All</option>
                    </select>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 items-end justify-between">
                <!-- Left side: Filters and Reset -->
                <div class="flex flex-col sm:flex-row gap-3 items-end">
                    <!-- Year Filter -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Year</label>
                        <select
                            :value="String(selectedYearLocal === null ? '' : selectedYearLocal)"
                            @change="(e) => { const val = (e.target as HTMLSelectElement).value === '' ? null : Number((e.target as HTMLSelectElement).value); emit('update:selectedYear', val); }"
                            class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                        >
                            <option value="">All Years</option>
                            <option v-for="year in availableYears" :key="year" :value="String(year)">
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <!-- Semester Filter -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Semester</label>
                        <select
                            :value="String(selectedSemesterLocal === null ? '' : selectedSemesterLocal)"
                            @change="(e) => { const val = (e.target as HTMLSelectElement).value === '' ? null : Number((e.target as HTMLSelectElement).value); emit('update:selectedSemester', val); }"
                            class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                        >
                            <option value="">All Semesters</option>
                            <option value="1">1st Semester (Jan - Jun)</option>
                            <option value="2">2nd Semester (Jul - Dec)</option>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">User</label>
                        <select
                            :value="selectedUserLocal === null ? '' : selectedUserLocal"
                            @change="(e) => { const val = (e.target as HTMLSelectElement).value === '' ? null : Number((e.target as HTMLSelectElement).value); emit('update:selectedUser', val); }"
                            class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                        >
                            <option value="">All Users</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <button
                        @click="resetFilters"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-xs font-medium"
                    >
                        <i class="fas fa-times"></i>
                        Reset
                    </button>
                </div>

                <!-- Right side: Report Buttons -->
                <div class="flex gap-2 flex-col sm:flex-row">
                    <button
                        @click="$emit('showReportModal')"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors text-xs font-medium"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Documents Summary Report
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
                            <button @click="$emit('sort', {sortBy: 'tracking_no', sortOrder: sortOrderLocal === 'asc' ? 'desc' : 'asc'})" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                Tracking No
                                <span v-if="sortByLocal === 'tracking_no'" class="text-xs">{{ sortOrderLocal === 'asc' ? 'Γû▓' : 'Γû╝' }}</span>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                            <button @click="$emit('sort', {sortBy: 'date', sortOrder: sortOrderLocal === 'asc' ? 'desc' : 'asc'})" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                Date
                                <span v-if="sortByLocal === 'date'" class="text-xs">{{ sortOrderLocal === 'asc' ? 'Γû▓' : 'Γû╝' }}</span>
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
                            <tr @click="$emit('toggleExpanded', document.id)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentIdLocal === document.id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
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
                                        @click.stop="$emit('toggleExpanded', document.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                    >
                                        <i :class="['fas', expandedDocumentIdLocal === document.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                        {{ document.transactions?.length || 0 }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Expanded Transactions Row -->
                            <tr v-if="expandedDocumentIdLocal === document.id" :key="`transactions-${document.id}`" class="bg-gray-50 dark:bg-gray-700/50">
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
                                                            {{ formatDuration(transaction.duration_hours) }}
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
                Showing <span class="font-semibold">{{ (currentPageLocal - 1) * itemsPerPageLocal + 1 }}-{{ Math.min(currentPageLocal * itemsPerPageLocal, filteredDocuments.length) }}</span> of <span class="font-semibold">{{ filteredDocuments.length }}</span> documents
            </div>
            <!-- Pagination Buttons -->
            <div class="flex items-center gap-1">
                <!-- First Page Button -->
                <button
                    @click="changePage(1)"
                    :disabled="currentPageLocal === 1"
                    class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-chevron-left"></i>
                </button>
                <!-- Previous Page Button -->
                <button
                    @click="changePage(currentPageLocal - 1)"
                    :disabled="currentPageLocal === 1"
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
                            currentPageLocal === page
                                ? 'bg-emerald-600 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
                <!-- Next Page Button -->
                <button
                    @click="changePage(currentPageLocal + 1)"
                    :disabled="currentPageLocal === totalPages"
                    class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
                <!-- Last Page Button -->
                <button
                    @click="changePage(totalPages)"
                    :disabled="currentPageLocal === totalPages"
                    class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

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

const props = defineProps({
    documents: Array<Document>,
    searchQuery: String,
    sortBy: String,
    sortOrder: String,
    itemsPerPage: Number,
    currentPage: Number,
    expandedDocumentId: Number,
    selectedYear: [Number, null],
    selectedSemester: [Number, null],
    selectedUser: [Number, null],
    filteredDocuments: Array<Document>,
    paginatedDocuments: Array<Document>,
    totalPages: Number,
    availableYears: Array<number>,
    availableUsers: Array<{id: number; name: string}>,
    loading: Boolean,
    error: String,
    // Methods
    calculateProcessingTime: Function,
    getTimeLeftText: Function,
    getTimeLeftStyles: Function,
    getCustodianName: Function,
    getActionType: Function,
    formatDuration: Function,
    matchesYearAndSemester: Function,
});

const emit = defineEmits(['update:searchQuery', 'update:itemsPerPage', 'update:currentPage', 'update:selectedYear', 'update:selectedSemester', 'update:selectedUser', 'sort', 'toggleExpanded', 'showReportModal']);

const searchQueryLocal = computed({
    get: () => props.searchQuery,
    set: (val) => emit('update:searchQuery', val),
});

const itemsPerPageLocal = computed({
    get: () => props.itemsPerPage,
    set: (val) => emit('update:itemsPerPage', val),
});

const currentPageLocal = computed({
    get: () => props.currentPage,
    set: (val) => emit('update:currentPage', val),
});

const selectedYearLocal = computed({
    get: () => props.selectedYear,
    set: (val) => emit('update:selectedYear', val),
});

const selectedSemesterLocal = computed({
    get: () => props.selectedSemester,
    set: (val) => emit('update:selectedSemester', val),
});

const selectedUserLocal = computed({
    get: () => props.selectedUser,
    set: (val) => emit('update:selectedUser', val),
});

const sortByLocal = computed({
    get: () => props.sortBy,
    set: (val) => emit('sort', {sortBy: val, sortOrder: props.sortOrder}),
});

const sortOrderLocal = computed({
    get: () => props.sortOrder,
    set: (val) => emit('sort', {sortBy: props.sortBy, sortOrder: val}),
});

const expandedDocumentIdLocal = computed({
    get: () => props.expandedDocumentId,
    set: (val) => emit('toggleExpanded', val),
});

const resetFilters = () => {
    emit('update:selectedYear', null);
    emit('update:selectedSemester', null);
    emit('update:selectedUser', null);
};

const changePage = (page: number) => {
    if (page >= 1 && page <= props.totalPages) {
        emit('update:currentPage', page);
    }
};
</script>
