<template>
    <!-- Why the form's Save/Create button is disabled -->
    <p
        v-if="fields.length > 0 || message"
        class="w-full text-center text-[11px] text-amber-700 dark:text-amber-400"
        role="status"
    >
        <i class="fas fa-info-circle mr-1"></i>
        <template v-if="fields.length > 0">Complete the required fields: {{ fields.join(', ') }}</template>
        <template v-else>{{ message }}</template>
    </p>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    /** Labels of required fields that are still empty */
    fields?: string[];
    /** Other reason the form can't be submitted (shown when no fields are missing) */
    message?: string | null;
}>(), {
    fields: () => [],
    message: null,
});
</script>
