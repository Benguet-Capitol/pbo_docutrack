<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-hourglass-half text-emerald-600 dark:text-emerald-400"></i>
                            Time Slip Preview
                        </h3>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1 text-xs bg-white/60 dark:bg-gray-800/60 rounded-lg p-1">
                                <button
                                    type="button"
                                    @click="paperSize = 'short'"
                                    :class="paperSize === 'short' ? 'bg-emerald-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-md font-medium transition-colors"
                                >
                                    8.5 x 11 (Short)
                                </button>
                                <button
                                    type="button"
                                    @click="paperSize = 'long'"
                                    :class="paperSize === 'long' ? 'bg-emerald-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-md font-medium transition-colors"
                                >
                                    8.5 x 13 (Long)
                                </button>
                            </div>
                            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body - Document Preview -->
                    <!-- The whole sheet, its margins inside (like the Leave preview); only this page is printed -->
                    <div class="p-4 bg-gray-100 dark:bg-gray-900 overflow-x-auto">
                        <div
                            id="preview-content"
                            class="mx-auto flex flex-col bg-white shadow"
                            data-print-page-only
                            data-page-width-in="8.5"
                            :data-page-height-in="paperSize === 'long' ? 13 : 11"
                            data-page-margin-in="0"
                            :style="{ boxSizing: 'border-box', width: '8.5in', minHeight: paperSize === 'long' ? '13in' : '11in', padding: '0.5in' }"
                        >
                            <!-- Header Section with Logos -->
                            <div class="flex items-center justify-center gap-2 pb-2" style="border-bottom: 3px double #050505;">
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                                    <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                    <p class="text-lg font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                                    <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                                </div>
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                                </div>
                            </div>
                            
                            <div class="text-left">
                                <p class="text-base font-semibold text-blue-700">PBO COPY</p>
                            </div>
                            
                            <div>
                                <p class="font-bold text-2xl text-center text-gray-900 mb-4">DTR TIME IN/OUT FORM</p>
                            </div>

                            <!-- Date & Control Numbers -->
                            <div class="mb-1 flex justify-end pr-8">
                                <div class="text-center text-sm">
                                    <p><span class="w-26 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                                    <p class="mt-1"><span class="w-26 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formatDate(formData.date) }}</span></p>
                                </div>
                            </div>

                            <!-- Requesting Employee -->
                            <div class="space-y-2 text-base mb-4">
                                <div class="space-y-1">
                                    <div class="flex gap-2 items-start">
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold uppercase block">{{ getEmployeeNameById(formData.requesting_employee_id) }}</span>
                                            <span class="text-xs text-gray-700">Requesting Employee</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Time Fields & Details Section -->
                            <div class="space-y-4 text-sm mb-4">
                                <!-- Time Information Grid -->
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">In AM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_in_am ? formatTime(formData.time_in_am) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Out AM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_out_am ? formatTime(formData.time_out_am) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">In PM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_in_pm ? formatTime(formData.time_in_pm) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Out PM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_out_pm ? formatTime(formData.time_out_pm) : '—' }}</p>
                                    </div>
                                </div>

                                <!-- Reason -->
                                <div>
                                    <p class="text-xs font-medium text-gray-600 mb-1">Reason:</p>
                                    <p class="text-sm text-gray-900 whitespace-pre-wrap border-b border-gray-900 pb-2">{{ formData.reason }}</p>
                                </div>
                            </div>

                            <!-- Certified By & Approved By (Inline) -->
                            <div v-if="(getCertifiedBySignatory() && formData.certified_by_employee_id) || getApprovedBySignatory()" class="mb-6 flex items-start justify-between pr-8">
                                <!-- Generated On -->
                                <div class="self-end text-left" style="font-size: 9px; color: #555;">
                                    <p class="m-0">Generated on {{ printGeneratedAtDisplay() }}</p>
                                    <p class="m-0 font-bold">PBO|DocuTrack</p>
                                </div>

                                <!-- Certified By -->
                                <div v-if="getCertifiedBySignatory() && formData.certified_by_employee_id">
                                    <p class="text-sm text-gray-900 mb-8">CERTIFIED BY:</p>
                                    <div class="w-72 text-center">
                                        <p class="font-bold text-base text-center text-gray-900 w-72 uppercase border-b border-gray-900 pb-1">{{ getCertifiedBySignatory()?.name }}</p>
                                        <p class="text-sm text-center text-gray-700 w-72">{{ getCertifiedBySignatory()?.designation }}</p>
                                    </div>
                                </div>

                                <!-- Approved By -->
                                <div v-if="getApprovedBySignatory()">
                                    <p class="text-sm text-gray-900 mb-8">APPROVED BY:</p>
                                    <div class="w-72 text-center">
                                        <p class="text-sm text-center text-gray-700"></p>
                                        <p class="font-bold text-base text-center text-gray-900 w-72 uppercase border-b border-gray-900 pb-1">{{ getApprovedBySignatory()?.name }}</p>
                                        <p class="text-sm text-center text-gray-700">{{ getApprovedBySignatory()?.designation }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider Border -->
                            <div class="mt-4" style="border-top: 3px dotted #050505;"></div>

                            <!-- Second Copy - Header Section with Logos -->
                            <div class="flex items-center justify-center gap-2 pb-2" style="border-bottom: 3px double #050505;">
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-gray-700 mt-8">Republic of the Philippines</p>
                                    <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                    <p class="text-lg font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                                    <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                                </div>
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                                </div>
                            </div>
                            
                            <div class="text-left">
                                <p class="text-base font-semibold text-blue-700">PHRMDO COPY</p>
                            </div>
                            
                            <div>
                                <p class="font-bold text-2xl text-center text-gray-900 mb-4">DTR TIME IN/OUT FORM</p>
                            </div>

                            <!-- Date & Control Numbers - Copy 2 -->
                            <div class="mb-1 flex justify-end pr-8">
                                <div class="text-center text-sm">
                                    <p><span class="w-26 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                                    <p class="mt-1"><span class="w-26 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formatDate(formData.date) }}</span></p>
                                </div>
                            </div>

                            <!-- Requesting Employee - Copy 2 -->
                            <div class="space-y-2 text-base mb-4">
                                <div class="space-y-1">
                                    <div class="flex gap-2 items-start">
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold uppercase block">{{ getEmployeeNameById(formData.requesting_employee_id) }}</span>
                                            <span class="text-xs text-gray-700">Requesting Employee</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Time Fields & Details Section - Copy 2 -->
                            <div class="space-y-4 text-sm mb-4">
                                <!-- Time Information Grid -->
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">In AM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_in_am ? formatTime(formData.time_in_am) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Out AM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_out_am ? formatTime(formData.time_out_am) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">In PM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_in_pm ? formatTime(formData.time_in_pm) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 mb-1">Out PM</p>
                                        <p class="border-b border-gray-900 text-center font-semibold" style="min-height: 24px;">{{ formData.time_out_pm ? formatTime(formData.time_out_pm) : '—' }}</p>
                                    </div>
                                </div>

                                <!-- Reason - Copy 2 -->
                                <div>
                                    <p class="text-xs font-medium text-gray-600 mb-1">Reason:</p>
                                    <p class="text-sm text-gray-900 whitespace-pre-wrap border-b border-gray-900 pb-2">{{ formData.reason }}</p>
                                </div>
                            </div>

                            <!-- Certified By & Approved By (Inline) - Copy 2 -->
                            <div v-if="(getCertifiedBySignatory() && formData.certified_by_employee_id) || getApprovedBySignatory()" class="mb-6 flex items-start justify-between pr-8">
                                <!-- Generated On -->
                                <div class="self-end text-left" style="font-size: 9px; color: #555;">
                                    <p class="m-0">Generated on {{ printGeneratedAtDisplay() }}</p>
                                    <p class="m-0 font-bold">PBO|DocuTrack</p>
                                </div>

                                <!-- Certified By -->
                                <div v-if="getCertifiedBySignatory() && formData.certified_by_employee_id">
                                    <p class="text-sm text-gray-900 mb-8">CERTIFIED BY:</p>
                                    <div class="w-72 text-center">
                                        <p class="font-bold text-base text-center text-gray-900 w-72 uppercase border-b border-gray-900 pb-1">{{ getCertifiedBySignatory()?.name }}</p>
                                        <p class="text-sm text-center text-gray-700 w-72">{{ getCertifiedBySignatory()?.designation }}</p>
                                    </div>
                                </div>

                                <!-- Approved By -->
                                <div v-if="getApprovedBySignatory()">
                                    <p class="text-sm text-gray-900 mb-8">APPROVED BY:</p>
                                    <div class="w-72 text-center">
                                        <p class="text-sm text-center text-gray-700"></p>
                                        <p class="font-bold text-base text-center text-gray-900 w-72 uppercase border-b border-gray-900 pb-1">{{ getApprovedBySignatory()?.name }}</p>
                                        <p class="text-sm text-center text-gray-700 w-72">{{ getApprovedBySignatory()?.designation }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
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

<script setup lang="ts">
import { isProvincialBudgetOfficer, isProvincialGovernor } from '@/Composables/useOfficials';
import { ref } from 'vue';
import { printSafely } from '@/Composables/usePrint';
import { resolveApprovedBy } from '@/Composables/useSignatory';

const paperSize = ref<'short' | 'long'>('long');

const props = defineProps<{
    show: boolean;
    formData: any;
    employees: any[];
    sortedEmployees: any[];    isPreviewFromTable?: boolean;}>();

defineEmits<{
    close: [];
    confirm: [];
}>();

const getEmployeeNameById = (id: number | null): string => {
    if (!id) return '—';
    const employee = props.sortedEmployees?.find((emp: any) => emp.id === id);
    return employee?.name || '—';
};

const getEmployeeDesignation = (id: number | null): string => {
    if (!id) return '';
    const employee = props.sortedEmployees?.find((emp: any) => emp.id === id);
    return employee?.designation || '';
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    
    try {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    } catch {
        return timeStr;
    }
};

const getCertifiedBySignatory = () => {
    return props.sortedEmployees.find((e: any) => e.id === props.formData.certified_by_employee_id);
};

const isRequestingEmployeeProvincialBudgetOfficer = (): boolean => {
    const requestingEmployee = props.sortedEmployees.find((e: any) => e.id === props.formData.requesting_employee_id);
    return isProvincialBudgetOfficer(requestingEmployee);
};

const getProvincialGovernor = () => {
    return props.sortedEmployees.find((e: any) => isProvincialGovernor(e));
};

const getProvincialBudgetOfficer = () => {
    return props.sortedEmployees.find((e: any) => isProvincialBudgetOfficer(e));
};

const getApprovedBySignatory = () => {
    // If requesting employee is Provincial Budget Officer, approver is Provincial Governor
    if (isRequestingEmployeeProvincialBudgetOfficer()) {
        return getProvincialGovernor();
    }
    // Otherwise, the signatory chosen on the form (OIC / Acting PBO), defaulting to the Provincial Budget Officer
    const approvedBy = resolveApprovedBy(
        props.sortedEmployees,
        props.formData.approved_by_employee_id,
        props.formData.approved_by_title,
        getProvincialBudgetOfficer()?.designation || 'Provincial Budget Officer',
    );
    return approvedBy.name ? { name: approvedBy.name, designation: approvedBy.title } : null;
};

const printPreview = () => {
    printSafely();
};

const printGeneratedAtDisplay = (): string => {
    const now = new Date();
    return now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        + ' at '
        + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
};
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
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
