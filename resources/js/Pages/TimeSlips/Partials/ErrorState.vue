<template>
    <div class="px-6 py-12 text-center">
        <i class="fas fa-exclamation-triangle text-red-300 dark:text-red-600 text-5xl mb-4 block"></i>
        <h3 class="text-lg font-semibold text-red-700 dark:text-red-300 mb-2">Error Loading Time Slips</h3>
        <p class="text-sm text-red-500 dark:text-red-400">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    error: string;
}>();
</script>
