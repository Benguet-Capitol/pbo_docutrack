<template>
    <!-- Sync from HRIS confirmation -->
    <Teleport to="body" v-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="!syncing && $emit('close')">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-teal-50 to-teal-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-rotate text-teal-600 dark:text-teal-400"></i>
                        Sync from HRIS
                    </h3>
                    <button :disabled="syncing" @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors disabled:opacity-50">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5 text-xs text-gray-700 dark:text-gray-300 space-y-2">
                    <p>This brings in every <strong>Permanent</strong>, <strong>Casual</strong> and <strong>COS</strong> employee from HRIS:</p>
                    <ul class="list-disc ml-5 space-y-1">
                        <li>adds employees not here yet;</li>
                        <li>updates names, positions, status and office of employees already here (by Employee ID);</li>
                        <li>keeps an employee whose HRIS ID changed (e.g. casual to permanent) as the same record, with their leaves and slips;</li>
                        <li>leaves deleted employees as they are.</li>
                    </ul>
                    <p class="text-gray-500 dark:text-gray-400">It can take a minute. This sync also runs automatically on the 1st of every month.</p>
                </div>

                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="$emit('confirm')"
                        :disabled="syncing"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <i :class="syncing ? 'fas fa-spinner fa-spin' : 'fas fa-rotate'"></i>
                        {{ syncing ? 'Syncing...' : 'Sync now' }}
                    </button>
                    <button
                        @click="$emit('close')"
                        :disabled="syncing"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 disabled:opacity-50"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
defineProps<{
    show: boolean;
    syncing: boolean;
}>();

defineEmits<{
    close: [];
    confirm: [];
}>();
</script>
