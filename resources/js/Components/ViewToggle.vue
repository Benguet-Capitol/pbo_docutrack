<template>
    <!-- List/Card switch: hidden on small screens, where card view is forced -->
    <div class="hidden sm:inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden flex-shrink-0">
        <button
            type="button"
            class="px-3 py-2 text-xs transition-colors"
            :class="modelValue === 'table' ? activeClass : inactiveClass"
            title="List View"
            @click="$emit('update:modelValue', 'table')"
        >
            <i class="fas fa-list"></i>
        </button>
        <button
            type="button"
            class="px-3 py-2 text-xs transition-colors border-l border-gray-300 dark:border-gray-600"
            :class="modelValue === 'card' ? activeClass : inactiveClass"
            title="Card View"
            @click="$emit('update:modelValue', 'card')"
        >
            <i class="fas fa-th-large"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import type { ViewMode } from '@/Composables/useResponsiveView';

defineProps<{
    modelValue: ViewMode;
}>();

defineEmits<{
    'update:modelValue': [value: ViewMode];
}>();

const activeClass = 'bg-emerald-600 dark:bg-emerald-700 text-white';
const inactiveClass = 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600';
</script>
