<template>
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="text-xs text-gray-600 dark:text-gray-400">
            Showing <span class="font-semibold">{{ startRecord }}-{{ endRecord }}</span> of <span class="font-semibold">{{ totalRecords }}</span> records
        </div>
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
import { computed } from 'vue';

interface Props {
    currentPage: number;
    totalPages: number;
    itemsPerPage: number;
    totalRecords: number;
    paginationRange: (number | string)[];
}

const props = defineProps<Props>();

defineEmits<{
    'page-change': [page: number];
}>();

const startRecord = computed(() => {
    return (props.currentPage - 1) * props.itemsPerPage + 1;
});

const endRecord = computed(() => {
    return Math.min(props.currentPage * props.itemsPerPage, props.totalRecords);
});
</script>
