<template>
    <button
        type="button"
        class="relative p-2 rounded-lg transition-all duration-200 group"
        :class="colorClasses[color]"
    >
        <i :class="icon"></i>
        <span
            v-if="tooltip"
            class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20"
        >{{ tooltip }}</span>
    </button>
</template>

<script setup lang="ts">
type Color = 'blue' | 'red' | 'purple' | 'emerald' | 'cyan' | 'green';

withDefaults(defineProps<{
    icon: string;
    color?: Color;
    tooltip?: string;
}>(), {
    color: 'blue',
    tooltip: '',
});

const colorClasses: Record<Color, string> = {
    blue: 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50',
    red: 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50',
    purple: 'text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/50',
    emerald: 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50',
    cyan: 'text-cyan-600 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-900/30 hover:text-cyan-700 dark:hover:text-cyan-300 hover:bg-cyan-200 dark:hover:bg-cyan-900/50',
    green: 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50',
};
</script>
