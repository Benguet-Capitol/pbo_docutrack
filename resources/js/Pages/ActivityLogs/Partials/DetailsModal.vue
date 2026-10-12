<template>
    <!-- Activity Details Modal -->
    <Teleport to="body" v-if="log">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-history text-emerald-600 dark:text-emerald-400"></i>
                        Activity Details
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-5 overflow-y-auto space-y-5">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ log.description }}</p>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Date &amp; Time</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ log.created_at }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Action</dt>
                            <dd>
                                <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-medium', actionMeta(log.action).classes]">
                                    <i :class="actionMeta(log.action).icon"></i>
                                    {{ actionMeta(log.action).label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">User</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ log.user_name || 'System' }}<span v-if="log.usertype" class="text-gray-500"> ({{ log.usertype }})</span></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Module</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ log.module }}</dd>
                        </div>
                        <div v-if="log.subject_label">
                            <dt class="text-gray-500 dark:text-gray-400">Record</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ log.subject_label }}<span v-if="log.subject_id" class="text-gray-500"> (ID {{ log.subject_id }})</span></dd>
                        </div>
                        <div v-if="log.ip_address">
                            <dt class="text-gray-500 dark:text-gray-400">IP Address</dt>
                            <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ log.ip_address }}</dd>
                        </div>
                    </dl>

                    <!-- Changes (updates) -->
                    <div v-if="changes.length">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300 mb-2">Changes</h4>
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-100 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-1/4">Field</th>
                                        <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">Old Value</th>
                                        <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">New Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="change in changes" :key="change.field">
                                        <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">{{ change.field }}</td>
                                        <td class="px-3 py-2 text-red-700 dark:text-red-400 break-all">{{ change.old }}</td>
                                        <td class="px-3 py-2 text-emerald-700 dark:text-emerald-400 break-all">{{ change.new }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Snapshot (create / delete) -->
                    <div v-if="attributes.length">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300 mb-2">Record Data</h4>
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="w-full text-xs text-left">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="attribute in attributes" :key="attribute.field">
                                        <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300 w-1/3 bg-gray-50 dark:bg-gray-900">{{ attribute.field }}</td>
                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100 break-all">{{ attribute.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center p-4 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="$emit('close')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200"
                    >
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { actionMeta } from '../Composables/useActivityLogData';
import type { ActivityLog } from '../Composables/useActivityLogData';

const props = defineProps<{
    log: ActivityLog | null;
}>();

defineEmits<{
    'close': [];
}>();

const fieldLabel = (key: string) => key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
};

const changes = computed(() => {
    const oldValues = props.log?.properties?.old ?? {};
    const newValues = props.log?.properties?.new ?? {};
    return Object.keys(newValues).map((key) => ({
        field: fieldLabel(key),
        old: formatValue(oldValues[key]),
        new: formatValue(newValues[key]),
    }));
});

const attributes = computed(() => {
    const values = props.log?.properties?.attributes ?? {};
    return Object.entries(values)
        .filter(([key]) => key !== 'id')
        .map(([key, value]) => ({ field: fieldLabel(key), value: formatValue(value) }));
});
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
