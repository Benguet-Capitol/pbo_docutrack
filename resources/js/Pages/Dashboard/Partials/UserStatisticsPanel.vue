<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                Summary of Users Average Time per Transaction
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">User Name</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Total Transactions</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Average Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-if="userStatistics.length > 0">
                        <template v-for="(user, userIndex) in userStatistics" :key="`user-${userIndex}`">
                            <tr 
                                @click="toggleUserExpanded(user.userId)"
                                :class="['transition-colors duration-150 cursor-pointer', expandedUserId === user.userId ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <i :class="['fas', expandedUserId === user.userId ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
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
                            <tr v-if="expandedUserId === user.userId" class="bg-gray-50 dark:bg-gray-700/50">
                                <td :colspan="3" class="px-6 py-6">
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                            <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                                            Detailed Breakdown
                                        </h4>
                                        <div v-if="selectedUserDetails.length === 0" class="py-6 text-center">
                                            <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">No document details found</p>
                                        </div>
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
    </div>
</template>

<script setup lang="ts">
defineProps<{
    userStatistics: any[];
    expandedUserId: number | null;
    selectedUserDetails: any[];
    formatHours: (hours: number) => string;
    toggleUserExpanded: (userId: number) => void;
}>();
</script>
