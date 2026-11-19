<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import LeaveSummary from './LeaveSummary.vue';
import MyFilings from './MyFilings.vue';
import SectionHeader from './SectionHeader.vue';

// One panel for the user's own records: the leave balances above the records by type (My Filings), one year for both
const props = defineProps<{
    leaves: { summary: any } | null;
    filings: { records: any } | null;
    employee: { employee_id: string; name: string; position_title: string | null; parenthetical_title: string | null } | null;
}>();

// "Provincial Government Department Head (Provincial Budget Officer)", as HRIS has it
const position = computed(() => [props.employee?.position_title, props.employee?.parenthetical_title && `(${props.employee.parenthetical_title})`].filter(Boolean).join(' '));
const initials = computed(() => {
    const words = String(props.employee?.name ?? '').replace(/[.,]/g, ' ').split(/\s+/).filter(Boolean);
    return ((words[0]?.[0] ?? '') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase() || '?';
});

const summary = computed(() => props.leaves?.summary ?? null);
const year = computed<number>(() => summary.value?.year ?? props.filings?.records?.year ?? new Date().getFullYear());
// Years with records, and always this year (to file in it)
const years = computed(() => [...new Set<number>([new Date().getFullYear(), year.value, ...(summary.value?.years ?? [])])].sort((a, b) => b - a));

// 2 -> "2", 2.5 -> "2.5"
const formatDays = (days: number | null | undefined) => String(Math.round((days ?? 0) * 10) / 10);

const loading = ref(false);
function changeYear(event: Event) {
    router.get(route('profile.edit'), { year: (event.target as HTMLSelectElement).value }, {
        only: ['leaves', 'filings'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
}
</script>

<template>
    <section class="relative">
        <div v-if="loading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-800/50"></div>

        <!-- Who: the employee, as on an ID card -->
        <div v-if="employee" class="flex items-center gap-4 rounded-md border border-emerald-200 dark:border-emerald-900/60 border-l-4 border-l-emerald-600 dark:border-l-emerald-400 bg-emerald-50/60 dark:bg-gray-900/40 px-5 py-4 mb-6">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-emerald-700 dark:bg-emerald-600 text-white text-base font-bold tracking-wide ring-4 ring-emerald-100 dark:ring-emerald-900/60">{{ initials }}</div>
            <div class="min-w-0">
                <p class="text-[10.5px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Provincial Budget Office</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-100 break-words">{{ employee.name }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                    <span class="break-words"><i class="fas fa-briefcase mr-1.5 text-emerald-600 dark:text-emerald-400"></i>{{ position || '—' }}</span>
                    <span v-if="employee.employee_id" class="inline-flex items-center gap-1.5 rounded border border-emerald-200 dark:border-emerald-900/60 bg-white dark:bg-gray-800 px-2 py-0.5">
                        <i class="fas fa-id-card text-emerald-600 dark:text-emerald-400"></i>
                        <span class="text-gray-500 dark:text-gray-400">ID No.</span>
                        <b class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ employee.employee_id }}</b>
                    </span>
                </div>
            </div>
        </div>

        <!-- Section: title, year, total days used -->
        <SectionHeader icon="fa-calendar-check" title="My Leaves and Filings" description="File and edit your own leaves, pass slips, time slips and tardiness/undertime here.">
            <div class="flex items-stretch gap-3">
                <label class="flex flex-col justify-center rounded border border-gray-200 dark:border-gray-700 px-3 py-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Year</span>
                    <select :value="year" class="border-0 bg-transparent p-0 pr-6 text-sm font-semibold text-gray-900 dark:text-white dark:bg-gray-800 focus:ring-0" :disabled="loading" @change="changeYear">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </label>
                <div v-if="summary" class="flex flex-col justify-center rounded border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-1.5 text-right">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Leave days used</span>
                    <span class="text-2xl font-bold leading-none tabular-nums text-emerald-700 dark:text-emerald-400">{{ formatDays(summary.total_days_used) }}</span>
                </div>
            </div>
        </SectionHeader>

        <LeaveSummary v-if="summary" :summary="summary" />

        <div v-if="filings?.records" :class="summary ? 'mt-6 border-t border-gray-100 dark:border-gray-700 pt-5' : ''">
            <MyFilings :records="filings.records" :travel-orders="summary?.travel_orders ?? []" />
        </div>
    </section>
</template>
