<template>
    <div class="border-t border-gray-200 dark:border-gray-700 p-6">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-users text-blue-600"></i>
            Employee Leaves Summary for {{ selectedHrYear }}
        </h4>
        <div class="overflow-x-auto">
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
    </div>
</template>

<script setup lang="ts">
defineProps<{
    employeeLeavesSummary: any[];
    uniqueLeaveTypes: string[];
    selectedHrYear: number;
    getOtherLeaveTypes: (emp: any) => string[];
    getOtherLeaveTypesMap: (emp: any) => Record<string, number>;
}>();
</script>
