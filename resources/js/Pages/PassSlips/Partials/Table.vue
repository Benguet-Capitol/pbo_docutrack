<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-20">
                <col class="w-16">
                <col class="w-28">
                <col class="w-24">
                <col class="w-20">
                <col class="w-40">
                <col class="w-40">
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
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Employees</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Inclusive Dates</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'requested_time')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Leave Time
                            <span v-if="sortBy === 'requested_time'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'purpose')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Purpose
                            <span v-if="sortBy === 'purpose'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'location')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Location
                            <span v-if="sortBy === 'location'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Return Time</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody>
                <tr v-for="slip in records" :key="slip.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-gray-100 font-semibold">
                        {{ slip.control_no }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatDate(slip.date) }}
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex gap-1 flex-wrap">
                            <span v-for="emp in slip.employees" :key="emp.id" class="inline-block px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-semibold">
                                {{ emp.name }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div v-if="slip.inclusive_dates && slip.inclusive_dates.length > 0" class="flex flex-wrap gap-1">
                            <span v-for="(date, idx) in slip.inclusive_dates" :key="idx" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-semibold">
                                {{ formatInclusiveDate(date) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatTime(slip.requested_time) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ slip.purpose }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ slip.location }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatTime(slip.expected_return_time) }}
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click.stop="$emit('preview', slip)"
                                class="relative p-2 text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-eye"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">View</span>
                            </button>
                            <button
                                @click.stop="$emit('edit', slip)"
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button
                                @click.stop="$emit('delete', slip)"
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
import type { PassSlip } from '../Composables/usePassSlipsData';

defineProps<{
    records: PassSlip[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}>();

defineEmits<{
    edit: [slip: PassSlip];
    delete: [slip: PassSlip];
    preview: [slip: PassSlip];
    sort: [field: string];
}>();

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    
    // If it doesn't contain a colon, it's not a time value (could be ASAP, NWD, NOM, Memo, etc.)
    if (!timeStr.includes(':')) {
        return timeStr;
    }
    
    try {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    } catch {
        return timeStr;
    }
};

const formatInclusiveDate = (dateEntry: string): string => {
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
