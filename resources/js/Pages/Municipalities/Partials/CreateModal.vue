<template>
    <!-- Create Municipality Modal -->
    <Teleport to="body" v-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-city text-emerald-600 dark:text-emerald-400"></i>
                        Create Municipality
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Municipality Name Field -->
                        <div class="space-y-2">
                            <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Name</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-city absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.name"
                                    id="name"
                                    type="text"
                                    placeholder="Municipality Name"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                        </div>

                        <!-- Code Field -->
                        <div class="space-y-2">
                            <label for="code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Code</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.code"
                                    id="code"
                                    type="text"
                                    placeholder="Municipality Code"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.code" class="text-red-500 text-xs">{{ formErrors.code }}</span>
                        </div>

                        <!-- Municipality Class Field -->
                        <div class="space-y-2">
                            <label for="municipality_class" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipality Class</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-tag absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.city_class"
                                    id="city_class"
                                    type="text"
                                    placeholder="e.g., 1st Class, 2nd Class"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Municipal Budget Officer Field -->
                        <div class="space-y-2">
                            <label for="municipal_budget_officer" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Municipal Budget Officer</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user-tie absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.municipal_budget_officer"
                                    id="municipal_budget_officer"
                                    type="text"
                                    placeholder="Budget Officer Name"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Representative Field -->
                        <div class="space-y-2">
                            <label for="representative" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Representative</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-person-booth absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.representative"
                                    id="representative"
                                    type="text"
                                    placeholder="Representative Name"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="onSave"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { FormData } from '../Composables/useMunicipalityForm';

defineProps({
    showCreateModal: {
        type: Boolean,
        required: true,
    },
    formData: {
        type: Object as () => FormData,
        required: true,
    },
    formErrors: {
        type: Object as () => Record<string, string>,
        required: true,
    },
});

const emit = defineEmits<{
    'close': [];
    'save': [];
}>();

const onClose = () => {
    emit('close');
};

const onSave = () => {
    emit('save');
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
