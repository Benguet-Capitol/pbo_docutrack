<template>
    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <colgroup>
                <col class="w-40">
                <col class="w-32">
                <col class="w-32">
                <col class="w-20">
            </colgroup>
            <!-- Table Header -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Name
                            <span v-if="sortBy === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'username')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Username
                            <span v-if="sortBy === 'username'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'usertype')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Role
                            <span v-if="sortBy === 'usertype'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                >
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ user.name }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                        {{ user.username }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ user.usertype }}
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button 
                                v-if="canManageUsers"
                                @click.stop="$emit('edit', user)" 
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button 
                                v-if="canManageUsers"
                                @click.stop="$emit('delete', user)" 
                                class="relative p-2 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-trash-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
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
</script>
