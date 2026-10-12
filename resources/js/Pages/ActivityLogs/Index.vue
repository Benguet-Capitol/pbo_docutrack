<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Activity Logs
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: filters -->
                <Header
                    :filters="filters"
                    :options="filterOptions"
                    :items-per-page="itemsPerPage"
                    :loading="loading"
                    @update:filters="filters = $event"
                    @update:items-per-page="itemsPerPage = $event"
                    @reset="dataComposable.resetFilters()"
                    @refresh="dataComposable.refresh()"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading && logs.length === 0" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="logs.length === 0" :filtered="hasFilters" />

                <!-- Data Table -->
                <Table
                    v-else
                    :class="{ 'opacity-60 pointer-events-none': loading }"
                    :logs="logs"
                    @view="selectedLog = $event"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="totalCount > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="totalCount"
                    @page-change="dataComposable.changePage($event)"
                />
            </div>
        </div>

        <DetailsModal :log="selectedLog" @close="selectedLog = null" />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import Pagination from './Partials/Pagination.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import DetailsModal from './Partials/DetailsModal.vue';
import { computed, ref } from 'vue';
import { useActivityLogData } from './Composables/useActivityLogData';
import type { ActivityLog } from './Composables/useActivityLogData';

// ============== Composables ==============

const dataComposable = useActivityLogData();

// ============== Expose State ==============

const logs = dataComposable.logs;
const loading = dataComposable.loading;
const error = dataComposable.error;
const filters = dataComposable.filters;
const filterOptions = dataComposable.filterOptions;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const totalPages = dataComposable.totalPages;
const totalCount = dataComposable.totalCount;

const hasFilters = computed(() => Object.values(filters.value).some((value) => value));

const selectedLog = ref<ActivityLog | null>(null);
</script>
