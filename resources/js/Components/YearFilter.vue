<template>
    <!-- Year Filter: which year's records to show (default: current year) -->
    <div class="relative flex items-center flex-shrink-0">
        <i class="far fa-calendar absolute left-3 text-gray-400 text-xs pointer-events-none"></i>
        <select
            :value="modelValue === null ? '' : String(modelValue)"
            @change="onChange(($event.target as HTMLSelectElement).value)"
            aria-label="Year"
            title="Year"
            class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg pl-8 pr-8 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer w-full sm:w-auto"
        >
            <option v-for="year in years" :key="year" :value="String(year)">{{ year }}</option>
            <option value="">All Years</option>
        </select>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: number | null;
    years: number[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const onChange = (value: string) => {
    emit('update:modelValue', value === '' ? null : Number(value));
};
</script>
