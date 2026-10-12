<template>
    <!-- Pagination Controls -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="text-xs text-gray-600 dark:text-gray-400">
            Showing <span class="font-semibold">{{ startItem }}-{{ endItem }}</span> of <span class="font-semibold">{{ totalCount }}</span> items
        </div>
        <div class="flex items-center gap-1">
            <button
                @click="$emit('page-change', currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Prev
            </button>
            <div class="flex gap-0.5">
                <template v-for="page in visiblePages" :key="page">
                    <span v-if="page < 0" class="px-2 py-1 text-xs text-gray-400">…</span>
                    <button
                        v-else
                        @click="$emit('page-change', page)"
                        :class="[
                            'px-2 py-1 text-xs rounded transition-colors',
                            currentPage === page
                                ? 'bg-emerald-600 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ page }}
                    </button>
                </template>
            </div>
            <button
                @click="$emit('page-change', currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    currentPage: number;
    totalPages: number;
    itemsPerPage: number;
    totalCount: number;
}>();

defineEmits<{
    'page-change': [page: number];
}>();

const startItem = computed(() => (props.totalCount === 0 ? 0 : (props.currentPage - 1) * props.itemsPerPage + 1));
const endItem = computed(() => Math.min(props.currentPage * props.itemsPerPage, props.totalCount));

/** Page numbers around the current page; negative numbers are ellipses */
const visiblePages = computed(() => {
    const total = props.totalPages;
    const current = props.currentPage;
    const pages: number[] = [];
    for (let page = 1; page <= total; page++) {
        if (page === 1 || page === total || Math.abs(page - current) <= 2) {
            pages.push(page);
        } else if (pages[pages.length - 1] > 0) {
            pages.push(-page);
        }
    }
    return pages;
});
</script>
