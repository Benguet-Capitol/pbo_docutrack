<template>
    <div class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Records</h3>
                <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    error: string | null;
}>();
</script>
