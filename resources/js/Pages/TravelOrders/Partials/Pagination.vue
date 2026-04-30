<template>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredCount) }} of {{ filteredCount }} results
        </p>
        <div class="flex items-center gap-1">
            <button
                @click="$emit('page-change', 1)"
                :disabled="currentPage === 1"
                class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fas fa-chevron-left"></i>
            </button>
            <button
                @click="$emit('page-change', currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Prev
            </button>
            <div class="flex gap-0.5">
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
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                    ]"
                >
                    {{ page }}
                </button>
            </div>
            <button
                @click="$emit('page-change', currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Next
            </button>
            <button
                @click="$emit('page-change', totalPages)"
                :disabled="currentPage === totalPages"
                class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fas fa-chevron-right"></i>
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