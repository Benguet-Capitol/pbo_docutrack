<template>
    <!-- Data Table: municipalities (same list style as Documents: gray header, shaded rows, actions pinned on the right) -->
    <div class="overflow-x-auto">
        <table :class="[LIST_TABLE, 'table-fixed']">
            <colgroup>
                <col class="w-40">
                <col class="w-40">
                <col class="w-40">
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
                <tr v-for="(municipality, index) in municipalities" :key="municipality.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ municipality.name }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300 font-mono']">{{ municipality.code || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ municipality.municipal_budget_officer || '—' }}</td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ municipality.city_class || '—' }}</td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton v-if="canEditMunicipalities" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', municipality)" />
                            <RowIconButton v-if="canDeleteMunicipalities" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', municipality)" />
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
import type { Municipality } from '../Composables/useMunicipalityData';

defineProps({
    municipalities: {
        type: Array as () => Municipality[],
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
    canEditMunicipalities: {
        type: Boolean,
        required: true,
    },
    canDeleteMunicipalities: {
        type: Boolean,
        required: true,
    },
});

defineEmits<{
    'sort': [field: string];
    'edit': [municipality: Municipality];
    'delete': [municipality: Municipality];
}>();

const COLUMNS = [
    { field: 'name', label: 'Municipality Name' },
    { field: 'code', label: 'Code' },
    { field: 'municipal_budget_officer', label: 'Budget Officer' },
    { field: 'city_class', label: 'Municipality Class' },
];
</script>
