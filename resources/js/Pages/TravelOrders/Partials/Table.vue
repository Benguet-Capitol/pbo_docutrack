<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-24">
                <col class="w-20">
                <col class="w-28">
                <col class="w-28">
                <col class="w-32">
                <col class="w-28">
                <col class="w-20">
                <col class="w-20">
            </colgroup>
            <!-- Table Header -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'control_no')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Control No
                            <span v-if="sortBy === 'control_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'date')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Date
                            <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'employees')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Employees
                            <span v-if="sortBy === 'employees'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'going_to')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Going To
                            <span v-if="sortBy === 'going_to'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Inclusive Dates</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Purpose</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Vehicle</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="order in records" :key="order.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">{{ order.control_no }}</td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ formatDateForDisplay(order.date) }}</td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex flex-wrap gap-1">
                            <span v-for="emp in order.employees" :key="emp.id" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                {{ emp.name }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ order.going_to }}</td>
                    <td class="px-4 py-2 text-xs">
                        <div v-if="order.inclusive_dates && order.inclusive_dates.length > 0" class="flex flex-wrap gap-1">
                            <span v-for="(date, idx) in order.inclusive_dates" :key="idx" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                {{ formatInclusiveDateForTable(date) }}
                            </span>
                        </div>
                        <span v-else class="text-gray-600 dark:text-gray-400">
                            <span v-if="order.from_date && order.to_date">
                                <span v-if="order.from_date === order.to_date">
                                    {{ formatDateForDisplay(order.from_date) }}
                                </span>
                                <span v-else>
                                    {{ formatDateForDisplay(order.from_date) }} - {{ formatDateForDisplay(order.to_date) }}
                                </span>
                            </span>
                            <span v-else>-</span>
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex flex-wrap gap-1">
                            <span v-for="p in order.purpose" :key="p" class="inline-block px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs">
                                {{ p }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ order.vehicle }}</td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Edit Button -->
                            <button
                                @click.stop="$emit('edit', order)"
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <!-- Delete Button -->
                            <button
                                @click.stop="$emit('delete', order)"
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
import type { TravelOrder } from '../Composables/useTravelOrdersData';

defineProps<{
    records: TravelOrder[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}>();

defineEmits<{
    edit: [order: TravelOrder];
    delete: [order: TravelOrder];
    sort: [field: string];
}>();

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDateForTable = (dateEntry: string): string => {
    if (!dateEntry) return '';
    
    if (dateEntry.includes(' - ')) {
        const [startStr, endStr] = dateEntry.split(' - ');
        const startDate = new Date(startStr.trim());
        const endDate = new Date(endStr.trim());
        const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return `${start} - ${end}`;
    } else {
        const date = new Date(dateEntry.trim());
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
};
</script>
