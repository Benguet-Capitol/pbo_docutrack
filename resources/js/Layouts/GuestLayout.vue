<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
</script>

<template>
    <div class="flex flex-col h-screen overflow-hidden bg-gradient-to-br from-gray-50 via-emerald-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <main class="flex-1 overflow-hidden">
            <slot />
        </main>
        <Footer />
    </div>
</template>
