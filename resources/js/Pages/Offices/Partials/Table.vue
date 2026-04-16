<template>
    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-40">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-10">
            </colgroup>
            <!-- Table Header: Contains sortable column headers -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'office_name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Office Name
                            <span v-if="sortBy === 'office_name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'office_abbreviation')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Abbreviation
                            <span v-if="sortBy === 'office_abbreviation'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'fund')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Fund
                            <span v-if="sortBy === 'fund'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'fpp_code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            FPP Code
                            <span v-if="sortBy === 'fpp_code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'responsibility_code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Responsibility Code
                            <span v-if="sortBy === 'responsibility_code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'branch')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Branch
                            <span v-if="sortBy === 'branch'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="office in offices"
                    :key="office.id"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                >
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ office.office_name }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                        {{ office.office_abbreviation }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="office.fund">{{ office.fund }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                        <span v-if="office.fpp_code">{{ office.fpp_code }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                        <span v-if="office.responsibility_code">{{ office.responsibility_code }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="office.branch">{{ office.branch }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button 
                                v-if="canEditOffices"
                                @click.stop="$emit('edit', office)" 
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button 
                                v-if="canDeleteOffices"
                                @click.stop="$emit('delete', office)" 
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
</script>
