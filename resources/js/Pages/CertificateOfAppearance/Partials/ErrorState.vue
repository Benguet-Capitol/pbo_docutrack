<template>
    <div class="px-6 py-12 text-center">
        <div class="inline-block p-4 bg-red-100 dark:bg-red-900/30 rounded-lg">
            <i class="fas fa-exclamation-triangle text-3xl text-red-600 dark:text-red-400"></i>
        </div>
        <p class="mt-4 text-gray-600 dark:text-gray-400 font-medium">An error occurred</p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
interface Props {
    error: string;
}

withDefaults(defineProps<Props>(), {});
</script>
