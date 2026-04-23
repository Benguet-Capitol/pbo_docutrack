<script setup lang="ts">
import type { CertificateOfAppearance } from '../Composables/useCoaData';

const props = defineProps<{
    show: boolean;
    certificate: CertificateOfAppearance | null;
    formattedDate: (dateStr: string) => string;
}>();

defineEmits<{
    close: [];
}>();

const printPreview = () => {
    window.print();
};
</script>

<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-certificate text-emerald-600 dark:text-emerald-400"></i>
                            Certificate Preview
                        </h3>
                        <div class="flex gap-3 items-center">
                            <button
                                @click="printPreview"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 font-medium text-sm transition-colors"
                            >
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                            <button
                                @click="$emit('close')"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Certificate Content -->
                    <div class="px-8 py-8 bg-white dark:bg-gray-900 overflow-y-auto" style="max-height: calc(90vh - 140px);">
                <div v-if="certificate" class="space-y-6">
                    <!-- Title -->
                    <div class="text-center border-b pb-6 dark:border-gray-700">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">CERTIFICATE OF APPEARANCE</h2>
                    </div>

                    <!-- Details -->
                    <div class="space-y-4 text-sm">
                        <!-- Control Number -->
                        <div class="flex justify-between items-start">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Control No:</span>
                            <span class="text-gray-900 dark:text-white font-mono">{{ certificate.control_no }}</span>
                        </div>

                        <!-- Date -->
                        <div class="flex justify-between items-start">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Date:</span>
                            <span class="text-gray-900 dark:text-white">{{ formattedDate(certificate.date) }}</span>
                        </div>

                        <!-- Name -->
                        <div class="flex justify-between items-start">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Name:</span>
                            <span class="text-gray-900 dark:text-white">{{ certificate.name }}</span>
                        </div>

                        <!-- Office -->
                        <div class="flex justify-between items-start">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Office:</span>
                            <span class="text-gray-900 dark:text-white">{{ certificate.office }}</span>
                        </div>

                        <!-- Purpose -->
                        <div class="border-t dark:border-gray-700 pt-4">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Purpose:</span>
                            <p class="text-gray-900 dark:text-white mt-2 whitespace-pre-wrap">{{ certificate.purpose }}</p>
                        </div>

                        <!-- Remarks -->
                        <div v-if="certificate.remarks" class="border-t dark:border-gray-700 pt-4">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Remarks:</span>
                            <p class="text-gray-900 dark:text-white mt-2 whitespace-pre-wrap">{{ certificate.remarks }}</p>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="border-t dark:border-gray-700 pt-8 mt-8">
                        <div class="flex justify-between">
                            <div class="text-center">
                                <div class="h-20 border-b-2 border-gray-400 dark:border-gray-600 mb-2"></div>
                                <p class="text-xs text-gray-700 dark:text-gray-400 font-medium">Issued By</p>
                            </div>
                            <div class="text-center">
                                <div class="h-20 border-b-2 border-gray-400 dark:border-gray-600 mb-2"></div>
                                <p class="text-xs text-gray-700 dark:text-gray-400 font-medium">Signature</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </Transition>
    </Teleport>
</template>

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

@media print {
    body {
        background: white;
    }
    .fixed,
    .sticky,
    button {
        display: none !important;
    }
    .bg-black {
        background: none !important;
    }
    .rounded-lg {
        border-radius: 0;
    }
}
</style>
