<template>
    <!-- Data Table: Shows municipalities data in table format -->
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-40">
                <col class="w-40">
                <col class="w-40">
                <col class="w-40">
                <col class="w-20">
            </colgroup>
            <!-- Table Header: Contains sortable column headers -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <!-- Municipality Name Header: Sortable, calls toggleSort('name') when clicked -->
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Municipality Name
                            <span v-if="sortBy === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <!-- Code Header: Sortable -->
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'code')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Code
                            <span v-if="sortBy === 'code'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <!-- Municipal Budget Officer Header: Sortable -->
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'municipal_budget_officer')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Budget Officer
                            <span v-if="sortBy === 'municipal_budget_officer'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <!-- Municipality Class Header: Sortable -->
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'city_class')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Municipality Class
                            <span v-if="sortBy === 'city_class'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body: Renders rows for each municipality -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="municipality in municipalities"
                    :key="municipality.id"
                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                >
                    <!-- Municipality Name Column -->
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                        {{ municipality.name }}
                    </td>
                    <!-- Code Column -->
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-mono">
                        <span v-if="municipality.code">{{ municipality.code }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <!-- Municipal Budget Officer Column -->
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="municipality.municipal_budget_officer">{{ municipality.municipal_budget_officer }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <!-- Municipality Class Column -->
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="municipality.city_class">{{ municipality.city_class }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                    </td>
                    <!-- Actions Column -->
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Edit Button -->
                            <button 
                                v-if="canEditMunicipalities"
                                @click.stop="$emit('edit', municipality)" 
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <!-- Delete Button -->
                            <button 
                                v-if="canDeleteMunicipalities"
                                @click.stop="$emit('delete', municipality)" 
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
</script>
