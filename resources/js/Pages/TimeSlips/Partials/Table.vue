<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-20">
                <col class="w-20">
                <col class="w-24">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-20">
                <col class="w-40">
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
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Employee</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">In AM</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Out AM</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">In PM</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Out PM</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'reason')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Reason
                            <span v-if="sortBy === 'reason'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
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
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-gray-100">
                        <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                            {{ slip.requesting_employee?.name || 'N/A' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="slip.time_in_am" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                            {{ formatTime(slip.time_in_am) }}
                        </span>
                        <span v-else>—</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="slip.time_out_am" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                            {{ formatTime(slip.time_out_am) }}
                        </span>
                        <span v-else>—</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="slip.time_in_pm" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                            {{ formatTime(slip.time_in_pm) }}
                        </span>
                        <span v-else>—</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="slip.time_out_pm" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                            {{ formatTime(slip.time_out_pm) }}
                        </span>
                        <span v-else>—</span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="block">{{ slip.reason }}</span>
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
                                v-if="canEditTimeSlips"
                                @click.stop="$emit('edit', slip)"
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button
                                v-if="canDeleteTimeSlips"
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
import type { TimeSlip } from '../Composables/useTimeSlipsData';

defineProps<{
    records: TimeSlip[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    canEditTimeSlips: boolean;
    canDeleteTimeSlips: boolean;
}>();

defineEmits<{
    edit: [slip: TimeSlip];
    delete: [slip: TimeSlip];
    preview: [slip: TimeSlip];
    sort: [field: string];
}>();

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    
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
</script>
