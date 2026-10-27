<template>
    <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Create Document Button: Calls openCreateModal() to show the create form modal -->
            <button v-if="hasPermission('documents.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                <i class="fas fa-plus"></i>
                Create Document
            </button>
            <div :class="['flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto', !hasPermission('documents.create') && 'sm:ml-auto']">
                    <div class="relative flex-1 sm:flex-none">
                        <i class="fas fa-search text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 text-xs"></i>
                        <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search documents..."
                            class="border border-gray-300 rounded-lg pl-8 pr-4 py-2 text-xs w-full sm:w-80 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                        />
                    </div>

                <!-- Document Type Filter: Filters table rows by document_type -->
                <select
                    v-model="documentTypeFilter"
                    class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer w-full sm:w-auto"
                >
                    <option value="">All Document Types</option>
                    <option value="Annual Budget">Annual Budget</option>
                    <option value="Supplemental Budget">Supplemental Budget</option>
                    <option value="Budget Proposals">Budget Proposals</option>
                    <optgroup label="Referral">
                        <option value="Referral - Simple">Referral - Simple</option>
                        <option value="Referral - Complex">Referral - Complex</option>
                        <option value="Referral - Highly Technical">Referral - Highly Technical</option>
                    </optgroup>
                </select>
                <YearFilter v-model="selectedYear" :years="availableYears" />
                <ViewToggle v-model="viewMode" />
                <!-- Items Per Page Selector: Controls number of items displayed per page -->
                <select
                    v-model.number="itemsPerPage"
                    class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer w-full sm:w-auto"
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
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';
import ViewToggle from '@/Components/ViewToggle.vue';
import YearFilter from '@/Components/YearFilter.vue';

// Create button, search, year and type filters, page size and view switch
const {
    viewMode, hasPermission, searchQuery, selectedYear, availableYears, itemsPerPage, documentTypeFilter,
    openCreateModal,
} = useDocumentsContext();
</script>
