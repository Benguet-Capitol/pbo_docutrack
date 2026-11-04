<template>
    <!-- A labelled value on a card; "stat" makes it a key figure (used inside CARD_STATS) -->
    <div class="min-w-0">
        <div class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <i v-if="icon" :class="[icon, 'text-[10px]']"></i>
            {{ label }}
        </div>
        <div class="mt-0.5 break-words" :class="stat ? 'text-sm font-bold text-gray-900 dark:text-gray-100' : 'text-gray-800 dark:text-gray-200'">
            <slot>{{ value || '—' }}</slot>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    label: string;
    icon?: string;
    value?: string | number | null;
    stat?: boolean;
}>();
</script>
