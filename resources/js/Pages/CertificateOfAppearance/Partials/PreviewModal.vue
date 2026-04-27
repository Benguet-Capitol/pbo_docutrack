<script setup lang="ts">
import type { CertificateOfAppearance } from '../Composables/useCoaData';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
}

const props = defineProps<{
    show: boolean;
    formData: any;
    isPreviewFromTable?: boolean;
    formattedDate: (dateStr: string) => string;
    sortedEmployees?: Employee[];
}>();

defineEmits<{
    confirm: [];
    close: [];
}>();

const printPreview = () => {
    window.print();
};

const getProvincialBudgetOfficer = () => {
    if (!props.sortedEmployees) return null;
    return props.sortedEmployees.find((e: Employee) => 
        e.designation?.toLowerCase().includes('provincial budget officer')
    );
};
</script>

<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-location-dot text-emerald-600 dark:text-emerald-400"></i>
                            Certificate of Appearance Preview
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Certificate Content -->
                    <div class="p-4 flex flex-col" style="background-color: white; min-height: 1000px;">
                        <div v-if="formData" class="space-y-6">
                            <!-- Header Section with Logos -->
                            <div class="flex items-center justify-center gap-2 mb-6 pb-2" style="border-bottom: 4px double #050505;">
                                <div style="width: 100px; flex-shrink: 0;">
                                    <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-semibold text-gray-900 mt-2">Republic of the Philippines</p>
                                    <p class="text-lg font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                    <p class="text-2xl font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                                    <p class="text-lg text-gray-900 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                                </div>
                                <div style="width: 100px; flex-shrink: 0;">
                                    <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                                </div>
                            </div>
                            
                            <div>
                                <p class="font-bold text-3xl text-center text-gray-900 mb-10">CERTIFICATE OF APPEARANCE</p>
                            </div>
                            
                            <!-- Date & Control Numbers -->
                            <div class="mb-8 flex justify-start pr-8">
                                <div class="text-center text-base">
                                    <p><span class="w-26 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                                    <p class="mt-1 mb-8"><span class="w-26 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formattedDate(formData.date) }}</span></p>
                                </div>
                            </div>

                            <!-- Details Body -->
                            <div class="space-y-4 text-lg mb-10">
                                <p class="text-justify indent-8">This is to certify that <span class="font-semibold uppercase">{{ formData.name }}</span>, of <span class="font-semibold uppercase">{{ formData.office }}</span>, has appeared in this office on <span class="font-semibold">{{ formattedDate(formData.date) }}</span> on Official Business for the purpose of <span class="font-semibold">{{ formData.purpose }}</span>.</p>
                                
                                <!-- Closing Statement -->
                                <p class="text-justify indent-8">This certification is issued upon the request of the above-named person for whatever legal purpose it may serve.</p>
                                
                                <!-- Issued Statement -->
                                <p class="text-justify indent-8 mb-10">Issued this <span class="font-semibold">{{ formattedDate(formData.date) }}</span> at <span class="font-semibold">La Trinidad, Benguet</span>.</p>
                            </div>

                            <!-- Signature Section -->
                            <div class="mt-12 mb-10">
                                <div class="mt-8 flex justify-end pr-8">
                                    <div class="w-72 text-center">
                                        <p class="text-lg text-gray-700 mt-12"></p>
                                        <p class="text-lg text-center text-gray-900 border-b border-gray-900 pb-1 font-bold uppercase">{{ getProvincialBudgetOfficer()?.name || 'PROVINCIAL BUDGET OFFICER' }}</p>
                                        <p class="text-lg text-center text-gray-900 ">{{ getProvincialBudgetOfficer()?.designation || 'Provincial Budget Officer' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Section -->
                        <div class="mb-6 mt-auto" style="border-top: 4px double #050505;">
                            <div class="flex justify-between items-center mt-2" style="line-height: 1;">
                                <p><span class="font-semibold text-sm">PBO Telephone No.:</span> <span class="text-sm text-gray-900">(074) 422-1378, Local: 134</span></p>
                                <p><span class="font-semibold text-sm">Website:</span> <span class="text-sm font-semibold text-blue-800 underline">www.benguet.gov.ph</span></p>
                            </div>
                            <p><span class="font-semibold text-sm">Email Address:</span> <span class="text-sm font-semibold text-blue-800 underline">benguetpbo@benguet.gov.ph</span></p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0 print:hidden">
                        <button @click="printPreview" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <template v-if="!isPreviewFromTable">
                            <button @click="$emit('confirm')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-check"></i>
                                Confirm & Save
                            </button>
                            <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-arrow-left"></i>
                                Back to Form
                            </button>
                        </template>
                        <template v-else>
                            <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                                <i class="fas fa-times"></i>
                                Close
                            </button>
                        </template>
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
    .sticky {
        position: static !important;
    }
    
    .sticky.top-0 {
        display: none !important;
    }
    
    .sticky.bottom-0 {
        display: none !important;
    }
    
    .overflow-y-auto {
        overflow: visible !important;
    }
    
    .max-h-\[90vh\] {
        max-height: none !important;
    }
    
    body, html {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .fixed.inset-0.flex.items-center.justify-center {
        align-items: flex-start !important;
        padding-top: 0 !important;
    }

    /* Remove shadows from printed form */
    .shadow-2xl {
        box-shadow: none !important;
    }
    
    [class*="shadow"] {
        box-shadow: none !important;
    }
}

</style>
