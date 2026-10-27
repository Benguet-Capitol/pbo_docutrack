<template>
    <!-- System status in the page header, right side (roles with "See backup and HRIS sync status"): quiet when all
         is well, red when a job failed or hasn't succeeded on time (e.g. the scheduler stopped). Details on hover. -->
    <ul
        v-if="jobs.length"
        class="flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-x-5 gap-y-1 text-xs"
        :class="problems.length ? 'rounded-md border border-red-300 bg-red-50 px-3 py-1.5 dark:border-red-800 dark:bg-red-900/20' : ''"
    >
        <li v-for="job in jobs" :key="job.job" class="flex items-center gap-1.5" :title="job.message || ''">
            <i :class="[STATUS[job.status].icon, STATUS[job.status].color]"></i>
            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ job.label }}:</span>
            <span :class="STATUS[job.status].color">{{ statusText(job) }}</span>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

interface JobStatus {
    job: string;
    label: string;
    status: 'ok' | 'failed' | 'overdue' | 'never';
    last_run_at: string | null;
    last_success_at: string | null;
    message: string | null;
}

const STATUS: Record<JobStatus['status'], { icon: string; color: string }> = {
    ok: { icon: 'fas fa-check-circle', color: 'text-emerald-600 dark:text-emerald-400' },
    failed: { icon: 'fas fa-times-circle', color: 'text-red-600 dark:text-red-400' },
    overdue: { icon: 'fas fa-clock', color: 'text-red-600 dark:text-red-400' },
    never: { icon: 'fas fa-question-circle', color: 'text-amber-600 dark:text-amber-400' },
};

const jobs = ref<JobStatus[]>([]);
const problems = computed(() => jobs.value.filter((job) => job.status === 'failed' || job.status === 'overdue'));

const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';

const statusText = (job: JobStatus): string => {
    switch (job.status) {
        case 'ok': return `last ran ${when(job.last_success_at)}`;
        case 'failed': return `failed ${when(job.last_run_at)}` + (job.last_success_at ? ` · last success ${when(job.last_success_at)}` : '');
        case 'overdue': return `no success since ${when(job.last_success_at)} — check that the scheduler is running`;
        default: return 'has not run yet';
    }
};

onMounted(async () => {
    try {
        const response = await fetch('/api/system-status', { headers: { Accept: 'application/json' } });
        if (response.ok) jobs.value = await response.json();
    } catch {
        // Status is a convenience: the Dashboard works without it
    }
});
</script>
