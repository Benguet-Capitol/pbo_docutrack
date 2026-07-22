<template>
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Create Time Slip Button -->
            <button
                v-if="canCreateTimeSlips"
                @click="$emit('create-click')"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200"
            >
                <i class="fas fa-plus"></i>
                Create Time Slip
            </button>

            <div :class="['flex items-center gap-3', !canCreateTimeSlips && 'sm:ml-auto']">
                <i class="fas fa-search text-gray-400"></i>
                <input
                    :value="searchQuery"
                    @input="$emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
                    type="text"
                    placeholder="Search time slips..."
                    class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                />
                <select
                    :value="itemsPerPage"
                    @change="$emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value))"
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
    </div>
</template>

<script setup lang="ts">
defineProps<{
    searchQuery: string;
    itemsPerPage: number;
    canCreateTimeSlips: boolean;
}>();

defineEmits<{
    'create-click': [];
    'update:searchQuery': [value: string];
    'update:itemsPerPage': [value: number];
}>();
</script>
