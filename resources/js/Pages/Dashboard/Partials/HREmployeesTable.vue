<template>
    <!-- Employee Leaves by Type Summary (Yearly) -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i>
                Employee Leaves Summary for {{ selectedHRYear }}
            </h4>
            <ViewToggle v-model="viewMode" />
        </div>
        <div v-if="viewMode === 'table'" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Employee</th>
                        <th v-for="leaveType in uniqueLeaveTypes" :key="leaveType" class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200 text-xs">
                            {{ leaveType }}
                        </th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="empSummary in employeeLeavesSummary" :key="empSummary.employeeId" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ empSummary.employeeName }}
                        </td>
                        <td v-for="leaveType in uniqueLeaveTypes" :key="`${empSummary.employeeId}-${leaveType}`" class="px-4 py-3 text-center">
                            <span v-if="empSummary.leaveTypes[leaveType]" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-semibold">
                                {{ empSummary.leaveTypes[leaveType] }}
                            </span>
                            <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold">
                            <div class="space-y-1">
                                <span class="inline-flex items-center justify-center px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded text-xs font-medium">
                                    {{ empSummary.totalLeaves }}
                                </span>
                                <div v-if="getOtherLeaveTypes(empSummary).length > 0" class="text-xs text-gray-600 dark:text-gray-400 mt-2 border-t border-gray-300 dark:border-gray-600 pt-2">
                                    <div v-for="(count, leaveType) in getOtherLeaveTypesMap(empSummary)" :key="leaveType" class="flex justify-between">
                                        <span>{{ leaveType }}:</span>
                                        <span class="font-semibold">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Card View: used on small screens, or when chosen on desktop -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
            <div
                v-for="empSummary in employeeLeavesSummary"
                :key="`card-${empSummary.employeeId}`"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-500 rounded-lg shadow-sm text-xs p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <h5 class="text-sm font-semibold text-gray-900 dark:text-white break-words">{{ empSummary.employeeName }}</h5>
                    <span class="inline-flex items-center px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded font-semibold whitespace-nowrap" title="Total">
                        {{ empSummary.totalLeaves }} total
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <template v-for="leaveType in uniqueLeaveTypes" :key="`card-${empSummary.employeeId}-${leaveType}`">
                        <span v-if="empSummary.leaveTypes[leaveType]" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                            {{ leaveType }}
                            <span class="font-bold">{{ empSummary.leaveTypes[leaveType] }}</span>
                        </span>
                    </template>
                    <span v-for="(count, leaveType) in getOtherLeaveTypesMap(empSummary)" :key="`card-other-${empSummary.employeeId}-${leaveType}`" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        {{ leaveType }}
                        <span class="font-bold">{{ count }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ViewToggle from '@/Components/ViewToggle.vue';
import { useResponsiveView } from '@/Composables/useResponsiveView';

defineProps({
    employeeLeavesSummary: Array<any>,
    uniqueLeaveTypes: Array<any>,
    selectedHRYear: Number,
    getOtherLeaveTypes: Function,
    getOtherLeaveTypesMap: Function,
});

// Table on desktop (user can switch to cards), always cards on small screens
const { viewMode: currentViewMode, setViewMode } = useResponsiveView('dashboardLeavesView');
const viewMode = computed({ get: () => currentViewMode.value, set: setViewMode });
</script>
