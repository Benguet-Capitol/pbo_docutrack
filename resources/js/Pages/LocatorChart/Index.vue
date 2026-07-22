<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Locator Chart | <span class="text-blue-700 dark:text-blue-300">Provincial Budget Office</span>
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Locator Chart Section -->
            <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between flex-wrap gap-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-map-location-dot text-blue-600 dark:text-blue-400"></i>
                        Date: {{ currentDateDisplay }}
                    </h3>
                    <div class="flex items-center gap-3 flex-wrap print:hidden">
                        <!-- View toggle: grid (compact, no-scroll) vs list (original table) -->
                        <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-xs font-medium">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'px-3 py-2 flex items-center gap-1.5 transition-colors',
                                    viewMode === 'grid'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
                                ]"
                                title="Compact grid view — fits more employees on screen"
                            >
                                <i class="fas fa-table-cells"></i>
                                Grid
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'px-3 py-2 flex items-center gap-1.5 transition-colors border-l border-gray-300 dark:border-gray-600',
                                    viewMode === 'list'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
                                ]"
                                title="Traditional table view"
                            >
                                <i class="fas fa-list"></i>
                                List
                            </button>
                        </div>

                        <!-- Column count control (grid view only) -->
                        <div v-if="viewMode === 'grid'" class="flex items-center gap-2">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Columns:</label>
                            <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-xs font-medium">
                                <button
                                    v-for="(col, colIndex) in [2, 3, 4, 6]"
                                    :key="col"
                                    @click="gridColumns = col"
                                    :class="[
                                        'px-2.5 py-2 transition-colors',
                                        gridColumns === col
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600',
                                        colIndex !== 0 ? 'border-l border-gray-300 dark:border-gray-600' : ''
                                    ]"
                                >
                                    {{ col }}
                                </button>
                            </div>
                        </div>

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

                        <!-- Auto-refresh controls -->
                        <div class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700">
                            <button
                                @click="toggleAutoRefresh"
                                :title="autoRefreshEnabled ? 'Auto-refresh is on — click to pause' : 'Auto-refresh is paused — click to resume'"
                                class="text-lg leading-none"
                            >
                                <i :class="autoRefreshEnabled ? 'fas fa-toggle-on text-blue-600' : 'fas fa-toggle-off text-gray-400'"></i>
                            </button>
                            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <span v-if="autoRefreshEnabled" class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse mr-1"></span>
                                Updated {{ lastUpdatedDisplay }}
                            </span>
                            <button @click="refreshAll" title="Refresh now" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                <i class="fas fa-rotate"></i>
                            </button>
                        </div>

                        <!-- Print -->
                        <div class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700">
                            <button
                                @click="printChart"
                                class="text-xs px-2.5 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1.5"
                            >
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary strip: quick counts so the user can see totals at a glance -->
                <div v-if="employeesWithStatus.length > 0" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs bg-gray-50/60 dark:bg-gray-800/60 print:hidden">
                    <span class="font-semibold text-gray-600 dark:text-gray-300">{{ employeesWithStatus.length }} Total Employees</span>
                    <span v-for="s in statusSummary" :key="s.status" class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                        <span class="w-2 h-2 rounded-full" :class="dotClass(s.status)"></span>
                        {{ s.status }}: <strong>{{ s.count }}</strong>
                    </span>
                </div>

                <!-- Weekend banner: friendlier messaging than a silently all-"Off Day" list -->
                <div
                    v-if="isSelectedDateWeekend"
                    class="mx-6 mt-4 px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2 print:hidden"
                >
                    <i class="fas fa-mug-hot text-gray-400"></i>
                    It's the weekend — listed staff are marked Off Day.
                </div>

                <!-- ============== GRID VIEW (compact, multi-column, minimal scrolling) ============== -->
                <div v-if="viewMode === 'grid'" class="p-4 print:hidden">
                    <div v-if="employeesWithStatus.length > 0" class="flex flex-col gap-6">
                        <div v-for="group in groupedEmployees" :key="group.status">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="dotClass(group.status)"></span>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ group.status }}</h4>
                                <span class="text-xs text-gray-400 dark:text-gray-500">({{ group.employees.length }})</span>
                            </div>
                            <div
                                class="grid gap-3 locator-grid"
                                :style="{ gridTemplateColumns: `repeat(${gridColumns}, minmax(0, 1fr))` }"
                            >
                                <div
                                    v-for="employee in group.employees"
                                    :key="employee.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 hover:shadow-md transition-shadow flex flex-col gap-2 min-w-0"
                                    :class="recordBgClass(employee.status)"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                            :class="statusBadgeClass(employee.status)"
                                        >
                                            {{ getInitials(employee.name) }}
                                        </span>
                                        <div class="flex-1 flex items-center justify-between gap-2 min-w-0">
                                            <span class="font-semibold text-gray-900 dark:text-white text-base truncate" :title="employee.name">
                                                {{ employee.name }}
                                            </span>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-bold whitespace-nowrap"
                                                :class="statusBadgeClass(employee.status)"
                                            >
                                                <i :class="statusIconClass(employee.status)"></i>
                                                {{ statusShortLabel(employee.status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span v-if="employee.remarks" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium break-words" :class="remarksBadgeClass(employee.status)">
                                            {{ employee.remarks }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Data State -->
                    <div v-else-if="!isSelectedDateWeekend" class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-md">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No employees found</p>
                    </div>
                </div>

                <!-- ============== LIST VIEW (original single-column table) ============== -->
                <div v-else class="overflow-x-auto print:hidden">
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
                            <tr
                                v-for="employee in employeesWithStatus"
                                :key="employee.id"
                                class="transition-colors hover:brightness-95 dark:hover:brightness-110"
                                :class="recordBgClass(employee.status)"
                            >
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-2">
                                        <span
                                            class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0"
                                            :class="statusBadgeClass(employee.status)"
                                        >
                                            {{ getInitials(employee.name) }}
                                        </span>
                                        {{ employee.name }}
                                    </span>
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
                    <div v-if="employeesWithStatus.length === 0 && !isSelectedDateWeekend" class="text-center py-12 bg-gray-50 dark:bg-gray-700">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No employees found</p>
                    </div>
                </div>

                <!-- ============== PRINT-ONLY VIEW (formal government form layout) ============== -->
                <div class="hidden print:block locator-print-area px-8 py-6 text-black">
                    <!-- Header Section with Logos -->
                    <div class="flex items-center justify-center gap-2 pb-2" style="border-bottom: 3px double #050505;">
                        <div style="width: 85px; flex-shrink: 0;">
                            <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                            <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                            <p class="text-lg font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                            <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                        </div>
                        <div style="width: 85px; flex-shrink: 0;">
                            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                        </div>
                    </div>
                    <div class="text-center mb-5 leading-snug">
                        <p class="text-lg font-bold uppercase tracking-wide mt-2">Daily Locator Chart</p>
                        <p class="text-sm mt-1">{{ currentDateDisplay }}</p>
                    </div>

                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="border border-black px-2 py-1.5 text-left w-12">No.</th>
                                <th class="border border-black px-2 py-1.5 text-left">Employee Name</th>
                                <th class="border border-black px-2 py-1.5 text-left w-40">Status</th>
                                <th class="border border-black px-2 py-1.5 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(employee, idx) in employeesWithStatus" :key="`print-${employee.id}`">
                                <td class="border border-black px-2 py-1 align-top">{{ idx + 1 }}</td>
                                <td class="border border-black px-2 py-1 align-top">{{ employee.name }}</td>
                                <td class="border border-black px-2 py-1 align-top">{{ employee.status }}</td>
                                <td class="border border-black px-2 py-1 align-top">{{ employee.remarks || '-' }}</td>
                            </tr>
                            <tr v-if="employeesWithStatus.length === 0">
                                <td colspan="4" class="border border-black px-2 py-3 text-center">No employees found</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="text-xs mt-2">
                        Total: {{ employeesWithStatus.length }}<span v-for="s in statusSummary" :key="`printsum-${s.status}`"> &nbsp;|&nbsp; {{ s.status }}: {{ s.count }}</span>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';

// ============== Data ==============
const employees = ref<Array<{id: number; employee_id: string; name: string; office_id: number; designation: string}>>([]);
const leaves = ref<any[]>([]);
const travelOrders = ref<any[]>([]);
const passSlips = ref<any[]>([]);
const tardiness = ref<any[]>([]);

// Grid = compact multi-column cards (default, minimizes scrolling for large rosters).
// List = original single-column table.
const viewMode = ref<'grid' | 'list'>('grid');

// Number of columns in the Grid view — user-adjustable.
const gridColumns = ref<number>(4);

// The date the Locator Chart is being viewed for — defaults to today,
// but the user can pick a different date to see historical/future records.
const selectedDate = ref<Date>(new Date());

// True once the user has picked a specific date themselves — while false,
// selectedDate is kept in sync with the real "today" (see the date-rollover
// check below), so leaving the chart open overnight rolls it to the new day.
const isDateManuallySet = ref<boolean>(false);

// ============== Auto-refresh ==============
const autoRefreshEnabled = ref<boolean>(true);
const lastUpdatedAt = ref<Date | null>(null);
const REFRESH_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes
let refreshTimer: ReturnType<typeof setInterval> | null = null;

const startAutoRefresh = () => {
    stopAutoRefresh();
    refreshTimer = setInterval(() => {
        refreshAll();
    }, REFRESH_INTERVAL_MS);
};

const stopAutoRefresh = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

const toggleAutoRefresh = () => {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
};

const lastUpdatedDisplay = computed(() => {
    if (!lastUpdatedAt.value) return '—';
    return lastUpdatedAt.value.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
});

// ============== Date rollover ==============

// Checks whether the real-world date has moved past selectedDate and,
// if the user hasn't manually chosen a date, advances selectedDate to
// match — so leaving the chart open across midnight shows the new day
// without needing a manual refresh or navigation.
let dateRolloverTimer: ReturnType<typeof setInterval> | null = null;
const DATE_ROLLOVER_CHECK_MS = 30 * 1000; // 30 seconds

const checkForDateRollover = () => {
    if (isDateManuallySet.value) return;

    const now = new Date();
    const hasDayChanged =
        now.getDate() !== selectedDate.value.getDate() ||
        now.getMonth() !== selectedDate.value.getMonth() ||
        now.getFullYear() !== selectedDate.value.getFullYear();

    if (hasDayChanged) {
        selectedDate.value = now;
    }
};

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
        isDateManuallySet.value = true;
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
    isDateManuallySet.value = false;
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
 * Designations that should always be pinned to the top of the roster,
 * ahead of the alphabetical-by-last-name ordering applied to everyone else.
 */
const PRIORITY_DESIGNATIONS = new Set([
    'Provincial Budget Officer',
    'Provincial Government Department Head (Provincial Budget Officer)'
]);

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
 * Initials for the avatar circle, e.g. "Avelino D. Cayat Jr." -> "AC",
 * skipping generational suffixes so they don't get used as the last initial.
 */
const getInitials = (fullName: string): string => {
    const parts = fullName.trim().split(/\s+/).filter(
        p => !NAME_SUFFIXES.has(p.toLowerCase().replace(/\.$/, ''))
    );
    if (parts.length === 0) return '?';
    const first = parts[0]?.[0] || '';
    const last = parts[parts.length - 1]?.[0] || first;
    return (first + last).toUpperCase();
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
 * Compact badge classes for the grid view cards (same color language as
 * the list view, just reused under a different name for clarity). Also
 * reused for the avatar initials circle.
 */
const statusBadgeClass = (status: string): string => {
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

const statusIconClass = (status: string): string => {
    switch (status) {
        case 'On Leave':
            return 'fas fa-calendar-check';
        case 'On Official Business':
            return 'fas fa-briefcase';
        case 'Undertime':
            return 'fas fa-hourglass-end';
        case 'Off Day':
            return 'fas fa-bed';
        case 'Present':
        default:
            return 'fas fa-check-circle';
    }
};

/**
 * Shorter labels for the compact grid card badges, so the badge doesn't
 * dominate a narrow card. "On Official Business" -> "On Official Business".
 */
const statusShortLabel = (status: string): string => {
    switch (status) {
        case 'On Official Business':
            return 'On Official Business';
        default:
            return status;
    }
};

const dotClass = (status: string): string => {
    switch (status) {
        case 'On Leave':
            return 'bg-orange-500';
        case 'On Official Business':
            return 'bg-blue-500';
        case 'Undertime':
            return 'bg-red-500';
        case 'Off Day':
            return 'bg-gray-500';
        case 'Present':
        default:
            return 'bg-green-500';
    }
};

/**
 * Subtle status-tinted background for a whole record (grid card or table
 * row) — light enough to still read as "white-ish" so it blends with the
 * existing card/table styling rather than competing with the status badge.
 */
const recordBgClass = (status: string): string => {
    switch (status) {
        case 'On Leave':
            return 'bg-orange-50/60 dark:bg-orange-900/10';
        case 'On Official Business':
            return 'bg-blue-50/60 dark:bg-blue-900/10';
        case 'Undertime':
            return 'bg-red-50/60 dark:bg-red-900/10';
        case 'Off Day':
            return 'bg-gray-50/80 dark:bg-gray-700/30';
        case 'Present':
        default:
            return 'bg-green-50/40 dark:bg-green-900/10';
    }
};

/**
 * Fixed display order for status groups in the Grid view.
 */
const STATUS_ORDER = ['On Official Business', 'On Leave', 'Undertime', 'Present', 'Off Day'];

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
 * The Provincial Budget Officer (or department head holding that role)
 * is always pinned first; everyone else follows alphabetically by last name.
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
            const aIsPriority = PRIORITY_DESIGNATIONS.has(a.designation);
            const bIsPriority = PRIORITY_DESIGNATIONS.has(b.designation);

            if (aIsPriority && !bIsPriority) return -1;
            if (!aIsPriority && bIsPriority) return 1;

            const lastNameCompare = getLastName(a.name).localeCompare(getLastName(b.name));
            if (lastNameCompare !== 0) return lastNameCompare;
            return a.name.localeCompare(b.name); // tiebreaker on full name
        });
});

/**
 * Counts per status, used by the summary strip above the chart.
 */
const statusSummary = computed(() => {
    const counts = new Map<string, number>();
    employeesWithStatus.value.forEach(e => {
        counts.set(e.status, (counts.get(e.status) || 0) + 1);
    });
    return Array.from(counts.entries()).map(([status, count]) => ({ status, count }));
});

/**
 * Employees grouped by status, in a fixed display order, for the Grid
 * view's sectioned headers. Relative order within each group is preserved
 * from employeesWithStatus (priority designation first, then last name).
 */
const groupedEmployees = computed(() => {
    return STATUS_ORDER
        .map(status => ({
            status,
            employees: employeesWithStatus.value.filter(e => e.status === status)
        }))
        .filter(group => group.employees.length > 0);
});

// ============== Print ==============

const printChart = () => {
    window.print();
};

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
 * Fetch all data sources and stamp the last-updated time. Used for the
 * initial load, the manual refresh button, the auto-refresh timer, and
 * whenever the selected date changes.
 */
const refreshAll = async () => {
    await Promise.all([
        fetchEmployees(),
        fetchLeaves(),
        fetchTravelOrders(),
        fetchPassSlips(),
        fetchTardiness()
    ]);
    lastUpdatedAt.value = new Date();
};

// Re-fetch everything whenever the viewed date changes — whether the user
// picked a different date or the automatic rollover check above advanced
// it to a new day — so the chart reflects the latest records for that
// date without needing a page reload. Skipped on the very first run since
// onMounted already does the initial fetch.
watch(selectedDate, () => {
    refreshAll();
});

/**
 * Initialize: Fetch all data on mount, then start auto-refresh if enabled.
 */
onMounted(async () => {
    await refreshAll();
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    }
    dateRolloverTimer = setInterval(checkForDateRollover, DATE_ROLLOVER_CHECK_MS);
});

onUnmounted(() => {
    stopAutoRefresh();
    if (dateRolloverTimer) {
        clearInterval(dateRolloverTimer);
        dateRolloverTimer = null;
    }
});
</script>

<style>
/* Printing must show ONLY the government-form sheet above — nothing else
   on the page, including the app's TopNavigation/Header/Footer that live
   outside this component in AuthenticatedLayout. This must be a global
   (non-scoped) style, since scoped styles can't reach ancestors like
   <body> or sibling layout markup outside this component. Hide everything
   on the page by default and re-reveal just the print sheet and its
   descendants; this works no matter what markup wraps it. */
@media print {
    body * {
        visibility: hidden;
    }
    .locator-print-area,
    .locator-print-area * {
        visibility: visible;
    }
    .locator-print-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
    @page {
        margin: 1.5cm;
    }
    table tr {
        break-inside: avoid;
    }
}
</style>