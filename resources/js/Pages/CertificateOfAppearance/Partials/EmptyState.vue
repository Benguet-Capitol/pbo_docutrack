<template>
    <div class="px-6 py-12 text-center">
        <div class="inline-block mb-4">
            <i class="fas fa-file-certificate text-gray-400 dark:text-gray-600 text-4xl"></i>
        </div>
        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">{{ title }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ message }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    message: string;
}>();
</script>
