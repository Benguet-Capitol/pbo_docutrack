<template>
    <!-- Placeholder Preview Modal -->
    <div v-if="show"></div>
</template>

<script setup lang="ts">
defineProps<{
    show: boolean;
}>();

defineEmits<{
    'close': [];
}>();
</script>
