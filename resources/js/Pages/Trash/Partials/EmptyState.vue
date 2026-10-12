<template>
    <!-- Empty State -->
    <div class="px-6 py-12 text-center">
        <div class="inline-block mb-4">
            <i class="fas fa-trash text-gray-400 dark:text-gray-600 text-4xl"></i>
        </div>
        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">{{ filtered ? 'No matching items' : 'Trash is empty' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
            {{ filtered ? 'Try a different search or module filter' : 'Deleted records from every module will appear here' }}
        </p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    filtered: boolean;
}>();
</script>
