<template>
    <!-- Empty State -->
    <div class="px-6 py-12 text-center">
        <div class="inline-block mb-4">
            <i class="fas fa-history text-gray-400 dark:text-gray-600 text-4xl"></i>
        </div>
        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No activity found</p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
            {{ filtered ? 'Try different filters' : 'Actions performed in the system will appear here' }}
        </p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    filtered: boolean;
}>();
</script>
