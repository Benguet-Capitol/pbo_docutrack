<template>
    <!-- Header Section: module filter, search, bulk actions -->
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <!-- Module chips -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button
                @click="$emit('update:moduleFilter', '')"
                :class="chipClass(moduleFilter === '')"
            >
                All
                <span class="ml-1 px-1.5 rounded-full bg-white/30 dark:bg-black/20">{{ totalCount }}</span>
            </button>
            <button
                v-for="module in modules.filter((m) => m.count > 0)"
                :key="module.key"
                @click="$emit('update:moduleFilter', module.key)"
                :class="chipClass(moduleFilter === module.key)"
            >
                {{ module.label }}
                <span class="ml-1 px-1.5 rounded-full bg-white/30 dark:bg-black/20">{{ module.count }}</span>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Bulk actions -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    :disabled="selectedCount === 0"
                    @click="$emit('restore-selected')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-trash-restore"></i>
                    Restore Selected ({{ selectedCount }})
                </button>
                <button
                    :disabled="selectedCount === 0"
                    @click="$emit('delete-selected')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-trash-alt"></i>
                    Delete Permanently ({{ selectedCount }})
                </button>
                <button
                    v-if="selectedCount > 0"
                    @click="$emit('clear-selection')"
                    class="px-3 py-2.5 text-xs text-gray-600 dark:text-gray-400 hover:underline"
                >
                    Clear selection
                </button>
            </div>

            <div class="flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex items-center flex-1 sm:flex-none">
                    <i class="fas fa-search text-gray-400 absolute left-3"></i>
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
                        type="text"
                        placeholder="Search trash..."
                        class="border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-full sm:w-72 focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"
                    />
                </div>
                <ViewToggle :model-value="viewMode" @update:model-value="$emit('update:viewMode', $event)" />

                <!-- Items Per Page Selector -->
                <select
                    :value="itemsPerPage"
                    @change="$emit('update:itemsPerPage', ($event.target as HTMLSelectElement).value)"
                    class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="999999">All</option>
                </select>

                <!-- Refresh -->
                <button
                    @click="$emit('refresh')"
                    class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    title="Refresh"
                >
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ViewToggle from '@/Components/ViewToggle.vue';
import type { ViewMode } from '@/Composables/useResponsiveView';
import type { TrashModule } from '../Composables/useTrashData';

defineProps<{
    viewMode: ViewMode;
    searchQuery: string;
    moduleFilter: string;
    itemsPerPage: number;
    modules: TrashModule[];
    totalCount: number;
    selectedCount: number;
}>();

defineEmits<{
    'update:viewMode': [value: ViewMode];
    'update:searchQuery': [value: string];
    'update:moduleFilter': [value: string];
    'update:itemsPerPage': [value: string];
    'restore-selected': [];
    'delete-selected': [];
    'clear-selection': [];
    'refresh': [];
}>();

const chipClass = (active: boolean) => [
    'inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
    active
        ? 'text-white bg-emerald-600 dark:bg-emerald-700'
        : 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600',
];
</script>
