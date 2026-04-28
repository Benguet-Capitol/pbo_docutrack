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
                <col class="w-24">
                <col class="w-20">
            </colgroup>
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'control_no')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Control No
                            <span v-if="sortBy === 'control_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Date Filed</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Employee</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'type')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Type
                            <span v-if="sortBy === 'type'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'requested_date')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Requested Date
                            <span v-if="sortBy === 'requested_date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Requested Time</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Return Time</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                        <button @click="$emit('sort', 'reason')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            Reason
                            <span v-if="sortBy === 'reason'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                        </button>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="record in records" :key="record.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-gray-100 font-semibold">
                        {{ record.control_no }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatDate(record.date_filed) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        <span class="inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                            {{ record.employee?.name || 'N/A' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            record.type === 'Tardiness' 
                                ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'
                                : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                        ]">
                            {{ record.type }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatDate(record.requested_date) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formatTime(record.requested_time) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ record.return_time ? formatTime(record.return_time) : 'N/A' }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400" :title="record.reason">
                        {{ record.reason }}
                    </td>
                    <td class="px-4 py-2 text-xs text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click.stop="$emit('preview', record)"
                                class="relative p-2 text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-eye"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">View</span>
                            </button>
                            <button 
                                v-if="hasPermission('tardiness.edit')" 
                                @click.stop="$emit('edit', record)" 
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button 
                                v-if="hasPermission('tardiness.delete')" 
                                @click.stop="$emit('delete', record)" 
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
import type { TardinessRecord } from '../Composables/useTardinessData';

defineProps<{
    records: TardinessRecord[];
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}>();

defineEmits<{
    edit: [record: TardinessRecord];
    delete: [record: TardinessRecord];
    preview: [record: TardinessRecord];
    sort: [field: string];
}>();

const hasPermission = (permission: string): boolean => {
    return true; // Placeholder - actual permission checking in layout
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};
</script>
