<template>
    <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Create Municipality Button: Calls openCreateModal() to show the create form modal -->
            <button 
                v-if="canCreateMunicipalities" 
                @click="onCreateClick" 
                class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200"
            >
                <i class="fas fa-plus"></i>
                Create Municipality
            </button>

            <div :class="['flex items-center gap-3', !canCreateMunicipalities && 'sm:ml-auto']">
                <!-- Search Input -->
                <div class="relative flex items-center flex-1 sm:flex-none">
                    <i class="fas fa-search text-gray-400 absolute left-3"></i>
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Search municipalities..."
                        class="border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80 focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"
                    />
                </div>

                <!-- Items Per Page Selector: Controls number of items displayed per page -->
                <select
                    :value="itemsPerPage"
                    @change="$emit('update:itemsPerPage', $event.target.value)"
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
defineProps({
    searchQuery: {
        type: String,
        required: true,
    },
    itemsPerPage: {
        type: [String, Number],
        required: true,
    },
    canCreateMunicipalities: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits<{
    'update:searchQuery': [value: string];
    'update:itemsPerPage': [value: string];
    'create-click': [];
}>();

const onCreateClick = () => {
    emit('create-click');
};
</script>
