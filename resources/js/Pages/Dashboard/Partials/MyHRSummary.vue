<template>
    <!-- My Summary: the signed-in employee's own leaves (the highlight), travel orders, pass slips and tardiness/undertime
         for a year (for roles without "View all employees' leaves") -->
    <section class="w-full bg-white dark:bg-gray-800 rounded-lg shadow relative overflow-hidden">
        <div v-if="loading" class="absolute inset-0 z-10 bg-gradient-to-r from-transparent via-white/30 to-transparent dark:via-gray-600/30"></div>

        <!-- Header: who and which year (white, with an emerald accent on the left) -->
        <div class="border-l-4 border-emerald-500 border-b border-b-gray-200 dark:border-b-gray-700 px-4 sm:px-6 py-5 bg-white dark:bg-gray-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white text-base font-bold tracking-wide ring-4 ring-emerald-100 dark:ring-emerald-900/50" :title="userName">
                        {{ userInitials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">My Summary</p>
                        <h3 class="text-lg font-semibold leading-tight text-gray-900 dark:text-white truncate">{{ userName || 'My records' }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Your leaves, travel orders, pass slips and tardiness/undertime in {{ year }}</p>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Year
                    <select
                        :value="year"
                        @change="$emit('update:year', Number(($event.target as HTMLSelectElement).value))"
                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 cursor-pointer"
                    >
                        <option v-for="option in years" :key="option" :value="option">{{ option }}</option>
                    </select>
                </label>
            </div>
        </div>

        <p v-if="!employeeId" class="px-6 py-10 text-center text-sm text-amber-700 dark:text-amber-400">
            <i class="fas fa-user-lock mr-1"></i> Your account isn't linked to an employee yet. Ask an administrator to link it in Users.
        </p>

        <div v-else class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-5 bg-gray-50/60 dark:bg-gray-800">
            <!-- ================= Leaves: the highlight ================= -->
            <div class="lg:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-600 bg-white dark:bg-gray-800 shadow-sm flex flex-col">
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-emerald-50/60 dark:bg-emerald-900/10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">Leaves</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ myLeaves.length }} {{ myLeaves.length === 1 ? 'leave' : 'leaves' }} filed in {{ year }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 leading-none">{{ formatDays(totalLeaveDays) }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">days used</div>
                    </div>
                </div>

                <div class="p-5 flex flex-col gap-5">
                    <!-- The highlight: leaves with a yearly limit, how many days are left -->
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Leaves with yearly limits</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                v-for="credit in creditRows"
                                :key="credit.type"
                                class="relative overflow-hidden rounded-lg border bg-white dark:bg-gray-900/40 p-4 flex flex-col"
                                :class="tileBorder(credit)"
                            >
                                <span class="absolute inset-y-0 left-0 w-1" :class="barColor(credit)"></span>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ credit.type }}</span>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-4xl font-bold leading-none" :class="tileNumber(credit)">{{ formatDays(credit.remaining) }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ credit.remaining === 1 ? 'day left' : 'days left' }}</span>
                                </div>
                                <div class="mt-3 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full transition-all" :class="barColor(credit)" :style="{ width: `${barWidth(credit)}%` }"></div>
                                </div>
                                <span class="mt-1.5 text-[11px]" :class="credit.remaining === 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                                    {{ credit.remaining === 0 ? `Exhausted · all ${credit.limit} used` : `${formatDays(credit.limit - credit.remaining)} of ${credit.limit} used` }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Secondary: Vacation, Sick and any other leave filed this year -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Other leaves used</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="tile in mainTiles" :key="tile.type" class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200">
                                <i :class="[tile.icon, tile.color]"></i>{{ tile.type }}
                                <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ formatDays(tile.used) }}</span>
                            </span>
                            <span v-for="row in otherRows" :key="row.type" class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200">
                                {{ row.type }} <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ formatDays(row.used) }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= Other records: compact ================= -->
            <div class="flex flex-col gap-4">
                <div v-for="card in sideCards" :key="card.key" class="rounded-lg border border-gray-200 dark:border-gray-700 border-t-4 bg-white dark:bg-gray-800 shadow-sm" :class="card.accent">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700" :class="card.strip">
                        <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                            <span class="flex h-7 w-7 items-center justify-center rounded-md text-xs" :class="card.badge"><i :class="card.icon"></i></span>
                            {{ card.title }}
                        </h4>
                        <span class="text-xl font-bold" :class="card.countColor">{{ card.items.length }}</span>
                    </div>
                    <div class="px-4 py-3">
                        <p v-if="card.note" class="mb-2 text-[11px] text-gray-500 dark:text-gray-400">{{ card.note }}</p>
                        <ul v-if="card.items.length" class="space-y-2 text-xs">
                            <li v-for="item in card.items.slice(0, LIST_SIZE)" :key="item.id" class="border-l-2 pl-2" :class="card.line">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ item.when }}</span>
                                <span class="block text-gray-500 dark:text-gray-400 truncate" :title="item.what">{{ item.what }}</span>
                            </li>
                            <li v-if="card.items.length > LIST_SIZE" class="text-gray-400 italic">and {{ card.items.length - LIST_SIZE }} more</li>
                        </ul>
                        <p v-else class="text-xs text-gray-400 italic">None in {{ year }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { LEAVE_LIMITS, LIMITED_LEAVE_TYPES, leaveDays, formatDays } from '@/Composables/useLeaveCredits';

const props = defineProps<{
    /** The signed-in user's employee (users.employee_id) */
    employeeId: number | null;
    year: number;
    years: number[];
    /** Leaves that proceeded (the user's own: the API returns only those) */
    leaves: any[];
    /** All the user's leaves, disapproved ones included: they still use the yearly limits */
    allLeaves: any[];
    travelOrders: any[];
    passSlips: any[];
    tardiness: any[];
    loading?: boolean;
}>();

defineEmits<{
    'update:year': [year: number];
}>();

const LIST_SIZE = 3;

/** The signed-in user's name and initials for the header: "Samuel B. Soliman" -> "SS" (middle initial and Jr./III left out) */
const page = usePage();
const userName = computed(() => String((page.props.auth as any)?.user?.name || ''));
const userInitials = computed(() => {
    const words = userName.value
        .replace(/,/g, ' ')
        .split(/\s+/)
        .filter((word) => word && !/^(jr|sr|ii|iii|iv|v)\.?$/i.test(word));
    if (!words.length) return '?';
    const first = words[0][0];
    const last = words.length > 1 ? words[words.length - 1][0] : '';
    return (first + last).toUpperCase();
});

const yearOf = (value: string | null | undefined): number | null => {
    const year = Number(String(value || '').slice(0, 4));
    return year > 0 ? year : null;
};
const mine = (record: any) => props.employeeId !== null && Number(record?.employee_id) === props.employeeId;
const inYear = (value: string | null | undefined) => yearOf(value) === props.year;
const newestFirst = (a: string, b: string) => String(b || '').localeCompare(String(a || ''));

// ---- Leaves: Vacation and Sick Leave always, the limited types with their remaining days, then any other type filed
const myLeaves = computed(() => props.leaves.filter((leave) => mine(leave) && inYear(leave.date_of_filing)));
const daysByType = (list: any[]): Record<string, number> => list.reduce<Record<string, number>>((map, leave) => {
    map[leave.type_of_leave] = (map[leave.type_of_leave] || 0) + leaveDays(leave);
    return map;
}, {});
const usedByType = computed(() => daysByType(myLeaves.value));
const countedByType = computed(() => daysByType(props.allLeaves.filter((leave) => mine(leave) && inYear(leave.date_of_filing))));
const totalLeaveDays = computed(() => Object.values(usedByType.value).reduce((sum, days) => sum + days, 0));

const leaveRows = computed(() => {
    const rows: Array<{ type: string; used: number; limit?: number; remaining?: number }> = [];
    for (const type of ['Vacation Leave', 'Sick Leave']) rows.push({ type, used: usedByType.value[type] || 0 });
    for (const type of LIMITED_LEAVE_TYPES) {
        const limit = LEAVE_LIMITS[type];
        rows.push({ type, used: usedByType.value[type] || 0, limit, remaining: Math.max(0, limit - (countedByType.value[type] || 0)) });
    }
    const shown = new Set(rows.map((row) => row.type));
    for (const [type, used] of Object.entries(usedByType.value)) {
        if (!shown.has(type)) rows.push({ type, used });
    }
    return rows;
});

/** Vacation and Sick Leave tiles, the limited types as credit bars, the rest as chips */
const mainTiles = computed(() => [
    { type: 'Vacation Leave', used: usedByType.value['Vacation Leave'] || 0, icon: 'fas fa-umbrella-beach', color: 'text-sky-600 dark:text-sky-400' },
    { type: 'Sick Leave', used: usedByType.value['Sick Leave'] || 0, icon: 'fas fa-notes-medical', color: 'text-rose-600 dark:text-rose-400' },
]);
const creditRows = computed(() => leaveRows.value.filter((row) => row.limit !== undefined) as Array<{ type: string; used: number; limit: number; remaining: number }>);
const otherRows = computed(() => leaveRows.value.filter((row) => row.limit === undefined && !['Vacation Leave', 'Sick Leave'].includes(row.type)));
/** Share of the yearly limit taken (disapproved leaves included, as in the days left) */
const barWidth = (credit: { limit: number; remaining: number }) => Math.min(100, Math.round(((credit.limit - credit.remaining) / credit.limit) * 100));
/** Status of a limited leave: plenty left (green), running low (amber), exhausted (red) */
const creditStatus = (credit: { limit: number; remaining: number }): 'ok' | 'low' | 'out' =>
    credit.remaining === 0 ? 'out' : credit.remaining <= credit.limit * 0.4 ? 'low' : 'ok';
const barColor = (credit: { limit: number; remaining: number }) =>
    ({ ok: 'bg-emerald-500', low: 'bg-amber-500', out: 'bg-red-500' })[creditStatus(credit)];
const tileBorder = (credit: { limit: number; remaining: number }) =>
    ({ ok: 'border-emerald-200 dark:border-emerald-800', low: 'border-amber-300 dark:border-amber-700', out: 'border-red-300 dark:border-red-700' })[creditStatus(credit)];
const tileNumber = (credit: { limit: number; remaining: number }) =>
    ({ ok: 'text-emerald-600 dark:text-emerald-400', low: 'text-amber-600 dark:text-amber-400', out: 'text-red-600 dark:text-red-400' })[creditStatus(credit)];

// ---- Travel orders, pass slips, tardiness/undertime of the year, newest first
const myTravelOrders = computed(() =>
    props.travelOrders
        .filter((to) => (to.employees || []).some((emp: any) => Number(emp.id) === props.employeeId) && inYear(to.date))
        .sort((a, b) => newestFirst(a.date, b.date))
);
const myPassSlips = computed(() =>
    props.passSlips
        .filter((ps) => (ps.employees || []).some((emp: any) => Number(emp.id) === props.employeeId) && inYear(ps.date))
        .sort((a, b) => newestFirst(a.date, b.date))
);
const myTardiness = computed(() =>
    props.tardiness
        .filter((tu) => mine(tu) && inYear(tu.requested_date))
        .sort((a, b) => newestFirst(a.requested_date, b.requested_date))
);
const tardyCount = computed(() => myTardiness.value.filter((tu) => String(tu.type || '').toLowerCase().startsWith('tard')).length);

/** Travel orders, pass slips and tardiness/undertime as compact cards */
const sideCards = computed(() => [
    {
        key: 'travel-orders', title: 'Travel Orders', icon: 'fas fa-route',
        accent: 'border-t-orange-500', strip: 'bg-orange-50/60 dark:bg-orange-900/10', badge: 'bg-orange-500 text-white', countColor: 'text-orange-600 dark:text-orange-400', line: 'border-orange-300 dark:border-orange-700',
        note: '',
        items: myTravelOrders.value.map((to) => ({ id: to.id, when: datesText(to.inclusive_dates, to.date), what: to.going_to || '—' })),
    },
    {
        key: 'pass-slips', title: 'Pass Slips', icon: 'fas fa-id-card',
        accent: 'border-t-blue-500', strip: 'bg-blue-50/60 dark:bg-blue-900/10', badge: 'bg-blue-500 text-white', countColor: 'text-blue-600 dark:text-blue-400', line: 'border-blue-300 dark:border-blue-700',
        note: '',
        items: myPassSlips.value.map((ps) => ({ id: ps.id, when: dateText(ps.date) + (ps.requested_time ? ` · ${timeText(ps.requested_time)}` : ''), what: ps.location || ps.purpose || '—' })),
    },
    {
        key: 'tardiness', title: 'Tardiness/Undertime', icon: 'fas fa-hourglass-half',
        accent: 'border-t-red-500', strip: 'bg-red-50/60 dark:bg-red-900/10', badge: 'bg-red-500 text-white', countColor: 'text-red-600 dark:text-red-400', line: 'border-red-300 dark:border-red-700',
        note: `${tardyCount.value} tardiness · ${myTardiness.value.length - tardyCount.value} undertime`,
        items: myTardiness.value.map((tu) => ({ id: tu.id, when: `${dateText(tu.requested_date)} · ${tu.type}`, what: tu.reason || '—' })),
    },
]);

// ---- Display
const dateText = (value: string | null | undefined): string => {
    if (!value) return '—';
    const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);
    return y && m && d ? new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : String(value);
};
const datesText = (entries: string[] | null | undefined, fallback?: string): string => {
    const list = (entries || []).filter(Boolean);
    if (!list.length) return dateText(fallback);
    return list.map((entry) => entry.split(' - ').map((part) => dateText(part.trim())).join(' – ')).join(', ');
};
const timeText = (value: string): string => {
    const [h, m] = String(value).split(':').map(Number);
    if (Number.isNaN(h)) return value;
    return `${((h + 11) % 12) + 1}:${String(m || 0).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
};
</script>
