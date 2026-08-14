<template>
    <!-- Expanded Details Section -->
    <div v-if="expandedType" class="border-t border-gray-200 dark:border-gray-700 p-6">
        <!-- Leaves Details -->
        <div v-if="expandedType === 'leaves'">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-calendar-alt text-blue-600"></i>
                Leaves by Type
            </h4>
            <div v-if="!currentMonthLeavesByTypeWithEmployees || Object.keys(currentMonthLeavesByTypeWithEmployees).length === 0" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <i class="fas fa-inbox text-gray-400 mr-2"></i>
                    No leave records found for this period.
                </p>
            </div>
            <div v-else class="space-y-4 max-h-96 overflow-y-auto">
                <div v-for="(data, type) in currentMonthLeavesByTypeWithEmployees" :key="type" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ type }}</p>
                        <span class="px-2 py-1 bg-blue-200 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">{{ data.count }}</span>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(entry, idx) in data.entries" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ entry.name }}</p>
                                <span v-if="entry.isHalfDay" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded text-xs font-medium">
                                    {{ entry.halfDayPeriod }}
                                </span>
                            </div>
                            <p v-if="entry.dates" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                {{ entry.dates }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Travel Orders Details -->
        <div v-else-if="expandedType === 'travelOrders'">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-map-marked-alt text-orange-600"></i>
                Travel Orders by Employee
            </h4>
            <div v-if="!currentMonthTravelOrdersByEmp || Object.keys(currentMonthTravelOrdersByEmp).length === 0" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <i class="fas fa-inbox text-gray-400 mr-2"></i>
                    No travel order records found for this period.
                </p>
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="(orders, empName) in currentMonthTravelOrdersByEmp" :key="empName" class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                    <div class="space-y-2">
                        <div v-for="(order, idx) in orders" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                            <p class="text-gray-700 dark:text-gray-300 font-medium">{{ order.control_no }}</p>
                            <p v-if="order.destination" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-map-pin text-orange-500 mr-1"></i>
                                {{ order.destination }}
                            </p>
                            <p v-if="order.inclusive_dates && Array.isArray(order.inclusive_dates) && order.inclusive_dates.length > 0" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar text-orange-500 mr-1"></i>
                                {{ formatInclusiveDatesForDisplay(order.inclusive_dates) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pass Slips Details -->
        <div v-else-if="expandedType === 'passSlips'">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-emerald-600"></i>
                Pass Slips by Employee
            </h4>
            <div v-if="!currentMonthPassSlipsByEmp || Object.keys(currentMonthPassSlipsByEmp).length === 0" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <i class="fas fa-inbox text-gray-400 mr-2"></i>
                    No pass slip records found for this period.
                </p>
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="(slips, empName) in currentMonthPassSlipsByEmp" :key="empName" class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800">
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                    <div class="space-y-2">
                        <div v-for="(slip, idx) in slips" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                            <p class="text-gray-700 dark:text-gray-300 font-medium">{{ slip.control_no }}</p>
                            <p v-if="slip.inclusive_dates && slip.inclusive_dates.length > 0" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar text-emerald-500 mr-1"></i>
                                {{ formatInclusiveDatesForDisplay(slip.inclusive_dates) }}
                            </p>
                            <p v-else-if="slip.date" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar text-emerald-500 mr-1"></i>
                                {{ new Date(slip.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                            </p>
                            <p v-if="slip.requested_time" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-clock text-emerald-500 mr-1"></i>
                                Leave: {{ formatTime(slip.requested_time) }}
                            </p>
                            <p v-if="slip.expected_return_time" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-hourglass-end text-emerald-500 mr-1"></i>
                                Return: {{ formatTime(slip.expected_return_time) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tardiness/Undertime Details -->
        <div v-else-if="expandedType === 'tardiness'">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-hourglass-end text-red-600"></i>
                Tardiness/Undertime by Employee
            </h4>
            <div v-if="!currentMonthTardinessByEmp || Object.keys(currentMonthTardinessByEmp).length === 0" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    <i class="fas fa-inbox text-gray-400 mr-2"></i>
                    No tardiness/undertime records found for this period.
                </p>
            </div>
            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="(records, empName) in currentMonthTardinessByEmp" :key="empName" class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800">
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                    <div class="space-y-2">
                        <div v-for="(record, idx) in records" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                            <p class="text-gray-700 dark:text-gray-300 font-medium">{{ record.control_no }}</p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar text-red-500 mr-1"></i>
                                Date: {{ new Date(record.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-flag text-red-500 mr-1"></i>
                                Type: <span class="font-medium">{{ record.type }}</span>
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-clock text-red-500 mr-1"></i>
                                Time: {{ formatTime(record.requested_time) }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-note-sticky text-red-500 mr-1"></i>
                                Reason: {{ record.reason }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps({
    expandedType: String,
    currentMonthLeavesByTypeWithEmployees: Object,
    currentMonthTravelOrdersByEmp: Object,
    currentMonthPassSlipsByEmp: Object,
    currentMonthTardinessByEmp: Object,
    formatDateRange: Function,
    formatTime: Function,
    formatInclusiveDates: Function,
});

const formatInclusiveDatesForDisplay = (inclusiveDates: string[] | undefined): string => {
    if (!inclusiveDates || !Array.isArray(inclusiveDates) || inclusiveDates.length === 0) {
        return '';
    }
    
    // Helper function to parse date string in YYYY-MM-DD format using local time
    const parseDateString = (dateString: string): Date => {
        const [year, month, day] = dateString.trim().split('-').map(Number);
        return new Date(year, month - 1, day);
    };
    
    const formattedDates = inclusiveDates.map((dateEntry: string) => {
        if (!dateEntry) return '';
        if (dateEntry.includes(' - ')) {
            const parts = dateEntry.split(' - ');
            if (parts.length === 2) {
                const startStr = parts[0].trim();
                const endStr = parts[1].trim();
                const startDate = parseDateString(startStr);
                const endDate = parseDateString(endStr);
                const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                return `${start} - ${end}`;
            }
        } else {
            const date = parseDateString(dateEntry);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
        return '';
    }).filter((d: string) => d);
    
    return formattedDates.join(', ');
};
</script>
