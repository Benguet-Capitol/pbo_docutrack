<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="relative w-full max-w-7xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10 print:hidden">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                        Request for Undertime Preview
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Preview Content - Two Column Layout -->
                <div id="preview-content" class="grid grid-cols-2 gap-4 p-4" style="background-color: white;">
                    <!-- Copy 1 -->
                    <div>
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-4 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-semibold text-gray-700 mb-1">Republic of the Philippines</p>
                                <p class="text-xs font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-base font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-xs text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                        <div>
                            <p class="font-bold text-lg text-center text-gray-900 mb-6">REQUEST FOR UNDERTIME</p>
                        </div>

                        <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formatDateDisplay(formData.date_filed) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-4 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-900 w-72 uppercase">{{ getProvincialBudgetOfficer()?.name || '-' }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content Letter -->
                        <div class="mb-6 text-xs leading-relaxed text-gray-900">
                            <p class="font-semibold text-left mb-4">Ma'am:</p>

                            <p class="mb-4">
                                May I request permission to go undertime for <span class="border-b border-gray-900 w-32 inline-block text-center font-semibold">{{ computeUndertime() }}</span>
                                from <span class="border-b border-gray-900 w-32 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span>
                                on <span class="border-b border-gray-900 w-48 inline-block text-center font-semibold">{{ formatDateDisplay(formData.date_filed) }}</span>.
                            </p>

                            <p class="mb-10">
                                I will be leaving my work station to <span class="border-b border-gray-900 w-[410px] inline-block text-center font-semibold">{{ formData.reason }}</span>
                                and be back <span class="border-b border-gray-900 w-48 inline-block text-center font-semibold">{{ formData.returnType === 'nwd' ? 'Next Working Day' : formatTimeDisplay(formData.return_time) }}</span>.
                            </p>

                            <p class="mb-8 text-left">Respectfully yours,</p>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p v-if="getRequestingEmployee()" class="font-bold text-xs text-gray-900 text-center border-b border-gray-900 uppercase w-72 pb-1">
                                {{ getRequestingEmployee()?.name }}
                            </p>
                            <p v-else class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 pb-1">[Employee Name]</p>
                            <p v-if="getRequestingEmployee()" class="text-xs text-center text-gray-700 w-72">{{ getRequestingEmployee()?.designation }}</p>
                            <p v-else class="text-xs text-center text-gray-700 w-72">[Designation]</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getSupervisor() && !isRequestingEmployeeProvincialBudgetOfficer()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-72">
                                    <p class="font-bold text-xs text-center text-gray-900 uppercase w-72">{{ getSupervisor()?.name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-900 pb-1 w-72">{{ getSupervisor()?.designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-72">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="font-bold text-xs text-gray-900 uppercase w-72">{{ getProvincialGovernor()?.name }}</p>
                                <p v-else-if="getProvincialBudgetOfficer()" class="font-bold text-xs text-gray-900 uppercase w-72">{{ getProvincialBudgetOfficer()?.name }}</p>
                                <p v-else class="font-bold text-xs text-gray-900 uppercase w-72">-</p>

                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-72">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-900 pb-1 w-72">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700 w-72">Department Head</p>
                            </div>
                        </div>
                    </div>

                    <!-- Copy 2 (Duplicate) -->
                    <div>
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-4 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-semibold text-gray-700 mb-1">Republic of the Philippines</p>
                                <p class="text-xs font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-base font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-xs text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                        <div>
                            <p class="font-bold text-lg text-center text-gray-900 mb-6">REQUEST FOR UNDERTIME</p>
                        </div>

                        <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formatDateDisplay(formData.date_filed) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-4 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-900 w-72 uppercase">{{ getProvincialBudgetOfficer()?.name || '-' }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content Letter -->
                        <div class="mb-6 text-xs leading-relaxed text-gray-900">
                            <p class="font-semibold text-left mb-4">Ma'am:</p>

                            <p class="mb-4">
                                May I request permission to go undertime for <span class="border-b border-gray-900 w-32 inline-block text-center font-semibold">{{ computeUndertime() }}</span>
                                from <span class="border-b border-gray-900 w-32 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span>
                                on <span class="border-b border-gray-900 w-48 inline-block text-center font-semibold">{{ formatDateDisplay(formData.date_filed) }}</span>.
                            </p>

                            <p class="mb-10">
                                I will be leaving my work station to <span class="border-b border-gray-900 w-[410px] inline-block text-center font-semibold">{{ formData.reason }}</span>
                                and be back <span class="border-b border-gray-900 w-48 inline-block text-center font-semibold">{{ formData.returnType === 'nwd' ? 'Next Working Day' : formatTimeDisplay(formData.return_time) }}</span>.
                            </p>

                            <p class="mb-8 text-left">Respectfully yours,</p>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p v-if="getRequestingEmployee()" class="font-bold text-xs text-gray-900 text-center border-b border-gray-900 uppercase w-72 pb-1">
                                {{ getRequestingEmployee()?.name }}
                            </p>
                            <p v-else class="font-bold text-xs text-gray-900 text-center border-b border-gray-900 w-72 pb-1">[Employee Name]</p>
                            <p v-if="getRequestingEmployee()" class="text-xs text-center text-gray-700 w-72">{{ getRequestingEmployee()?.designation }}</p>
                            <p v-else class="text-xs text-center text-gray-700 w-72">[Designation]</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getSupervisor() && !isRequestingEmployeeProvincialBudgetOfficer()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-72">
                                    <p class="font-bold text-xs text-center text-gray-900 uppercase w-72">{{ getSupervisor()?.name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-900 pb-1 w-72">{{ getSupervisor()?.designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-72">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="font-bold text-xs text-gray-900 uppercase w-72">{{ getProvincialGovernor()?.name }}</p>
                                <p v-else-if="getProvincialBudgetOfficer()" class="font-bold text-xs text-gray-900 uppercase w-72">{{ getProvincialBudgetOfficer()?.name }}</p>
                                <p v-else class="font-bold text-xs text-gray-900 uppercase w-72">-</p>

                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-72">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-900 pb-1 w-72">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700 w-72">Department Head</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                    <button @click="printTardinessRequest" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                    <template v-if="!isPreviewFromTable">
                        <button @click="$emit('confirm')" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ saving ? 'Saving...' : 'Confirm & Save' }}
                        </button>
                        <button @click="$emit('close')" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left"></i>
                            Back to Form
                        </button>
                    </template>
                    <template v-else>
                        <button @click="$emit('close')" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-times"></i>
                            Close
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { Employee } from '../Composables/useTardinessData';

const props = defineProps<{
    show: boolean;
    formData: {
        control_no: string;
        date_filed: string;
        requested_time: string;
        return_time: string;
        returnType: 'time' | 'nwd';
        reason: string;
        employee_id: number;
        supervisor_employee_id: number | null;
    };
    requestingEmployee: Employee | null;
    supervisor: Employee | null;
    provincialBudgetOfficer: Employee | null;
    provincialGovernor: Employee | null;
    saving: boolean;
    isPreviewFromTable?: boolean;
}>();

defineEmits<{
    close: [];
    confirm: [];
}>();

const getRequestingEmployee = () => props.requestingEmployee;
const getSupervisor = () => props.supervisor;
const getProvincialBudgetOfficer = () => props.provincialBudgetOfficer;
const getProvincialGovernor = () => props.provincialGovernor;

const isRequestingEmployeeProvincialBudgetOfficer = (): boolean => {
    return props.requestingEmployee?.designation === 'Provincial Budget Officer';
};

const getEmployeeOffice = (): string => {
    return props.requestingEmployee?.office?.name || 'Provincial Budget Office';
};

const formatDateDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
        console.error('Error formatting date:', e);
        return '';
    }
};

const formatTimeDisplay = (timeStr: string): string => {
    if (!timeStr) return '';
    try {
        const timeParts = timeStr.split(':');
        if (!timeParts || timeParts.length < 2) return '';
        const hours = timeParts[0];
        const minutes = timeParts[1];
        const hour = parseInt(hours);
        if (isNaN(hour)) return '';
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    } catch (e) {
        console.error('Error formatting time:', e);
        return '';
    }
};

const computeUndertime = (): string => {
    try {
        if (!props.formData || !props.formData.requested_time || !props.formData.return_time) {
            return '';
        }

        const requestedTime = props.formData.requested_time.toString();
        const returnTime = props.formData.return_time.toString();

        if (!requestedTime || !returnTime || requestedTime.length < 5 || returnTime.length < 5) {
            return '';
        }

        const [startHour, startMin] = requestedTime.split(':').map(Number);
        const [endHour, endMin] = returnTime.split(':').map(Number);

        if (isNaN(startHour) || isNaN(startMin) || isNaN(endHour) || isNaN(endMin)) {
            return '';
        }

        const requestedTotalMin = startHour * 60 + startMin;
        const returnTotalMin = endHour * 60 + endMin;

        const isDayWrap = returnTotalMin < requestedTotalMin;

        let diffMin = returnTotalMin - requestedTotalMin;

        if (diffMin < 0) {
            diffMin += 24 * 60;
        }

        const LUNCH_START = 12 * 60;
        const LUNCH_END = 13 * 60;
        const LUNCH_DURATION = 60;

        let shouldSubtractLunch = false;

        if (isDayWrap) {
            shouldSubtractLunch = true;
        } else {
            if (requestedTotalMin < LUNCH_START && returnTotalMin > LUNCH_END) {
                shouldSubtractLunch = true;
            }
        }

        if (shouldSubtractLunch) {
            diffMin -= LUNCH_DURATION;
        }

        const hours = Math.floor(diffMin / 60);
        const minutes = diffMin % 60;

        if (hours === 0) {
            return minutes > 0 ? `${minutes} mins` : '0 mins';
        } else if (minutes === 0) {
            return `${hours} hrs`;
        } else {
            return `${hours} hrs and ${minutes} mins`;
        }
    } catch (e) {
        console.error('Error computing undertime:', e);
        return '';
    }
};

const printTardinessRequest = () => {
    window.print();
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

/* Print Styles: Hide modal chrome and scrollbars */
@media print {
    /* Remove page margins */
    @page {
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Hide all modal chrome and UI elements */
    .sticky {
        position: static !important;
    }

    .sticky.top-0 {
        display: none !important;
    }

    .sticky.bottom-0 {
        display: none !important;
    }

    /* Remove scrollbars and margins */
    body {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    html {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    /* Remove modal container padding and styles */
    .relative {
        box-shadow: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Remove preview content padding and margins */
    #preview-content {
        padding: 0 !important;
        margin: 0 !important;
        gap: 0 !important;
    }

    /* Remove margins from preview columns */
    #preview-content > div {
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Remove grid padding */
    .grid {
        padding: 0 !important;
        margin: 0 !important;
        gap: 0 !important;
    }

    /* Remove scroll from modal/container */
    .overflow-y-auto {
        overflow: visible !important;
    }

    /* Remove shadows from printed form */
    .shadow-2xl {
        box-shadow: none !important;
    }

    [class*="shadow"] {
        box-shadow: none !important;
    }

    /* Ensure content takes full width */
    div[style*="background-color: white"] {
        margin: 0 !important;
        padding: 0 !important;
    }
}</style>
