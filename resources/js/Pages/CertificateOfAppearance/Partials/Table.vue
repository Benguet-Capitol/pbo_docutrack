<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-24">
                <col class="w-24">
                <col class="w-28">
                <col class="w-28">
                <col class="w-40">
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
                <tr v-for="(certificate, index) in certificates" :key="certificate.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ certificate.control_no }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-600 dark:text-gray-400']">{{ listDate(certificate.date) }}</td>
                    <td :class="LIST_CELL">
                        <span :class="[LIST_TAG, 'rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300']">
                            {{ certificate.name }}
                        </span>
                    </td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300 break-words']">{{ certificate.office || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-800 dark:text-gray-200 break-words']" style="white-space: pre-wrap;">{{ certificate.purpose || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton color="purple" icon="fas fa-eye" tooltip="Preview" @click.stop="$emit('preview', certificate)" />
                            <RowIconButton v-if="can('certificate_of_appearances.edit')" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', certificate)" />
                            <RowIconButton v-if="can('certificate_of_appearances.delete')" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', certificate)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TAG, LIST_TITLE, listDate, listRowClass } from '@/utils/listTable';
import type { CertificateOfAppearance } from '../Composables/useCoaData';

type SortField = 'control_no' | 'name' | 'office' | 'purpose' | 'date';

const { can } = usePermissions();

defineProps<{
    certificates: CertificateOfAppearance[];
    sortBy?: SortField;
    sortOrder?: 'asc' | 'desc';
}>();

defineEmits<{
    edit: [certificate: CertificateOfAppearance];
    delete: [certificate: CertificateOfAppearance];
    preview: [certificate: CertificateOfAppearance];
    sort: [field: SortField];
}>();

const COLUMNS: { field: SortField; label: string }[] = [
    { field: 'control_no', label: 'Control No' },
    { field: 'date', label: 'Date' },
    { field: 'name', label: 'Name' },
    { field: 'office', label: 'Office' },
    { field: 'purpose', label: 'Purpose' },
];
</script>
