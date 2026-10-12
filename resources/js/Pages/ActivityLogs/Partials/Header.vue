<template>
    <!-- Header Section: filters for the activity log -->
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Search -->
            <div class="relative flex items-center lg:col-span-2">
                <i class="fas fa-search text-gray-400 absolute left-3"></i>
                <input
                    :value="filters.search"
                    @input="update('search', ($event.target as HTMLInputElement).value)"
                    type="text"
                    placeholder="Search description, record or user..."
                    :class="[inputClass, 'pl-10 w-full']"
                />
            </div>

            <!-- Module -->
            <select :value="filters.module" @change="update('module', ($event.target as HTMLSelectElement).value)" :class="inputClass">
                <option value="">All modules</option>
                <option v-for="module in options.modules" :key="module" :value="module">{{ module }}</option>
            </select>

            <!-- Action -->
            <select :value="filters.action" @change="update('action', ($event.target as HTMLSelectElement).value)" :class="inputClass">
                <option value="">All actions</option>
                <option v-for="action in options.actions" :key="action" :value="action">{{ actionMeta(action).label }}</option>
            </select>

            <!-- User -->
            <select :value="filters.user_id" @change="update('user_id', ($event.target as HTMLSelectElement).value)" :class="inputClass">
                <option value="">All users</option>
                <option v-for="user in options.users" :key="user.user_id" :value="String(user.user_id)">{{ user.user_name }}</option>
            </select>

            <!-- Per page -->
            <select
                :value="itemsPerPage"
                @change="$emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value))"
                :class="inputClass"
            >
                <option :value="10">10 per page</option>
                <option :value="25">25 per page</option>
                <option :value="50">50 per page</option>
                <option :value="100">100 per page</option>
            </select>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-3">
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">From</label>
                <input type="date" :value="filters.date_from" @change="update('date_from', ($event.target as HTMLInputElement).value)" :class="inputClass" />
                <label class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">To</label>
                <input type="date" :value="filters.date_to" @change="update('date_to', ($event.target as HTMLInputElement).value)" :class="inputClass" />
            </div>

            <div class="flex items-center gap-2 sm:ml-auto">
                <button
                    v-if="hasFilters"
                    @click="$emit('reset')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <i class="fas fa-times"></i>
                    Clear filters
                </button>
                <button
                    @click="$emit('refresh')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { actionMeta } from '../Composables/useActivityLogData';
import type { ActivityLogFilters, FilterOptions } from '../Composables/useActivityLogData';

const props = defineProps<{
    filters: ActivityLogFilters;
    options: FilterOptions;
    itemsPerPage: number;
    loading: boolean;
}>();

const emit = defineEmits<{
    'update:filters': [value: ActivityLogFilters];
    'update:itemsPerPage': [value: number];
    'reset': [];
    'refresh': [];
}>();

const inputClass =
    'border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white dark:placeholder-gray-400 focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors';

const hasFilters = computed(() => Object.values(props.filters).some((value) => value));

const update = (key: keyof ActivityLogFilters, value: string) => {
    emit('update:filters', { ...props.filters, [key]: value });
};
</script>
