<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
        v-model="model"
        ref="input"
    />
</template>
