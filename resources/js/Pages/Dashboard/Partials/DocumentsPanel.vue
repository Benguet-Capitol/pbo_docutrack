<template>
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
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search documents..."
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

            <!-- Filter Section -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 items-end justify-between">
                <!-- Left side: Filters and Reset -->
                <div class="flex flex-col sm:flex-row gap-3 items-end">
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

                    <!-- Reset Button -->
                    <button
                        @click="selectedYear = null; selectedSemester = null; selectedUser = null"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-xs font-medium"
                    >
                        <i class="fas fa-times"></i>
                        Reset
                    </button>
                </div>

                <!-- Right side: Report Buttons -->
                <div class="flex gap-2 flex-col sm:flex-row">
                    <button
                        @click="$emit('show-report-modal')"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors text-xs font-medium"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Documents Summary Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Documents Table -->
        <DocumentsTable
            :documents="paginatedDocuments"
            :sort-by="sortBy"
            :sort-order="sortOrder"
            :expanded-document-id="expandedDocumentId"
            :loading="loading"
            :error="error"
            :calculate-processing-time="calculateProcessingTime"
            :get-time-left-text="getTimeLeftText"
            :get-time-left-styles="getTimeLeftStyles"
            :get-custodian-name="getCustodianName"
            :toggle-expanded="toggleExpanded"
            :format-duration="formatDuration"
            :get-action-type="getActionType"
            @update:sort-by="sortBy = $event"
            @update:sort-order="sortOrder = $event"
        />

        <!-- Pagination Controls -->
        <div v-if="!loading && documents.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Pagination Info -->
            <div class="text-xs text-gray-600 dark:text-gray-400">
                Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredDocuments.length) }}</span> of <span class="font-semibold">{{ filteredDocuments.length }}</span> documents
            </div>
            <!-- Pagination Buttons -->
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
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import DocumentsTable from './DocumentsTable.vue';

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

const props = defineProps<{
    documents: Document[];
    filteredDocuments: Document[];
    availableYears: number[];
    availableUsers: Array<{id: number; name: string}>;
    selectedYear: number | null;
    selectedSemester: number | null;
    selectedUser: number | null;
    searchQuery: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    itemsPerPage: number;
    currentPage: number;
    expandedDocumentId: number | null;
    loading: boolean;
    error: string;
    offices: any[];
    calculateProcessingTime: (doc: Document) => string;
    getTimeLeftText: (doc: Document) => string;
    getTimeLeftStyles: (doc: Document) => object;
    getCustodianName: (doc: Document) => string;
    toggleExpanded: (id: number) => void;
    formatDuration: (hours: number) => string;
    getActionType: (action: string) => string;
}>();

const emit = defineEmits<{
    'show-report-modal': [];
    'update:selected-year': [value: number | null];
    'update:selected-semester': [value: number | null];
    'update:selected-user': [value: number | null];
    'update:search-query': [value: string];
    'update:sort-by': [value: string];
    'update:sort-order': [value: 'asc' | 'desc'];
    'update:items-per-page': [value: number];
    'update:current-page': [value: number];
}>();

const searchQuery = computed({
    get: () => props.searchQuery,
    set: (val) => emit('update:search-query', val)
});

const sortBy = computed({
    get: () => props.sortBy,
    set: (val) => emit('update:sort-by', val)
});

const sortOrder = computed({
    get: () => props.sortOrder,
    set: (val) => emit('update:sort-order', val)
});

const itemsPerPage = computed({
    get: () => props.itemsPerPage,
    set: (val) => emit('update:items-per-page', val)
});

const selectedYear = computed({
    get: () => props.selectedYear,
    set: (val) => emit('update:selected-year', val)
});

const selectedSemester = computed({
    get: () => props.selectedSemester,
    set: (val) => emit('update:selected-semester', val)
});

const selectedUser = computed({
    get: () => props.selectedUser,
    set: (val) => emit('update:selected-user', val)
});

const paginatedDocuments = computed(() => {
    const start = (props.currentPage - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return props.filteredDocuments.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(props.filteredDocuments.length / itemsPerPage.value);
});

const expandedDocumentId = computed({
    get: () => props.expandedDocumentId,
    set: (val) => {
        // This will be handled by v-model binding through the expand handler
    }
});

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:current-page', page);
    }
};
</script>
