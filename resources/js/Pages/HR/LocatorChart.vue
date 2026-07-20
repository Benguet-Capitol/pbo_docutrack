<template>
    <PageHead title="Locator Chart" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Locator Chart
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Locator Chart Section -->
            <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between flex-wrap gap-4">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-map-location-dot text-blue-600 dark:text-blue-400"></i>
                        {{ currentDateDisplay }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <label for="locatorDate" class="text-sm font-medium text-gray-700 dark:text-gray-300">Date:</label>
                        <input
                            type="date"
                            id="locatorDate"
                            v-model="selectedDateInput"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        />
                        <button
                            v-if="!isSelectedDateToday"
                            @click="resetToToday"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Reset to Today
                        </button>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm table-fixed">
                        <colgroup>
                            <col class="w-1/4">
                            <col class="w-1/6">
                            <col class="w-auto">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Employee Name</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="employee in employeesWithStatus" :key="employee.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ employee.name }}
                                </td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 text-center">
                                    <span v-if="employee.status === 'Present'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check-circle"></i>
                                        Present
                                    </span>
                                    <span v-else-if="employee.status === 'On Official Business'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-briefcase"></i>
                                        On Official Business
                                    </span>
                                    <span v-else-if="employee.status === 'On Leave'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                        <i class="fas fa-calendar-check"></i>
                                        On Leave
                                    </span>
                                    <span v-else-if="employee.status === 'Undertime'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        <i class="fas fa-hourglass-end"></i>
                                        Undertime
                                    </span>
                                    <span v-else-if="employee.status === 'Off Day'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-bed"></i>
                                        Off Day
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    <span v-if="employee.remarks" class="inline-flex items-center px-3 py-1 rounded-full text-xs  font-medium" :class="remarksBadgeClass(employee.status)">
                                        {{ employee.remarks }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- No Data State -->
                    <div v-if="employeesWithStatus.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-700">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No employees found</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';

// ============== Data ==============
const employees = ref<Array<{id: number; employee_id: string; name: string; office_id: number; designation: string}>>([]);
const leaves = ref<any[]>([]);
const travelOrders = ref<any[]>([]);
const passSlips = ref<any[]>([]);
const tardiness = ref<any[]>([]);

// The date the Locator Chart is being viewed for — defaults to today,
// but the user can pick a different date to see historical/future records.
const selectedDate = ref<Date>(new Date());

// Bridges the <input type="date"> (which needs "YYYY-MM-DD" strings)
// to/from the selectedDate Date object.
const selectedDateInput = computed({
    get: () => {
        const y = selectedDate.value.getFullYear();
        const m = (selectedDate.value.getMonth() + 1).toString().padStart(2, '0');
        const d = selectedDate.value.getDate().toString().padStart(2, '0');
        return `${y}-${m}-${d}`;
    },
    set: (value: string) => {
        if (!value) return;
        const [y, m, d] = value.split('-').map(Number);
        selectedDate.value = new Date(y, m - 1, d);
    }
});

const isSelectedDateToday = computed(() => {
    const today = new Date();
    return selectedDate.value.getDate() === today.getDate() &&
           selectedDate.value.getMonth() === today.getMonth() &&
           selectedDate.value.getFullYear() === today.getFullYear();
});

const resetToToday = () => {
    selectedDate.value = new Date();
};

// ============== Computed Properties ==============

/**
 * Display label for the currently selected date (e.g., "July 20, 2026")
 */
const currentDateDisplay = computed(() => {
    return selectedDate.value.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

/**
 * Check if the selected date is a Saturday or Sunday.
 */
const isSelectedDateWeekend = computed(() => {
    const day = selectedDate.value.getDay();
    return day === 0 || day === 6;
});

/**
 * Check if a date matches the selected date
 */
const isDateSelected = (dateStr: string): boolean => {
    const date = new Date(dateStr);
    const target = selectedDate.value;
    return date.getDate() === target.getDate() &&
           date.getMonth() === target.getMonth() &&
           date.getFullYear() === target.getFullYear();
};

/**
 * Strip the time component from a Date, leaving just the calendar date
 * at local midnight — needed so range comparisons aren't affected by
 * what time of day "now" happens to be.
 */
const toDateOnly = (date: Date): Date => {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
};

/**
 * Check if a date range includes the selected date (date-only comparison)
 */
const isDateRangeSelected = (dateRangeStr: string): boolean => {
    if (!dateRangeStr) return false;

    if (dateRangeStr.includes(' - ')) {
        const [startStr, endStr] = dateRangeStr.split(' - ');
        const startDate = toDateOnly(new Date(startStr.trim()));
        const endDate = toDateOnly(new Date(endStr.trim()));
        const target = toDateOnly(selectedDate.value);

        return target >= startDate && target <= endDate;
    } else {
        return isDateSelected(dateRangeStr.trim());
    }
};

/**
 * Check if the selected date falls within inclusive_dates array
 */
const isSelectedDateInInclusiveDates = (inclusiveDates: string[] | undefined): boolean => {
    if (!inclusiveDates || !Array.isArray(inclusiveDates)) return false;

    for (const dateEntry of inclusiveDates) {
        if (isDateRangeSelected(dateEntry)) {
            return true;
        }
    }
    return false;
};

/**
 * Common name suffixes to exclude when identifying the last name.
 */
const NAME_SUFFIXES = new Set(['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v']);

/**
 * Extract the last name from a "Firstname M. LastName [Suffix]" formatted
 * name, ignoring generational suffixes so "Avelino B. Cayat Jr." sorts by
 * "Cayat", not "Jr."
 */
const getLastName = (fullName: string): string => {
    const parts = fullName.trim().split(/\s+/);
    if (parts.length === 0) return fullName;

    let lastIndex = parts.length - 1;
    const lastToken = parts[lastIndex].toLowerCase().replace(/\.$/, '');

    if (NAME_SUFFIXES.has(lastToken) && parts.length > 1) {
        lastIndex -= 1;
    }

    return parts[lastIndex] || fullName;
};

/**
 * Get employee status and remarks for the selected date
 */
const getEmployeeStatusAndRemarks = (employee: any): { status: string; remarks: string } => {
    // Weekends override everything else — nobody's "Present" or "On Leave"
    // on a non-working day, the day itself is the reason.
    if (isSelectedDateWeekend.value) {
        return {
            status: 'Off Day',
            remarks: ''
        };
    }
    const employeeId = employee.id;

    const formatTime = (timeStr: string): string => {
        if (!timeStr) return '';
        const [hoursStr, minutesStr] = timeStr.split(':');
        let hours = parseInt(hoursStr, 10);
        const minutes = minutesStr;
        const period = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        if (hours === 0) hours = 12;
        return `${hours.toString().padStart(2, '0')}:${minutes} ${period}`;
    };

    // Check for undertime first
    const undertime = tardiness.value.find(t =>
        t.employee_id === employeeId && isDateSelected(t.date_filed)
    );
    if (undertime) {
        return {
            status: 'Undertime',
            remarks: `${undertime.reason} (${formatTime(undertime.requested_time)} - ${formatTime(undertime.return_time)})`
        };
    }

    // Check for leave
    const leave = leaves.value.find(l =>
        l.employee_id === employeeId && isSelectedDateInInclusiveDates(l.inclusive_dates)
    );
    if (leave) {
        return {
            status: 'On Leave',
            remarks: `${leave.type_of_leave}`
        };
    }

    // Check for pass slip
    const passSlip = passSlips.value.find(ps => {
        if (!ps.employees || !Array.isArray(ps.employees)) return false;
        const isEmployeeInPS = ps.employees.some((emp: any) => emp.id === employeeId);
        if (!isEmployeeInPS) return false;

        return isSelectedDateInInclusiveDates(ps.inclusive_dates);
    });

    if (passSlip) {
        return {
            status: 'On Official Business',
            remarks: `Pass Slip: ${passSlip.purpose} at ${passSlip.location}`
        };
    }

    // Check for travel order
    const travelOrder = travelOrders.value.find(to => {
        if (!to.employees || !Array.isArray(to.employees)) return false;
        const isEmployeeInTO = to.employees.some((emp: any) => emp.id === employeeId);
        if (!isEmployeeInTO) return false;

        return isSelectedDateInInclusiveDates(to.inclusive_dates);
    });

    if (travelOrder) {
        return {
            status: 'On Official Business',
            remarks: `Travel Order: ${Array.isArray(travelOrder.purpose) ? travelOrder.purpose.join(', ') : travelOrder.purpose} at ${travelOrder.going_to}`
        };
    }

    // Default to present
    return {
        status: 'Present',
        remarks: ''
    };
};

const remarksBadgeClass = (status: string): string => {
    switch (status) {
        case 'On Leave':
            return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
        case 'On Official Business':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'Undertime':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        case 'Off Day':
            return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
        case 'Present':
        default:
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
    }
};

/**
 * Set of employee IDs that have an active leave, pass slip, travel order,
 * or tardiness record today — used to filter the full employee roster
 * down to only those who should appear in the Locator Chart.
 */
const employeeIdsWithAnyRecord = computed(() => {
    const ids = new Set<number>();

    tardiness.value.forEach(t => {
        ids.add(t.employee_id);
    });

    leaves.value.forEach(l => {
        ids.add(l.employee_id);
    });

    passSlips.value.forEach(ps => {
        ids.add(ps.employee_id);
    });

    travelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach((emp: any) => ids.add(emp.id));
        }
    });

    return ids;
});

/**
 * Get all employees with their status and remarks for today —
 * filtered to only those with an active record in one of the four sources.
 */
const employeesWithStatus = computed(() => {
    return employees.value
        .filter(employee => employeeIdsWithAnyRecord.value.has(employee.id))
        .map(employee => {
            const { status, remarks } = getEmployeeStatusAndRemarks(employee);
            return {
                ...employee,
                status,
                remarks
            };
        })
        .sort((a, b) => {
            const lastNameCompare = getLastName(a.name).localeCompare(getLastName(b.name));
            if (lastNameCompare !== 0) return lastNameCompare;
            return a.name.localeCompare(b.name); // tiebreaker on full name
        });
});

// ============== Fetch Data ==============

/**
 * Fetch employees from API
 */
const fetchEmployees = async () => {
    try {
        const response = await fetch('/api/employees');
        if (response.ok) {
            employees.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching employees:', e);
    }
};

/**
 * Fetch leaves from API
 */
const fetchLeaves = async () => {
    try {
        const response = await fetch('/api/leaves');
        if (response.ok) {
            leaves.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching leaves:', e);
    }
};

/**
 * Fetch travel orders from API
 */
const fetchTravelOrders = async () => {
    try {
        const response = await fetch('/api/travel-orders');
        if (response.ok) {
            travelOrders.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching travel orders:', e);
    }
};

/**
 * Fetch pass slips from API
 */
const fetchPassSlips = async () => {
    try {
        const response = await fetch('/api/pass-slips');
        if (response.ok) {
            passSlips.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching pass slips:', e);
    }
};

/**
 * Fetch tardiness/undertime from API
 */
const fetchTardiness = async () => {
    try {
        const response = await fetch('/api/tardiness');
        if (response.ok) {
            const result = await response.json();
            tardiness.value = result.data || result;
        }
    } catch (e) {
        console.error('Error fetching tardiness:', e);
    }
};

/**
 * Initialize: Fetch all data on mount
 */
onMounted(async () => {
    await Promise.all([
        fetchEmployees(),
        fetchLeaves(),
        fetchTravelOrders(),
        fetchPassSlips(),
        fetchTardiness()
    ]);
});
</script>
