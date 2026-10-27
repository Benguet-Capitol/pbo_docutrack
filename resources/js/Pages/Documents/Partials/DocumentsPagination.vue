<template>
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
            <!-- Page Number Buttons: v-for loops through smart pagination pages with ellipsis -->
            <div class="flex gap-0.5">
                <button
                    v-for="page in paginationPages"
                    :key="page"
                    @click="page !== '...' && changePage(page as number)"
                    :disabled="page === '...' || currentPage === page"
                    :class="[
                        'px-2 py-1 text-xs rounded transition-colors',
                        page === '...'
                            ? 'text-gray-400 dark:text-gray-500 cursor-default'
                            : currentPage === page
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
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';

// Page range, total and page buttons
const {
    documents, loading, currentPage, itemsPerPage, filteredDocuments, totalPages, paginationPages,
    changePage,
} = useDocumentsContext();
</script>
