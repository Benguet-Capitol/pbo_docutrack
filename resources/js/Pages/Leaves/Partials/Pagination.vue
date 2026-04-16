<template>
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredCount) }} of {{ filteredCount }} results
        </p>
        <div class="flex gap-1">
            <button
                @click="$emit('page-change', Math.max(1, currentPage - 1))"
                :disabled="currentPage === 1"
                class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
            >
                Previous
            </button>
            <button
                v-for="page in paginationRange"
                :key="page"
                @click="page !== '...' && $emit('page-change', page as number)"
                :disabled="page === '...' || currentPage === page"
                :class="[
                    'px-2 py-1 text-xs rounded transition-colors',
                    page === '...'
                        ? 'text-gray-400 dark:text-gray-500 cursor-default'
                        : currentPage === page
                        ? 'bg-emerald-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
            >
                {{ page }}
            </button>
            <button
                @click="$emit('page-change', Math.min(totalPages, currentPage + 1))"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    currentPage: number;
    totalPages: number;
    itemsPerPage: number;
    filteredCount: number;
    paginationRange: (number | string)[];
}>();

defineEmits<{
    'page-change': [page: number];
}>();
</script>
