<template>
    <!-- HR Summary Panel: Leaves, Travel Orders, Pass Slips for Current Month -->
    <div v-if="canViewHRSummary" class="w-full bg-white dark:bg-gray-800 rounded-lg shadow relative">
        <!-- Loading Animation Overlay -->
        <div v-if="hrLoading" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent dark:via-gray-600/30 rounded-lg hr-loading-shimmer"></div>
        <!-- Header Section -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <!-- Left side: Title and Filters -->
                <div class="flex flex-col gap-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-users text-emerald-600 dark:text-emerald-400"></i>
                        Leaves, Travel Orders, Pass Slips and Tardiness/Undertime Summary
                    </h3>

                    <!-- Month and Year Filters -->
                    <div class="flex flex-col sm:flex-row gap-2 items-end">
                        <!-- Year Filter -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Year</label>
                            <select
                                v-model.number="selectedHRYearLocal"
                                class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-2 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                            >
                                <option v-for="year in availableHRYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <!-- Month Filter -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Month</label>
                            <select
                                v-model.number="selectedHRMonthLocal"
                                class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-2 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer max-w-[120px]"
                            >
                                <option v-for="(monthName, index) in months" :key="index" :value="index">
                                    {{ monthName }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Right side: Generate Report Button -->
                <button
                    @click="$emit('showSummaryModal')"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors text-xs font-medium whitespace-nowrap"
                >
                    <i class="fas fa-file-pdf"></i>
                    Summary of Leaves, TOs, PS and Tardiness/Undertimes
                </button>
            </div>
        </div>

        <!-- Summary Cards Grid -->
        <div class="w-full p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 auto-rows-max">
            <!-- Leaves Card -->
            <div 
                @click="expandedHRTypeLocal = expandedHRTypeLocal === 'leaves' ? null : 'leaves'"
                class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                :class="expandedHRTypeLocal === 'leaves' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-blue-400'"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400"></i>
                        Leaves
                    </h4>
                    <i :class="['fas', expandedHRTypeLocal === 'leaves' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                </div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                    {{ currentMonthLeaves.length }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ Object.keys(currentMonthLeavesByType).length }}</span> types filed
                </p>
            </div>

            <!-- Travel Orders Card -->
            <div 
                @click="expandedHRTypeLocal = expandedHRTypeLocal === 'travelOrders' ? null : 'travelOrders'"
                class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                :class="expandedHRTypeLocal === 'travelOrders' ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-orange-400'"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-orange-600 dark:text-orange-400"></i>
                        Travel Orders
                    </h4>
                    <i :class="['fas', expandedHRTypeLocal === 'travelOrders' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                </div>
                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400 mb-1">
                    {{ currentMonthTravelOrders.length }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ uniqueEmployeesTravelOrders.size }}</span> employees involved
                </p>
            </div>

            <!-- Pass Slips Card -->
            <div 
                @click="expandedHRTypeLocal = expandedHRTypeLocal === 'passSlips' ? null : 'passSlips'"
                class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                :class="expandedHRTypeLocal === 'passSlips' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-emerald-400'"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-emerald-600 dark:text-emerald-400"></i>
                        Pass Slips
                    </h4>
                    <i :class="['fas', expandedHRTypeLocal === 'passSlips' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                </div>
                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">
                    {{ currentMonthPassSlips.length }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ uniqueEmployeesPassSlips.size }}</span> employees involved
                </p>
            </div>

            <!-- Tardiness/Undertime Card -->
            <div 
                @click="expandedHRTypeLocal = expandedHRTypeLocal === 'tardiness' ? null : 'tardiness'"
                class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                :class="expandedHRTypeLocal === 'tardiness' ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-red-400'"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-hourglass-end text-red-600 dark:text-red-400"></i>
                        Tardiness/Undertime
                    </h4>
                    <i :class="['fas', expandedHRTypeLocal === 'tardiness' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                </div>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-1">
                    {{ currentMonthTardiness.length }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ uniqueEmployeesTardiness.size }}</span> employees involved
                </p>
            </div>
        </div>

        <!-- Expanded Details Section (moved to HRDetailsPanel) -->
        <HRDetailsPanel 
            v-if="expandedHRTypeLocal"
            :expanded-type="expandedHRTypeLocal"
            :current-month-leaves-by-type-with-employees="currentMonthLeavesByTypeWithEmployees"
            :current-month-travel-orders-by-emp="currentMonthTravelOrdersByEmp"
            :current-month-pass-slips-by-emp="currentMonthPassSlipsByEmp"
            :current-month-tardiness-by-emp="currentMonthTardinessByEmp"
            :format-date-range="formatDateRange"
            :format-time="formatTime"
            :format-inclusive-dates="formatInclusiveDates"
        />

        <!-- Employee Leaves by Type Summary (Yearly) (moved to HREmployeesTable) -->
        <HREmployeesTable 
            v-if="employeeLeavesSummary.length > 0"
            :employee-leaves-summary="employeeLeavesSummary"
            :unique-leave-types="uniqueLeaveTypes"
            :selected-hr-year="selectedHRYear"
            :get-other-leave-types="getOtherLeaveTypes"
            :get-other-leave-types-map="getOtherLeaveTypesMap"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import HRDetailsPanel from './HRDetailsPanel.vue';
import HREmployeesTable from './HREmployeesTable.vue';

const props = defineProps({
    canViewHRSummary: Boolean,
    hrLoading: Boolean,
    selectedHRYear: Number,
    selectedHRMonth: Number,
    availableHRYears: Array<number>,
    currentMonthLeaves: Array<any>,
    currentMonthLeavesByType: Object,
    currentMonthLeavesByTypeWithEmployees: Object,
    currentMonthTravelOrders: Array<any>,
    uniqueEmployeesTravelOrders: Object,
    currentMonthTravelOrdersByEmp: Object,
    currentMonthPassSlips: Array<any>,
    uniqueEmployeesPassSlips: Object,
    currentMonthPassSlipsByEmp: Object,
    currentMonthTardiness: Array<any>,
    uniqueEmployeesTardiness: Object,
    currentMonthTardinessByEmp: Object,
    employeeLeavesSummary: Array<any>,
    uniqueLeaveTypes: Array<any>,
    formatDateRange: Function,
    formatTime: Function,
    formatInclusiveDates: Function,
    getOtherLeaveTypes: Function,
    getOtherLeaveTypesMap: Function,
});

const emit = defineEmits(['update:selectedHRYear', 'update:selectedHRMonth', 'showSummaryModal']);

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const selectedHRYearLocal = computed({
    get: () => props.selectedHRYear,
    set: (val) => emit('update:selectedHRYear', val),
});

const selectedHRMonthLocal = computed({
    get: () => props.selectedHRMonth,
    set: (val) => emit('update:selectedHRMonth', val),
});

const expandedHRTypeLocal = ref<'leaves' | 'travelOrders' | 'passSlips' | 'tardiness' | null>(null);
</script>

<style scoped>
@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.hr-loading-shimmer {
    animation: shimmer 2s infinite;
    background-size: 200% 100%;
}
</style>
