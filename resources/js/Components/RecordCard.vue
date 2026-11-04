<template>
    <!-- One card of a module's card view: header band (what it is), body (the details), footer (who/when and the actions) -->
    <article
        class="flex flex-col rounded-xl border border-t-4 text-xs shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
        :class="[
            accentClasses.border,
            selected
                ? 'bg-emerald-50 dark:bg-emerald-900/20 border-x-emerald-400 border-b-emerald-400 dark:border-x-emerald-600 dark:border-b-emerald-600'
                : 'bg-white dark:bg-gray-800 border-x-gray-200 border-b-gray-200 dark:border-x-gray-700 dark:border-b-gray-700',
        ]"
    >
        <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-2.5 dark:border-gray-700" :class="accentClasses.band">
            <slot name="leading" />
            <slot name="icon">
                <div v-if="icon" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" :class="iconClass || accentClasses.icon">
                    <i :class="icon"></i>
                </div>
            </slot>
            <div class="min-w-0 flex-1">
                <h4 class="break-words text-sm font-bold leading-tight text-gray-900 dark:text-gray-100"><slot name="title">{{ title }}</slot></h4>
                <p v-if="date || $slots.subtitle" class="mt-0.5 break-words text-[11px] text-gray-600 dark:text-gray-300" :title="dateLabel">
                    <slot name="subtitle"><i class="far fa-calendar mr-1 text-[10px]"></i>{{ date }}</slot>
                </p>
            </div>
            <div v-if="$slots.aside" class="flex-shrink-0 self-start"><slot name="aside" /></div>
        </header>

        <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <slot />
        </div>

        <footer
            v-if="$slots.meta || $slots.actions"
            class="flex items-center justify-between gap-2 rounded-b-xl border-t border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="min-w-0"><slot name="meta" /></div>
            <div class="flex flex-shrink-0 flex-wrap justify-end gap-2"><slot name="actions" /></div>
        </footer>
    </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';

export type CardAccent = 'blue' | 'cyan' | 'emerald' | 'gray' | 'green' | 'orange' | 'purple' | 'red' | 'yellow';

// Written out in full so Tailwind generates them
const ACCENTS: Record<CardAccent, { border: string; band: string; icon: string }> = {
    blue: { border: 'border-t-blue-500', band: 'bg-blue-50 dark:bg-blue-900/20', icon: 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300' },
    cyan: { border: 'border-t-cyan-500', band: 'bg-cyan-50 dark:bg-cyan-900/20', icon: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/60 dark:text-cyan-300' },
    emerald: { border: 'border-t-emerald-500', band: 'bg-emerald-50 dark:bg-emerald-900/20', icon: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' },
    gray: { border: 'border-t-gray-400', band: 'bg-gray-50 dark:bg-gray-700/30', icon: 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' },
    green: { border: 'border-t-green-500', band: 'bg-green-50 dark:bg-green-900/20', icon: 'bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-300' },
    orange: { border: 'border-t-orange-500', band: 'bg-orange-50 dark:bg-orange-900/20', icon: 'bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300' },
    purple: { border: 'border-t-purple-500', band: 'bg-purple-50 dark:bg-purple-900/20', icon: 'bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300' },
    red: { border: 'border-t-red-500', band: 'bg-red-50 dark:bg-red-900/20', icon: 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300' },
    yellow: { border: 'border-t-yellow-500', band: 'bg-yellow-50 dark:bg-yellow-900/20', icon: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/60 dark:text-yellow-300' },
};

const props = withDefaults(defineProps<{
    accent?: CardAccent;
    icon?: string;
    /** Replaces the accent's colours on the icon (e.g. an activity's own colour) */
    iconClass?: string;
    title?: string;
    date?: string;
    /** What the date is, on hover (e.g. "Filing Date") */
    dateLabel?: string;
    /** Highlighted (ticked, or its details are open) */
    selected?: boolean;
}>(), {
    accent: 'gray',
    selected: false,
});

const accentClasses = computed(() => ACCENTS[props.accent]);
</script>
