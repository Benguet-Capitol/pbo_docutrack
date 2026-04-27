<template>
    <div class="px-6 py-12 text-center">
        <i class="fas fa-inbox text-gray-300 dark:text-gray-600 text-5xl mb-4 block mx-auto"></i>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ title }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ message }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    message: string;
}>();
</script>
