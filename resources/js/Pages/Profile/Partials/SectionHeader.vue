<script setup lang="ts">
// The one header every Profile card uses (as on pbo-registryV2's profile): icon tile, title and a line of help, with room on the right (e.g. the year)
defineProps<{
    icon: string;
    title: string;
    description?: string;
}>();
</script>

<template>
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-3 min-w-0">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400">
                <i class="fas text-sm" :class="icon"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-base font-semibold leading-tight text-gray-900 dark:text-gray-100">{{ title }}</h2>
                <p v-if="description || $slots.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400"><slot name="description">{{ description }}</slot></p>
            </div>
        </div>
        <div v-if="$slots.default" class="shrink-0"><slot /></div>
    </header>
</template>
