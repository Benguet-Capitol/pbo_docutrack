<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Trash
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <Header
                    :search-query="searchQuery"
                    :module-filter="moduleFilter"
                    :items-per-page="itemsPerPage"
                    :modules="modules"
                    :total-count="totalCount"
                    :selected-count="selectedItems.length"
                    @update:search-query="searchQuery = $event"
                    @update:module-filter="moduleFilter = $event"
                    @update:items-per-page="itemsPerPage = Number($event)"
                    @restore-selected="openRestore(selectedItems)"
                    @delete-selected="openForceDelete(selectedItems)"
                    @clear-selection="dataComposable.clearSelection()"
                    @refresh="dataComposable.fetchItems()"
                />

                <!-- Loading State -->
                <LoadingState v-if="loading && items.length === 0" />

                <!-- Error State -->
                <ErrorState v-else-if="error" :error="error" />

                <!-- Empty State -->
                <EmptyState v-else-if="filteredItems.length === 0" :filtered="items.length > 0" />

                <!-- Data Table -->
                <Table
                    v-else
                    :items="paginatedItems"
                    :selected-keys="selectedKeys"
                    :all-page-selected="allPageSelected"
                    @toggle-select="dataComposable.toggleSelect($event)"
                    @toggle-select-page="dataComposable.toggleSelectPage()"
                    @restore="openRestore([$event])"
                    @delete="openForceDelete([$event])"
                />

                <!-- Pagination -->
                <Pagination
                    v-if="filteredItems.length > 0"
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :items-per-page="itemsPerPage"
                    :total-count="filteredItems.length"
                    @page-change="dataComposable.changePage($event)"
                />
            </div>
        </div>

        <!-- Modals -->
        <RestoreModal
            :show="showRestoreModal"
            :items="actionItems"
            :processing="processing"
            @close="showRestoreModal = false"
            @confirm="handleConfirmRestore"
        />

        <ForceDeleteModal
            :show="showForceDeleteModal"
            :items="actionItems"
            :processing="processing"
            :error="forceDeleteError"
            @close="showForceDeleteModal = false"
            @confirm="handleConfirmForceDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import Header from './Partials/Header.vue';
import Table from './Partials/Table.vue';
import Pagination from './Partials/Pagination.vue';
import LoadingState from './Partials/LoadingState.vue';
import ErrorState from './Partials/ErrorState.vue';
import EmptyState from './Partials/EmptyState.vue';
import RestoreModal from './Partials/RestoreModal.vue';
import ForceDeleteModal from './Partials/ForceDeleteModal.vue';
import { ref } from 'vue';
import { useTrashData } from './Composables/useTrashData';
import type { TrashItem } from './Composables/useTrashData';

// ============== Composables ==============

const dataComposable = useTrashData();

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// ============== Expose State ==============

const items = dataComposable.items;
const modules = dataComposable.modules;
const loading = dataComposable.loading;
const error = dataComposable.error;
const searchQuery = dataComposable.searchQuery;
const moduleFilter = dataComposable.moduleFilter;
const currentPage = dataComposable.currentPage;
const itemsPerPage = dataComposable.itemsPerPage;
const selectedKeys = dataComposable.selectedKeys;
const totalCount = dataComposable.totalCount;
const filteredItems = dataComposable.filteredItems;
const totalPages = dataComposable.totalPages;
const paginatedItems = dataComposable.paginatedItems;
const selectedItems = dataComposable.selectedItems;
const allPageSelected = dataComposable.allPageSelected;

// ============== Modal State ==============

const showRestoreModal = ref(false);
const showForceDeleteModal = ref(false);
const actionItems = ref<TrashItem[]>([]);
const processing = ref(false);
const forceDeleteError = ref<string | null>(null);

const describe = (list: TrashItem[]) =>
    list.length === 1 ? `<strong>${list[0].label}</strong>` : `<strong>${list.length} items</strong>`;

// ============== Event Handlers ==============

const openRestore = (list: TrashItem[]) => {
    if (list.length === 0) return;
    actionItems.value = [...list];
    showRestoreModal.value = true;
};

const openForceDelete = (list: TrashItem[]) => {
    if (list.length === 0) return;
    actionItems.value = [...list];
    forceDeleteError.value = null;
    showForceDeleteModal.value = true;
};

const handleConfirmRestore = async () => {
    processing.value = true;
    try {
        await dataComposable.restoreItems(actionItems.value);
        toastRef.value?.add('success', 'Restored', `${describe(actionItems.value)} has been restored successfully!`, 3000);
        showRestoreModal.value = false;
    } catch (e) {
        toastRef.value?.add('error', 'Error', e instanceof Error ? e.message : 'An error occurred', 4000);
    } finally {
        processing.value = false;
    }
};

const handleConfirmForceDelete = async (password: string) => {
    processing.value = true;
    forceDeleteError.value = null;
    try {
        await dataComposable.forceDeleteItems(actionItems.value, password);
        toastRef.value?.add('error', 'Deleted', `${describe(actionItems.value)} has been permanently deleted.`, 3000);
        showForceDeleteModal.value = false;
    } catch (e) {
        forceDeleteError.value = e instanceof Error ? e.message : 'An error occurred';
    } finally {
        processing.value = false;
    }
};
</script>
