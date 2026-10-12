<template>
    <!-- Permanent Delete Modal: requires Administrator/Developer password -->
    <Teleport to="body" v-if="show && items.length > 0">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-lg mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                        Delete Permanently
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="onConfirm">
                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-4">
                        <p class="text-sm text-gray-900 dark:text-gray-100">
                            <template v-if="items.length === 1">
                                Permanently delete <span class="font-semibold">{{ items[0].label }}</span> ({{ items[0].module_label }})?
                            </template>
                            <template v-else>
                                Permanently delete <span class="font-semibold">{{ items.length }} items</span>?
                            </template>
                        </p>
                        <p class="text-xs text-red-700 dark:text-red-400 font-medium">
                            This action cannot be undone. The {{ items.length === 1 ? 'record' : 'records' }} will be removed from the database.
                        </p>

                        <!-- Cascade warnings -->
                        <div v-if="warnings.length" class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 max-h-32 overflow-y-auto">
                            <p v-for="warning in warnings" :key="warning.key" class="text-xs text-amber-800 dark:text-amber-300">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span class="font-semibold">{{ warning.label }}:</span> {{ warning.text }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="trash-password" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Enter your Administrator/Developer password to confirm
                            </label>
                            <div class="relative">
                                <input
                                    id="trash-password"
                                    ref="passwordInput"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    class="w-full border rounded-lg px-3 py-2 pr-10 text-sm dark:bg-gray-700 dark:text-white focus:outline-none"
                                    :class="error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-emerald-500'"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    tabindex="-1"
                                >
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <p v-if="error" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ error }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            type="submit"
                            :disabled="processing || !password"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            <i :class="processing ? 'fas fa-spinner fa-spin' : 'fas fa-trash-alt'"></i>
                            Delete Permanently
                        </button>
                        <button
                            type="button"
                            @click="onClose"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { itemKey } from '../Composables/useTrashData';
import type { TrashItem } from '../Composables/useTrashData';

const props = defineProps<{
    show: boolean;
    items: TrashItem[];
    processing: boolean;
    error: string | null;
}>();

const emit = defineEmits<{
    'close': [];
    'confirm': [password: string];
}>();

const password = ref('');
const showPassword = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const warnings = computed(() =>
    props.items
        .filter((item) => item.warning)
        .map((item) => ({ key: itemKey(item), label: item.label, text: item.warning as string }))
);

// Reset the password each time the modal opens
watch(
    () => props.show,
    async (show) => {
        password.value = '';
        showPassword.value = false;
        if (show) {
            await nextTick();
            passwordInput.value?.focus();
        }
    }
);

const onClose = () => {
    if (!props.processing) emit('close');
};

const onConfirm = () => {
    if (password.value && !props.processing) {
        emit('confirm', password.value);
    }
};
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
