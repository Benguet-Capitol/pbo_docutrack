<template>
    <div class="flex flex-col items-center justify-center py-12 px-4">
        <div class="text-center">
            <i class="fas fa-exclamation-circle text-4xl text-red-600 dark:text-red-400 mb-4"></i>
            <p class="text-red-700 dark:text-red-300 font-semibold mb-2">Error Loading Leaves</p>
            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ error }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    error: string;
}>();
</script>
