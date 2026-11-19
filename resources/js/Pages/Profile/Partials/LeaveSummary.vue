<script setup lang="ts">
import { computed } from 'vue';

// The signed-in user's leave balances, as in the Dashboard's My Summary; the records themselves are in My Filings below it
const props = defineProps<{
    summary: any;
}>();

// 2 -> "2", 2.5 -> "2.5"
const formatDays = (days: number | null | undefined) => String(Math.round((days ?? 0) * 10) / 10);

const MAIN_TYPES: Record<string, string> = {
    'Vacation Leave': 'fas fa-umbrella-beach text-sky-600 dark:text-sky-400',
    'Sick Leave': 'fas fa-notes-medical text-rose-600 dark:text-rose-400',
};
const limitedTypes = computed(() => new Set((props.summary?.limited_leaves ?? []).map((l: any) => l.type)));
// Vacation and Sick first, then anything else used that has no yearly limit (those show as tiles)
const otherUsed = computed(() => (props.summary?.used_by_type ?? [])
    .filter((row: any) => !limitedTypes.value.has(row.type))
    .sort((a: any, b: any) => (MAIN_TYPES[b.type] ? 1 : 0) - (MAIN_TYPES[a.type] ? 1 : 0)));

type Credit = { type: string; limit: number; used: number; remaining: number };
type Status = 'ok' | 'low' | 'out';
// Plenty left (green), running low (amber), exhausted (red): the same thresholds as My Summary
const status = (c: Credit): Status => (c.remaining <= 0 ? 'out' : c.remaining <= c.limit * 0.4 ? 'low' : 'ok');
const BAR: Record<Status, string> = { ok: 'bg-emerald-500', low: 'bg-amber-500', out: 'bg-red-500' };
const LABEL: Record<Status, string> = { ok: 'Available', low: 'Running low', out: 'Exhausted' };
const TAG: Record<Status, string> = {
    ok: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    low: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    out: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
};
const NUMBER: Record<Status, string> = { ok: 'text-emerald-600 dark:text-emerald-400', low: 'text-amber-600 dark:text-amber-400', out: 'text-red-600 dark:text-red-400' };
const barWidth = (c: Credit) => (c.limit ? Math.min(100, Math.round((c.used / c.limit) * 100)) : 0);

// One tile and one sub-heading look for the whole panel (My Filings uses the same sub-heading)
const TILE = 'relative overflow-hidden rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 shadow-sm px-4 py-3.5 flex flex-col';
const SUBHEAD = 'flex items-center gap-2 mb-2.5 text-[11px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300';
const SUBHEAD_ICON = 'text-emerald-600 dark:text-emerald-400';
</script>

<template>
    <section class="grid grid-cols-1 xl:grid-cols-4 gap-5">
        <!-- The highlight: leaves with a yearly limit, how many days are left -->
        <div class="xl:col-span-3">
            <h4 :class="SUBHEAD"><i class="fas fa-hourglass-half" :class="SUBHEAD_ICON"></i>Leaves with yearly limits</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div v-for="credit in summary.limited_leaves" :key="credit.type" :class="TILE">
                    <span class="absolute inset-y-0 left-0 w-1" :class="BAR[status(credit)]"></span>
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ credit.type }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap" :class="TAG[status(credit)]">{{ LABEL[status(credit)] }}</span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-bold leading-none tabular-nums" :class="NUMBER[status(credit)]">{{ formatDays(credit.remaining) }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ credit.remaining === 1 ? 'day left' : 'days left' }}</span>
                    </div>
                    <div class="mt-3 h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="BAR[status(credit)]" :style="{ width: `${barWidth(credit)}%` }"></div>
                    </div>
                    <span class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">{{ formatDays(credit.used) }} of {{ credit.limit }} days used</span>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400"><i class="fas fa-circle-info mr-1"></i>Disapproved leaves still count against these limits, as when filing.</p>
        </div>

        <!-- Vacation, Sick and any other leave used, in the same tiles -->
        <div>
            <h4 :class="SUBHEAD"><i class="fas fa-list-check" :class="SUBHEAD_ICON"></i>Other leaves used</h4>
            <div class="grid grid-cols-2 xl:grid-cols-1 gap-3">
                <div v-for="row in otherUsed" :key="row.type" :class="TILE" class="!py-3">
                    <span class="absolute inset-y-0 left-0 w-1 bg-emerald-600 dark:bg-emerald-400"></span>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200"><i v-if="MAIN_TYPES[row.type]" class="mr-1.5" :class="MAIN_TYPES[row.type]"></i>{{ row.type }}</span>
                        <span class="text-right"><span class="text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ formatDays(row.days) }}</span> <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ row.days === 1 ? 'day' : 'days' }}</span></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
