<template>
    <!-- Same list style as Documents: gray header, shaded rows, actions pinned on the right -->
    <div class="overflow-x-auto">
        <table :class="LIST_TABLE">
            <colgroup>
                <col class="w-40">
                <col class="w-32">
                <col class="w-32">
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
                <tr v-for="(user, index) in users" :key="user.id" :class="listRowClass(index)">
                    <td :class="LIST_CELL"><span :class="LIST_TITLE">{{ user.name }}</span></td>
                    <td :class="[LIST_CELL, 'text-gray-700 dark:text-gray-300']">{{ user.username }}</td>
                    <td :class="LIST_CELL">
                        <span class="text-gray-600 dark:text-gray-400">{{ user.usertype || '—' }}</span>
                    </td>
                    <td :class="[LIST_CELL, ACTIONS_CELL]">
                        <div class="flex items-center justify-center gap-1.5">
                            <RowIconButton v-if="canManageUsers" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', user)" />
                            <RowIconButton v-if="canManageUsers" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', user)" />
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
import type { User } from '../Composables/useUserData';

defineProps({
    users: {
        type: Array as () => User[],
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
    canManageUsers: {
        type: Boolean,
        required: true,
    },
});

defineEmits<{
    'sort': [field: string];
    'edit': [user: User];
    'delete': [user: User];
}>();

const COLUMNS = [
    { field: 'name', label: 'Name' },
    { field: 'username', label: 'Username' },
    { field: 'usertype', label: 'Role' },
];
</script>
