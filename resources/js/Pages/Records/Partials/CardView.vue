<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="record in records"
            :key="record.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ record.record_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <i class="far fa-calendar text-[10px]"></i> {{ new Date(record.created_at).toLocaleDateString() }}
                    </span>
                </div>
                <div class="mt-2 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 flex flex-col items-center justify-center flex-shrink-0">
                        <i class="fas fa-file-alt"></i>
                        <span v-if="record.file_size" class="text-[8px] font-bold uppercase leading-none mt-0.5">{{ record.file_extension || 'FILE' }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-gray-100 break-words line-clamp-2" :title="record.title">{{ record.title }}</p>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ record.record_subtype || '—' }}
                            <template v-if="record.file_size"> · {{ formatFileSize(record.file_size) }}</template>
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="record.remarks" class="mx-4 mt-3 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Remarks</div>
                <div class="text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="record.remarks">{{ record.remarks }}</div>
            </div>

            <div class="flex-1"></div>
            <div v-if="record.image_path || canEdit || canDelete" class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton v-if="record.image_path" color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('view', record)" />
                <RowIconButton v-if="record.image_path" color="emerald" icon="fas fa-download" tooltip="Download" @click.stop="$emit('download', record)" />
                <RowIconButton v-if="canEdit" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                <RowIconButton v-if="canDelete" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
            </div>
            <div v-else class="mt-3"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import RowIconButton from '@/Components/RowIconButton.vue';
import type { Record } from '../Composables/useRecordsData';

defineProps<{
    records: Record[];
}>();

defineEmits<{
    'view': [record: Record];
    'download': [record: Record];
    'edit': [record: Record];
    'delete': [record: Record];
}>();

const page = usePage();

const hasPermission = (permission: string): boolean => {
    const permissions = (page.props.auth as any)?.permissions || [];
    return permissions.includes(permission);
};

const canEdit = computed(() => hasPermission('records.edit'));
const canDelete = computed(() => hasPermission('records.delete'));

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};
</script>
