<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
</script>

<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

    </AuthenticatedLayout>
</template>
