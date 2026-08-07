<script setup lang="ts">
import { computed } from 'vue';

const currentYear = computed(() => new Date().getFullYear());
</script>

<template>
    <footer class="flex-shrink-0 px-4 sm:px-6 py-4 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <p class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-0.5 sm:gap-1 text-center text-xs sm:text-sm text-gray-600 dark:text-gray-400">
            <span>© {{ currentYear }} HEY! Sam.. | PBO Document Tracking System |</span>
            <a
                href="https://benguet.gov.ph/provincial-budget-office/"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-600 hover:underline dark:text-blue-400"
            >
                PBO - Province of Benguet
            </a>
        </p>
    </footer>
</template>