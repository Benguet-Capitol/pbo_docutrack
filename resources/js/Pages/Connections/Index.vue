<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Connections
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-t-4 border-emerald-500">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        The systems DocuTrack connects to, tested from this server, and this server's setup. Keys are never shown. Checked {{ checkedAt }}.
                    </p>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-60"
                        :disabled="checking"
                        @click="checkAgain"
                    >
                        <i class="fas" :class="checking ? 'fa-spinner fa-spin' : 'fa-rotate'"></i> Check again
                    </button>
                </div>

                <h3 :class="SECTION">Outside systems</h3>
                <Deferred data="checks">
                    <template #fallback><Loading /></template>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="check in checks" :key="check.name" class="px-6 py-4 border-l-4 text-xs" :class="STATUS[check.status].classes">
                            <div class="flex items-start gap-3">
                                <i class="fas text-lg mt-0.5" :class="STATUS[check.status].icon"></i>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ check.name }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold" :class="STATUS[check.status].badge">{{ STATUS[check.status].label }}</span>
                                        <span v-if="check.ms !== null" class="text-[11px] text-gray-500 dark:text-gray-400 tabular-nums">{{ check.ms }} ms</span>
                                    </div>
                                    <p class="mt-1 text-gray-800 dark:text-gray-200 break-words">{{ check.message }}</p>
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Used for: {{ check.used_for }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400 break-all">
                                        <span v-if="check.url">Address: <span class="font-mono">{{ check.url }}</span> · </span>Settings: <span class="font-mono">{{ check.settings }}</span>
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </Deferred>

                <h3 :class="[SECTION, 'border-t border-gray-200 dark:border-gray-700']">This server</h3>
                <Deferred data="serverChecks">
                    <template #fallback><Loading /></template>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="check in serverChecks" :key="check.name" class="flex items-start gap-3 px-6 py-3 border-l-4 text-xs" :class="STATUS[check.status].classes">
                            <i class="fas text-base mt-0.5" :class="STATUS[check.status].icon"></i>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ check.name }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold" :class="STATUS[check.status].badge">{{ STATUS[check.status].label }}</span>
                                </div>
                                <p class="mt-0.5 text-gray-800 dark:text-gray-200 break-words">{{ check.message }}</p>
                            </div>
                        </li>
                    </ul>
                </Deferred>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { h, ref } from 'vue';
import { Deferred, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';

// System → Connections (as in pbo-registryV2): HRIS, the shared APIs for pbo-registryV2 and this server's setup,
// checked each time the page opens or on "Check again"

type Status = 'ok' | 'error' | 'warning' | 'not_configured';

interface Check {
    name: string;
    status: Status;
    message: string;
    used_for: string;
    url: string | null;
    settings: string;
    ms: number | null;
}

interface ServerCheck {
    name: string;
    status: Status;
    message: string;
}

defineProps<{
    checks?: Check[]; // deferred
    serverChecks?: ServerCheck[]; // deferred
    checkedAt: string;
}>();

const SECTION = 'px-6 pt-4 pb-1 text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400';

const STATUS: Record<Status, { label: string; icon: string; classes: string; badge: string }> = {
    ok: { label: 'Working', icon: 'fa-circle-check', classes: 'border-emerald-500 text-emerald-700 dark:text-emerald-400', badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' },
    error: { label: 'Problem', icon: 'fa-circle-xmark', classes: 'border-red-500 text-red-700 dark:text-red-400', badge: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' },
    warning: { label: 'Check', icon: 'fa-triangle-exclamation', classes: 'border-amber-500 text-amber-700 dark:text-amber-400', badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' },
    not_configured: { label: 'Not set up', icon: 'fa-circle-minus', classes: 'border-gray-400 text-gray-600 dark:text-gray-400', badge: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
};

// Shown while a section's checks run
const Loading = () => h('p', { class: 'px-6 py-6 text-xs text-gray-500 dark:text-gray-400' }, [h('i', { class: 'fas fa-spinner fa-spin mr-2' }), 'Checking…']);

const checking = ref(false);
const checkAgain = () => {
    router.reload({
        only: ['checks', 'serverChecks', 'checkedAt'],
        onStart: () => (checking.value = true),
        onFinish: () => (checking.value = false),
    });
};
</script>
