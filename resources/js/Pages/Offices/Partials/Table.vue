<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-40">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-10">
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
                <tr v-for="(office, index) in offices" :key="office.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ office.office_name }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ office.office_abbreviation || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ office.fund || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300 font-mono']">{{ office.fpp_code || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300 font-mono']">{{ office.responsibility_code || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ office.branch || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton v-if="canEditOffices" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', office)" />
                            <RowIconButton v-if="canDeleteOffices" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', office)" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import { ACTIONS_CELL, ACTIONS_HEAD, LIST_CELL, LIST_HEAD, LIST_SORT, LIST_TABLE, LIST_TITLE, listRowClass } from '@/utils/listTable';
import type { Office } from '../Composables/useOfficeData';

defineProps({
    offices: {
        type: Array as () => Office[],
        required: true,
    },
    sortBy: {
        type: String,
        required: true,
    },
    sortOrder: {
        type: String as () => 'asc' | 'desc',
        required: true,
    },
    canEditOffices: {
        type: Boolean,
        required: true,
    },
    canDeleteOffices: {
        type: Boolean,
        required: true,
    },
});

defineEmits<{
    'sort': [field: string];
    'edit': [office: Office];
    'delete': [office: Office];
}>();

const COLUMNS = [
    { field: 'office_name', label: 'Office Name' },
    { field: 'office_abbreviation', label: 'Abbreviation' },
    { field: 'fund', label: 'Fund' },
    { field: 'fpp_code', label: 'FPP Code' },
    { field: 'responsibility_code', label: 'Responsibility Code' },
    { field: 'branch', label: 'Branch' },
];
</script>
