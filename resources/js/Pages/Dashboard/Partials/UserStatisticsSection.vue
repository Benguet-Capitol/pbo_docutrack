<template>
    <!-- Users Summary Section: Average Duration per Transaction -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header Section -->
        <div class="px-4 sm:px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 shrink-0"></i>
                    <span class="min-w-0">Summary of Users Average Time per Transaction</span>
                </h3>
                <ViewToggle v-model="viewMode" />
            </div>
        </div>

        <!-- User Statistics Table -->
        <div v-if="viewMode === 'table'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                            User Name
                        </th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                            Total Transactions
                        </th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                            Average Duration
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-if="filteredUserStatistics.length > 0">
                        <template v-for="(user, userIndex) in filteredUserStatistics" :key="`user-${userIndex}`">
                            <tr 
                                @click="$emit('expandUser', user.userId)"
                                :class="['transition-colors duration-150 cursor-pointer', expandedUserIdLocal === user.userId ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <i :class="['fas', expandedUserIdLocal === user.userId ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                    {{ user.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center justify-center px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                        {{ user.count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-center font-medium">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                        <i class="fas fa-hourglass-end"></i>
                                        {{ formatHours(user.averageHours) }}
                                    </span>
                                </td>
                            </tr>
                            <!-- Expanded User Details Row -->
                            <tr v-if="expandedUserIdLocal === user.userId" class="bg-gray-50 dark:bg-gray-700/50">
                                <td :colspan="3" class="px-6 py-6">
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                            <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                                            Detailed Breakdown
                                        </h4>
                                        
                                        <!-- No Details State -->
                                        <div v-if="selectedUserDetails.length === 0" class="py-6 text-center">
                                            <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">No document details found</p>
                                        </div>

                                        <!-- Details Table -->
                                        <div v-else class="overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead class="bg-gray-200 dark:bg-gray-600">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Document</th>
                                                        <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Received</th>
                                                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Completed</th>
                                                        <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Pending Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                    <tr v-for="detail in selectedUserDetails" :key="`detail-${detail.documentId}`" class="bg-white dark:bg-gray-800">
                                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                            {{ detail.trackingNo }}
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate" :title="detail.particulars">
                                                            {{ detail.particulars }}
                                                        </td>
                                                        <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                            {{ detail.startDate }}
                                                        </td>
                                                        <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                            {{ detail.endDate }}
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium whitespace-nowrap">
                                                                <i class="fas fa-hourglass-end"></i>
                                                                {{ formatHours(detail.pendingHours) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <tr v-else>
                        <td :colspan="3" class="px-6 py-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                <p class="text-sm">No user data available</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Card View: used on small screens, or when chosen on desktop. Clicking a card shows the user's breakdown -->
        <div v-else class="p-4">
            <div v-if="filteredUserStatistics.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-3">
                <template v-for="(user, userIndex) in filteredUserStatistics" :key="`user-card-${userIndex}`">
                    <div
                        @click="$emit('expandUser', user.userId)"
                        :class="[
                            'border border-t-4 border-t-blue-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs p-4 cursor-pointer',
                            expandedUserIdLocal === user.userId
                                ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300 dark:border-indigo-700'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-blue-600 dark:bg-blue-700 text-white flex items-center justify-center flex-shrink-0 font-bold">{{ initials(user.name) }}</div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words">{{ user.name }}</h4>
                            </div>
                            <i :class="['fas', expandedUserIdLocal === user.userId ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 mt-1']"></i>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5">
                                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Transactions</div>
                                <div class="text-base font-bold text-blue-700 dark:text-blue-300">{{ user.count }}</div>
                            </div>
                            <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5">
                                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Average Duration</div>
                                <div class="text-base font-bold text-emerald-700 dark:text-emerald-300"><i class="fas fa-hourglass-end text-xs"></i> {{ formatHours(user.averageHours) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded: detailed breakdown -->
                    <div v-if="expandedUserIdLocal === user.userId" class="col-span-full rounded-lg border border-indigo-200 dark:border-indigo-800 bg-gray-50 dark:bg-gray-700/50 p-3 space-y-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-xs flex items-center gap-2">
                            <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                            Detailed Breakdown
                        </h4>
                        <p v-if="selectedUserDetails.length === 0" class="py-4 text-center text-xs text-gray-600 dark:text-gray-400">No document details found</p>
                        <div v-for="detail in selectedUserDetails" :key="`detail-card-${detail.documentId}`" class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 p-3 text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-semibold font-mono text-gray-900 dark:text-gray-100 break-all">{{ detail.trackingNo }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded font-medium whitespace-nowrap">
                                    <i class="fas fa-hourglass-end"></i>
                                    {{ formatHours(detail.pendingHours) }}
                                </span>
                            </div>
                            <p class="mt-1 text-gray-600 dark:text-gray-400 break-words line-clamp-2" :title="detail.particulars">{{ detail.particulars }}</p>
                            <div class="mt-2 flex flex-wrap justify-between gap-2 text-gray-600 dark:text-gray-400">
                                <span><span class="text-gray-500">Received:</span> {{ detail.startDate }}</span>
                                <span><span class="text-gray-500">Completed:</span> {{ detail.endDate }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div v-else class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                <p class="text-sm">No user data available</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ViewToggle from '@/Components/ViewToggle.vue';
import { useResponsiveView } from '@/Composables/useResponsiveView';

// Table on desktop (user can switch to cards), always cards on small screens
const { viewMode: currentViewMode, setViewMode } = useResponsiveView('dashboardUserStatsView');
const viewMode = computed({ get: () => currentViewMode.value, set: setViewMode });

const props = defineProps({
    filteredUserStatistics: Array<any>,
    expandedUserId: Number,
    selectedUserDetails: Array<any>,
    formatHours: Function,
});

const emit = defineEmits(['expandUser']);

const expandedUserIdLocal = computed({
    get: () => props.expandedUserId,
    set: (val) => emit('expandUser', val),
});

// Initials for the card avatar, e.g. "Juan D. Cruz" -> "JC"
const initials = (name: string) => {
    const words = String(name ?? '').replace(/[.,]/g, ' ').split(/\s+/).filter(Boolean);
    return ((words[0]?.[0] ?? '') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase() || '?';
};
</script>
