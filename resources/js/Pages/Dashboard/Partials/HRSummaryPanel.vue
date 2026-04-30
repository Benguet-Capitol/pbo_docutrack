<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <!-- HR Summary Header and Filters -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-chart-bar text-emerald-600 dark:text-emerald-400"></i>
                    HR Summary
                </h2>
                <div class="flex gap-3">
                    <select
                        v-model.number="selectedMonth"
                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option v-for="(name, idx) in months" :key="idx" :value="idx">
                            {{ name }}
                        </option>
                    </select>
                    <select
                        v-model.number="selectedYear"
                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option v-for="year in availableHRYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Leaves Card -->
                <div 
                    @click="emit('toggle-hr-expanded', 'leaves')"
                    class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                    :class="expandedHRType === 'leaves' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-blue-400'"
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400"></i>
                            Leaves
                        </h4>
                        <i :class="['fas', expandedHRType === 'leaves' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
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
                    @click="emit('toggle-hr-expanded', 'travelOrders')"
                    class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                    :class="expandedHRType === 'travelOrders' ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-orange-400'"
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-map-marked-alt text-orange-600 dark:text-orange-400"></i>
                            Travel Orders
                        </h4>
                        <i :class="['fas', expandedHRType === 'travelOrders' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
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
                    @click="emit('toggle-hr-expanded', 'passSlips')"
                    class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                    :class="expandedHRType === 'passSlips' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-emerald-400'"
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-clipboard-list text-emerald-600 dark:text-emerald-400"></i>
                            Pass Slips
                        </h4>
                        <i :class="['fas', expandedHRType === 'passSlips' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
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
                    @click="emit('toggle-hr-expanded', 'tardiness')"
                    class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                    :class="expandedHRType === 'tardiness' ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-red-400'"
                >
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-hourglass-end text-red-600 dark:text-red-400"></i>
                            Tardiness/Undertime
                        </h4>
                        <i :class="['fas', expandedHRType === 'tardiness' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                    </div>
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-1">
                        {{ currentMonthTardiness.length }}
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ uniqueEmployeesTardiness.size }}</span> employees involved
                    </p>
                </div>
            </div>
        </div>

        <!-- Expanded Details Section -->
        <HRDetailsPanel
            v-if="expandedHRType"
            v-bind="hrDetailsPanelProps"
        />

        <!-- Employee Leaves Summary Table -->
        <HREmployeesSummaryTable
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
import { computed, ref } from 'vue';
import HRDetailsPanel from './HRDetailsPanel.vue';
import HREmployeesSummaryTable from './HREmployeesSummaryTable.vue';

const props = defineProps<{
    leaves: any[];
    travelOrders: any[];
    passSlips: any[];
    tardiness: any[];
    expandedHRType: 'leaves' | 'travelOrders' | 'passSlips' | 'tardiness' | null;
    selectedHRMonth: number;
    selectedHRYear: number;
    employeeLeavesSummary: any[];
    uniqueLeaveTypes: string[];
    formatDateRange: (from: any, to: any) => string;
    formatTime: (time: string) => string;
    formatInclusiveDates: (dates: any) => string;
    getOtherLeaveTypes: (emp: any) => string[];
    getOtherLeaveTypesMap: (emp: any) => Record<string, number>;
}>();

const emit = defineEmits<{
    'toggle-hr-expanded': [type: 'leaves' | 'travelOrders' | 'passSlips' | 'tardiness'];
    'update:selected-month': [value: number];
    'update:selected-year': [value: number];
}>();

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const selectedMonth = computed({
    get: () => props.selectedHRMonth,
    set: (val) => emit('update:selected-month', val)
});
const selectedYear = computed({
    get: () => props.selectedHRYear,
    set: (val) => emit('update:selected-year', val)
});

const leaveInSelectedMonth = (leave: any): boolean => {
    if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;
    
    const monthStart = new Date(selectedYear.value, selectedMonth.value, 1);
    const monthEnd = new Date(selectedYear.value, selectedMonth.value + 1, 0);
    
    for (const dateEntry of leave.inclusive_dates) {
        if (!dateEntry) continue;
        if (dateEntry.includes(' - ')) {
            const [startStr, endStr] = dateEntry.split(' - ');
            const rangeStart = new Date(startStr.trim());
            const rangeEnd = new Date(endStr.trim());
            if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                return true;
            }
        } else {
            const date = new Date(dateEntry.trim());
            if (date.getMonth() === selectedMonth.value && date.getFullYear() === selectedYear.value) {
                return true;
            }
        }
    }
    return false;
};

const currentMonthLeaves = computed(() => props.leaves.filter(leave => leaveInSelectedMonth(leave)));

const currentMonthLeavesByType = computed(() => {
    const grouped: Record<string, number> = {};
    currentMonthLeaves.value.forEach(leave => {
        const type = leave.type_of_leave;
        grouped[type] = (grouped[type] || 0) + 1;
    });
    return grouped;
});

const currentMonthLeavesByTypeWithEmployees = computed(() => {
    const grouped: Record<string, { count: number; entries: Array<{ name: string; dates: string; isHalfDay: boolean; halfDayPeriod: string | null }> }> = {};
    currentMonthLeaves.value.forEach(leave => {
        const type = leave.type_of_leave;
        const empName = leave.employee?.name || 'Unknown Employee';
        
        let datesStr = props.formatDateRange ? '' : '';
        if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 2) {
            datesStr = `${leave.inclusive_dates[0]} - ${leave.inclusive_dates[1]}`;
        } else if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 1) {
            datesStr = leave.inclusive_dates[0];
        }
        
        if (!grouped[type]) {
            grouped[type] = { count: 0, entries: [] };
        }
        grouped[type].count += 1;
        grouped[type].entries.push({ 
            name: empName, 
            dates: datesStr,
            isHalfDay: leave.is_half_day || false,
            halfDayPeriod: leave.half_day_period || null
        });
    });
    
    Object.keys(grouped).forEach(type => {
        grouped[type].entries.sort((a, b) => a.name.localeCompare(b.name));
    });
    return grouped;
});

// Alias for HRDetailsPanel component (expected prop name)
const currentMonthLeavesTypeWithEmployees = computed(() => currentMonthLeavesByTypeWithEmployees.value);

// Alias for HREmployeesSummaryTable component (expected prop name - selectedHRYear not selectedYear)
const selectedHRYear = computed(() => selectedYear.value);

// Create readonly props object for HRDetailsPanel component
const hrDetailsPanelProps = computed(() => ({
    expandedType: props.expandedHRType,
    currentMonthLeaves: currentMonthLeaves.value,
    currentMonthLeavesTypeWithEmployees: currentMonthLeavesTypeWithEmployees.value,
    currentMonthTravelOrders: currentMonthTravelOrders.value,
    currentMonthTravelOrdersByEmp: currentMonthTravelOrdersByEmp.value,
    currentMonthPassSlips: currentMonthPassSlips.value,
    currentMonthPassSlipsByEmp: currentMonthPassSlipsByEmp.value,
    currentMonthTardiness: currentMonthTardiness.value,
    currentMonthTardinesssByEmp: currentMonthTardinesssByEmp.value,
    formatDateRange: props.formatDateRange,
    formatTime: props.formatTime,
    formatInclusiveDates: props.formatInclusiveDates,
} as const));

const travelOrderInSelectedMonth = (to: any): boolean => {
    if (!to.from_date || !to.to_date) return false;
    const fromDate = new Date(to.from_date);
    const toDate = new Date(to.to_date);
    const monthStart = new Date(selectedYear.value, selectedMonth.value, 1);
    const monthEnd = new Date(selectedYear.value, selectedMonth.value + 1, 0);
    return fromDate <= monthEnd && toDate >= monthStart;
};

const currentMonthTravelOrders = computed(() => props.travelOrders.filter(to => travelOrderInSelectedMonth(to)));

const uniqueEmployeesTravelOrders = computed(() => {
    const empSet = new Set<string>();
    currentMonthTravelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach(emp => empSet.add(emp.name));
        }
    });
    return empSet;
});

const currentMonthTravelOrdersByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthTravelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach(emp => {
                if (!grouped[emp.name]) grouped[emp.name] = [];
                grouped[emp.name].push(to);
            });
        }
    });
    return grouped;
});

const currentMonthPassSlips = computed(() => {
    return props.passSlips.filter(ps => {
        const psDate = new Date(ps.date);
        return psDate.getMonth() === selectedMonth.value && psDate.getFullYear() === selectedYear.value;
    });
});

const uniqueEmployeesPassSlips = computed(() => {
    const empSet = new Set<string>();
    currentMonthPassSlips.value.forEach(ps => {
        if (ps.employees && Array.isArray(ps.employees)) {
            ps.employees.forEach(emp => empSet.add(emp.name));
        }
    });
    return empSet;
});

const currentMonthPassSlipsByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthPassSlips.value.forEach(ps => {
        if (ps.employees && Array.isArray(ps.employees)) {
            ps.employees.forEach(emp => {
                if (!grouped[emp.name]) grouped[emp.name] = [];
                grouped[emp.name].push(ps);
            });
        }
    });
    return grouped;
});

const tardinessInSelectedMonth = (tu: any): boolean => {
    if (!tu.date_filed) return false;
    const tuDate = new Date(tu.date_filed);
    return tuDate.getMonth() === selectedMonth.value && tuDate.getFullYear() === selectedYear.value;
};

const currentMonthTardiness = computed(() => props.tardiness.filter(tu => tardinessInSelectedMonth(tu)));

const uniqueEmployeesTardiness = computed(() => {
    const empSet = new Set<string>();
    currentMonthTardiness.value.forEach(tu => {
        if (tu.employee) empSet.add(tu.employee.name);
    });
    return empSet;
});

const currentMonthTardinesssByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthTardiness.value.forEach(tu => {
        const empName = tu.employee?.name || 'Unknown Employee';
        if (!grouped[empName]) grouped[empName] = [];
        grouped[empName].push(tu);
    });
    return grouped;
});

const availableHRYears = computed(() => {
    const yearsSet = new Set<number>();
    props.leaves.forEach(leave => {
        if (leave.inclusive_dates && Array.isArray(leave.inclusive_dates)) {
            leave.inclusive_dates.forEach((dateEntry: string) => {
                if (dateEntry) {
                    const dateStr = dateEntry.includes(' - ') ? dateEntry.split(' - ')[0] : dateEntry;
                    const year = new Date(dateStr.trim()).getFullYear();
                    yearsSet.add(year);
                }
            });
        }
    });
    return Array.from(yearsSet).sort((a, b) => a - b);
});
</script>
