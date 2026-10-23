<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-20">
                <col class="w-20">
                <col class="w-32">
                <col class="w-32">
                <col class="w-32">
                <col class="w-20">
                <col class="w-20">
            </colgroup>
            <thead>
                <tr>
                    <th v-for="column in COLUMNS" :key="column.field" :class="LIST_HEAD">
                        <button @click="$emit('sort', column.field)" :class="LIST_SORT">
                            {{ column.label }}
                            <span v-if="sortBy === column.field">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th :class="[LIST_HEAD, ACTIONS_HEAD]">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(record, index) in records" :key="record.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ record.record_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(record.created_at) }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ record.record_subtype || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']">{{ record.title }}</td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400 break-words']" style="white-space: pre-wrap;">{{ record.remarks || '—' }}</td>
                    <td :class="[LIST_CELL, 'whitespace-nowrap']">
                        <div v-if="record.file_size" class="flex flex-col items-start gap-1 text-[11px]">
                            <span class="inline-block px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded font-medium">
                                {{ record.file_extension || 'FILE' }}
                            </span>
                            <span class="text-gray-600 dark:text-gray-400">{{ formatFileSize(record.file_size) }}</span>
                        </div>
                        <span v-else class="text-gray-400">—</span>
                    </td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton v-if="record.image_path" color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('view', record)" />
                            <RowIconButton v-if="record.image_path" color="emerald" icon="fas fa-download" tooltip="Download" @click.stop="$emit('download', record)" />
                            <RowIconButton v-if="hasPermission('records.edit')" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                            <RowIconButton v-if="hasPermission('records.delete')" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import RowIconButton from '@/Components/RowIconButton.vue';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TITLE, listDate, listRowClass } from '@/utils/listTable';
import type { Record } from '../Composables/useRecordsData';

defineProps<{
    records: Record[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}>();

defineEmits<{
    'sort': [field: string];
    'view': [record: Record];
    'download': [record: Record];
    'edit': [record: Record];
    'delete': [record: Record];
}>();

const COLUMNS = [
    { field: 'record_no', label: 'Record No' },
    { field: 'created_at', label: 'Date' },
    { field: 'record_subtype', label: 'Subtype' },
    { field: 'title', label: 'Title' },
    { field: 'remarks', label: 'Remarks' },
    { field: 'file_extension', label: 'File Details' },
];

const page = usePage();

const hasPermission = (permission: string): boolean => {
    const permissions = (page.props.auth as any)?.permissions || [];
    return permissions.includes(permission);
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};
</script>
