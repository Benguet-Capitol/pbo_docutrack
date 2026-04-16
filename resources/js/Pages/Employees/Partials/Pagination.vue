<template>
    <!-- Pagination Controls -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Pagination Info -->
        <div class="text-xs text-gray-600 dark:text-gray-400">
            Showing <span class="font-semibold">{{ startItem }}-{{ endItem }}</span> of <span class="font-semibold">{{ totalCount }}</span> employees
        </div>
        <!-- Pagination Buttons -->
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
                    v-for="page in totalPages"
                    :key="page"
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

const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    totalPages: {
        type: Number,
        required: true,
    },
    itemsPerPage: {
        type: Number,
        required: true,
    },
    totalCount: {
        type: Number,
        required: true,
    },
});

defineEmits<{
    'page-change': [page: number];
}>();

const startItem = computed(() => {
    return (props.currentPage - 1) * props.itemsPerPage + 1;
});

const endItem = computed(() => {
    return Math.min(props.currentPage * props.itemsPerPage, props.totalCount);
});
</script>
