<template>
    <div class="flex flex-col items-center justify-center py-12 px-4">
        <div class="text-center">
            <i class="fas fa-inbox text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-700 dark:text-gray-300 font-semibold mb-1">{{ title }}</p>
            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ message }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    message: string;
}>();
</script>
