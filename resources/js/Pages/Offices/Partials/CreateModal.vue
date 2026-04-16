<template>
    <!-- Create Office Modal -->
    <Teleport to="body" v-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-building text-emerald-600 dark:text-emerald-400"></i>
                        Create Office
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Office Name Field -->
                        <div class="space-y-2">
                            <label for="office_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office Name</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-qrcode absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.office_name"
                                    id="office_name"
                                    type="text"
                                    placeholder="Office Name"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.office_name" class="text-red-500 text-xs">{{ formErrors.office_name }}</span>
                        </div>

                        <!-- Abbreviation and Sub Office -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label for="office_abbreviation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Abbreviation</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.office_abbreviation"
                                        id="office_abbreviation"
                                        type="text"
                                        placeholder="Abbreviation"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.office_abbreviation" class="text-red-500 text-xs">{{ formErrors.office_abbreviation }}</span>
                            </div>
                            <div class="space-y-2">
                                <label for="sub_office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Sub Office</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-window-restore absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.sub_office"
                                        id="sub_office"
                                        type="text"
                                        placeholder="Sub Office"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Fund Field -->
                        <div class="space-y-2">
                            <label for="fund" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Fund</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-money-bill absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.fund"
                                    id="fund"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option value="">Select Fund</option>
                                    <option value="General Fund">General Fund</option>
                                    <option value="Provincial Development Fund">Provincial Development Fund</option>
                                    <option value="Benguet General Hospital Economic Enterprise">Benguet General Hospital Economic Enterprise</option>
                                    <option value="Special Education Fund">Special Education Fund</option>
                                </select>
                            </div>
                            <span v-if="formErrors.fund" class="text-red-500 text-xs">{{ formErrors.fund }}</span>
                        </div>

                        <!-- FPP Code and Responsibility Code -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label for="fpp_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">FPP Code</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-invoice absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.fpp_code"
                                        id="fpp_code"
                                        type="text"
                                        placeholder="FPP Code"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="responsibility_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Responsibility Code</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-lines absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.responsibility_code"
                                        id="responsibility_code"
                                        type="text"
                                        placeholder="Responsibility Code"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Branch -->
                        <div class="space-y-2">
                            <label for="branch" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Branch</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-sitemap absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.branch"
                                    id="branch"
                                    type="text"
                                    placeholder="Branch"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.branch" class="text-red-500 text-xs">{{ formErrors.branch }}</span>
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
import type { FormData } from '../Composables/useOfficeForm';

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
