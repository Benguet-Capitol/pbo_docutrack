<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <colgroup>
                <col class="w-24">
                <col class="w-28">
                <col class="w-32">
                <col class="w-32">
                <col class="w-20">
                <col class="w-48">
                <col class="w-20">
            </colgroup>
            <!-- Table Header -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'id')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Control No
                            <span v-if="sortBy === 'id'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'date_of_filing')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Filing Date
                            <span v-if="sortBy === 'date_of_filing'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'employee_name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Employee
                            <span v-if="sortBy === 'employee_name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'type_of_leave')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Leave Type
                            <span v-if="sortBy === 'type_of_leave'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Days Applied</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Inclusive Dates</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="leave in records" :key="leave.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">{{ leave.control_no }}</td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ formatDateForDisplay(leave.date_of_filing) }}</td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ leave.employee.name }}</td>
                    <td class="px-4 py-2 text-xs">
                        <span class="inline-block px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs">
                            {{ leave.type_of_leave }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                        <div class="flex flex-col gap-1">
                            <span>{{ leave.number_of_working_days_applied_for }}</span>
                            <span v-if="leave.is_half_day" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded text-xs font-medium">
                                {{ leave.half_day_period }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex flex-col gap-1">
                            <span v-for="(date, idx) in leave.inclusive_dates" :key="idx" class="text-gray-600 dark:text-gray-400">
                                {{ formatInclusiveDate(date) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Edit Button -->
                            <button
                                @click.stop="$emit('edit', leave)"
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <!-- Delete Button -->
                            <button
                                @click.stop="$emit('delete', leave)"
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
import type { Leave } from '../Composables/useLeavesData';

defineProps<{
    records: Leave[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}>();

defineEmits<{
    edit: [leave: Leave];
    delete: [leave: Leave];
    sort: [field: string];
}>();

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDate = (entry: string): string => {
    if (!entry) return '';
    if (entry.includes(' - ')) {
        const [startDate, endDate] = entry.split(' - ');
        return `${formatDateForDisplay(startDate.trim())} - ${formatDateForDisplay(endDate.trim())}`;
    } else {
        return formatDateForDisplay(entry);
    }
};
</script>
