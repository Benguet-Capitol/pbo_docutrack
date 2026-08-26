<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                            Travel Order Preview
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
                    <div
                        class="p-4 flex flex-col"
                        :class="paperSize === 'long' ? 'paper-long' : 'paper-short'"
                        style="background-color: white;"
                    >
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-6 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 85px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                                <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-lg font-bold text-gray-900" v-if="isApproverProvincialGovernor()">OFFICE OF THE GOVERNOR</p>
                                <p class="text-lg font-bold text-gray-900" v-else>PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                            </div>
                            <div style="width: 85px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>
                        
                        <div>
                            <p class="font-bold text-3xl text-center text-gray-900 mb-6">TRAVEL ORDER</p>
                        </div>

                        <!-- Date & Control Numbers -->
                        <div class="mb-1 flex justify-end pr-8">
                            <div class="text-center text-sm">
                                <p><span class="w-26 inline-block text-right">PBO CONTROL NO.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formData.control_no }}</span></p>
                                <p v-if="isApproverProvincialGovernor()" class="mt-2"><span class="w-26 inline-block text-right">PGO CONTROL NO.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">   </span></p>
                                <p class="mt-1"><span class="w-26 inline-block text-right">DATE: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-900 inline-block text-center">{{ formatDateForDisplayFull(formData.date) }}</span></p>
                            </div>
                        </div>

                        <!-- Requesting Employees -->
                        <div class="space-y-2 text-sm mb-6">
                            <div class="space-y-1">
                                <!-- Vertical Layout for less than 5 employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length && formData.employee_ids.length < 5" class="space-y-3 mb-6">
                                    <div class="flex gap-2 items-start">
                                        <p class="w-7">TO:</p>
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold uppercase block">{{ employees.find(e => e.id === formData.employee_ids[0])?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === formData.employee_ids[0])?.designation || '' }}</span>
                                        </div>
                                    </div>
                                    <div v-for="(empId, index) in formData.employee_ids.slice(1)" :key="empId" class="flex gap-2">
                                        <span class="w-7"></span>
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold uppercase block">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2-Column Grid Layout for 5-8 employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length >= 5 && formData.employee_ids.length < 9" class="mb-6">
                                    <p class="mb-1">TO:</p>
                                    <div class="grid grid-cols-2 gap-x-1 gap-y-3">
                                        <div v-for="(empId, index) in formData.employee_ids" :key="empId" class="text-center">
                                            <span class="block text-center font-semibold uppercase text-sm">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3-Column Grid Layout for 9 or more employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length >= 9" class="mb-6">
                                    <p class="mb-1">TO:</p>
                                    <div class="grid grid-cols-3 gap-1 space-y-1">
                                        <div v-for="(empId, index) in formData.employee_ids" :key="empId" class="text-center">
                                            <span class="block text-center font-semibold uppercase text-xs">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nature of Travel -->
                        <div class="space-y-2 text-sm mb-6">
                            <p><span class="inline-block">NATURE OF TRAVEL:</span><span class="border-b border-gray-900 w-40 inline-block text-center font-semibold">OFFICIAL BUSINESS</span></p>
                        </div>

                        <!-- Body Content -->
                        <div class="space-y-2 text-sm mb-6">
                            <p class="space-y-2">
                                <span class="inline-block indent-8">You are hereby authorized to go to </span> 
                                <span class="border-b border-gray-900 w-[500px] inline-block text-center font-semibold pr-8">{{ formData.going_to }}</span> 
                                on
                                <!-- If inclusive_dates exist, show them; otherwise show from_date and to_date -->
                                <template v-if="formData.inclusive_dates && formData.inclusive_dates.length > 0">
                                    <template v-for="(entry, idx) in formData.inclusive_dates" :key="idx">
                                        <template v-if="(idx as number) > 0">, </template>
                                        <span class="border-b border-gray-900 inline-block text-center font-semibold w-56">
                                            <template v-if="entry.includes(' - ')">
                                                {{ formatInclusiveDateForBodyDisplay(entry).start }} to {{ formatInclusiveDateForBodyDisplay(entry).end }}
                                            </template>
                                            <template v-else>
                                                {{ formatInclusiveDateForBodyDisplay(entry).start }}
                                            </template>
                                        </span>
                                    </template>
                                </template>
                                <template v-else>
                                    <span class="border-b border-gray-900 w-40 inline-block text-center font-semibold">{{ formatDateForDisplayFull(formData.from_date) }}</span>
                                    <span v-if="formData.to_date && formData.to_date !== formData.from_date"> to <span class="border-b border-gray-900 w-40 inline-block text-center font-semibold">{{ formatDateForDisplayFull(formData.to_date) }}</span></span>
                                </template>
                                for the following official duties, viz:
                            </p>
                        </div>

                        <!-- Purpose List -->
                        <div class="space-y-2 text-sm mb-6">
                            <ol v-if="formData.purpose && formData.purpose.length" class="list-decimal pl-8 list-outside space-y-3">
                                <li v-for="(purpose, idx) in formData.purpose" :key="idx" class="ml-4"><span class="border-b border-gray-900 font-semibold text-left pb-1" :style="{ display: 'inline', boxDecorationBreak: 'clone', WebkitBoxDecorationBreak: 'clone', maxWidth: getPurposeMaxWidth(purpose), lineHeight: '1.8' }">{{ purpose }}</span></li>
                            </ol>
                        </div>

                        <p class="text-justify indent-8 space-y-2 text-sm mb-8">
                            You are expected to report on your mission immediately upon your return.
                        </p>

                        <!-- Driver -->
                        <div v-if="formData.driver" class="text-sm flex items-start gap-2 mb-2">
                            <p>DRIVER:</p>
                            <p class="border-b border-gray-900 w-40 inline-block text-center font-semibold uppercase">{{ formData.driver }}</p>
                        </div>

                        <!-- Vehicle -->
                        <div class="text-sm flex items-start gap-2 mb-8">
                            <p>VEHICLE:</p>
                            <p class="border-b border-gray-900 w-40 inline-block text-center font-semibold" v-if="formData.vehicle === 'RP Vehicle' && formData.plate_number">{{ formData.vehicle }} - {{ formData.plate_number }}</p>
                            <p class="border-b border-gray-900 w-40 inline-block text-center font-semibold" v-else>{{ formData.vehicle }}</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getRecommendingApprovalSignatory()" class="mb-6">
                            <p class="text-sm text-gray-900 mb-8">RECOMMENDING APPROVAL:</p>
                            <div class="space-y-8">
                                <div class="w-72">
                                    <p class="font-bold text-sm text-center text-gray-900 w-72 uppercase" :class="{ 'border-b border-gray-900 pb-1': isApproverProvincialGovernor() }">{{ getRecommendingApprovalSignatory()?.name }}</p>
                                    <p v-if="!isApproverProvincialGovernor()" class="text-sm text-center text-gray-700 border-b border-gray-900 w-72">{{ getRecommendingApprovalSignatory()?.designation }}</p>
                                    <p class="text-sm text-center text-gray-700 w-72">{{ getRecommendingApprovalDesignation() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="mb-6 flex justify-end pr-8 mb-10">
                            <div class="w-72 text-center">
                                <p class="text-sm text-left text-gray-900 mb-8">APPROVED:</p>
                                <p class="text-sm text-center text-gray-700"></p>
                                <p class="text-base text-center text-gray-900 border-b border-gray-900 pb-1 font-bold uppercase">{{ getApproverName() }}</p>
                                <p class="text-sm text-center text-gray-700">{{ getApproverRole() }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mb-6 mt-auto">
                            <div class="text-right" style="font-size: 9px; color: #555;">
                                <p class="m-0">Generated on {{ printGeneratedAtDisplay() }}</p>
                                <p class="m-0 font-bold">PBO|DocuTrack</p>
                            </div>
                            <div style="border-top: 3px double #050505;">
                                <div class="flex justify-between items-center mt-2" style="line-height: 1;">
                                    <p v-if="!isApproverProvincialGovernor()"><span class="font-semibold text-xs">PBO Telephone No.:</span> <span class="text-xs text-gray-900">(074) 422-1378, Local: 134</span></p>
                                    <p v-else class="whitespace-nowrap"><span class="font-semibold text-xs">Benguet Capitol Trunklines:</span> <span class="text-xs text-gray-900">(074) 422-5657; 422-1116; 422-2306; 422-5760; Local: 134</span></p>
                                    <p><span class="font-semibold text-xs">Website:</span> <span class="text-xs font-semibold text-blue-800 underline">www.benguet.gov.ph</span></p>
                                </div>
                                <p><span class="font-semibold text-xs">Email Address:</span> <span class="text-xs font-semibold text-blue-800 underline">benguetpbo@benguet.gov.ph</span></p>
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
import { ref } from 'vue';
import type { Employee, TravelOrder } from '../Composables/useTravelOrdersData';
import { printSafely } from '@/Composables/usePrint';

const paperSize = ref<'short' | 'long'>('long');

const props = defineProps<{
    show: boolean;
    formData: any;
    employees: Employee[];
    sortedEmployees: Employee[];
    isPreviewFromTable?: boolean;
}>();

defineEmits<{
    close: [];
    confirm: [];
}>();

const isApproverProvincialGovernor = () => {
    if (props.formData.is_acting_approver && props.formData.acting_approver_designation?.trim()) {
        return props.formData.acting_approver_designation.toLowerCase().includes('governor');
    }
    const approver = props.sortedEmployees.find((e: Employee) => e.id === props.formData.approver_employee_id);
    return approver?.designation?.toLowerCase().includes('provincial governor');
};

const getApproverName = () => {
    if (props.formData.is_acting_approver && props.formData.acting_approver_name?.trim()) {
        return props.formData.acting_approver_name.trim();
    }
    const approver = props.sortedEmployees.find((e: Employee) => e.id === props.formData.approver_employee_id);
    return approver?.name || '';
};

const getApproverRole = () => {
    if (props.formData.is_acting_approver && props.formData.acting_approver_designation?.trim()) {
        return props.formData.acting_approver_designation.trim();
    }
    const approver = props.sortedEmployees.find((e: Employee) => e.id === props.formData.approver_employee_id);
    return approver?.designation || '';
};

const getProvincialBudgetOfficer = () => {
    return props.sortedEmployees.find((e: Employee) => 
        e.designation?.toLowerCase().includes('provincial budget officer')
    );
};

const isProvincialBudgetOfficerRequesting = () => {
    const pbo = getProvincialBudgetOfficer();
    return pbo && props.formData.employee_ids.includes(pbo.id);
};

const getRecommendingApprovalSignatory = () => {
    // If Provincial Governor is the approver and PBO is not a requesting employee, show PBO as recommending
    if (isApproverProvincialGovernor() && !isProvincialBudgetOfficerRequesting()) {
        return getProvincialBudgetOfficer();
    }
    // Otherwise, show the supervisor if available
    if (props.formData.supervisor_employee_id) {
        return props.sortedEmployees.find((e: Employee) => e.id === props.formData.supervisor_employee_id);
    }
    return null;
};

const getRecommendingApprovalDesignation = (): string => {
    // If Provincial Governor is the approver, Provincial Budget Officer recommends
    if (isApproverProvincialGovernor()) {
        return 'Provincial Budget Officer';
    }
    // If Provincial Budget Officer is the approver, the supervisor recommends
    return 'Immediate Supervisor';
};

const formatDateForDisplayFull = (date: string) => {
    if (!date) return '';
    const d = new Date(date + 'T00:00:00');
    const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('en-US', options);
};

const formatInclusiveDateForDisplay = (dateEntry: string): string => {
    if (!dateEntry) return '';
    
    if (dateEntry.includes(' - ')) {
        const [startStr, endStr] = dateEntry.split(' - ');
        const startDate = new Date(startStr.trim() + 'T00:00:00');
        const endDate = new Date(endStr.trim() + 'T00:00:00');
        const startFmt = startDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const endFmt = endDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        return `${startFmt} - ${endFmt}`;
    } else {
        const date = new Date(dateEntry.trim() + 'T00:00:00');
        return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }
};

const formatInclusiveDateForBodyDisplay = (dateEntry: string): { start: string; end?: string } => {
    if (!dateEntry) return { start: '' };
    
    if (dateEntry.includes(' - ')) {
        const [startStr, endStr] = dateEntry.split(' - ');
        const startDate = new Date(startStr.trim() + 'T00:00:00');
        const endDate = new Date(endStr.trim() + 'T00:00:00');
        const startFmt = startDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
        const endFmt = endDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
        return { start: startFmt, end: endFmt };
    } else {
        const date = new Date(dateEntry.trim() + 'T00:00:00');
        const dateFmt = date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
        return { start: dateFmt };
    }
};

const getPurposeMaxWidth = (purpose: string): string => {
    // If purpose text is short (less than 80 characters), use 750px, otherwise none (no max-width constraint)
    return purpose.length < 80 ? '750px' : 'none'
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

.paper-short {
    min-height: 1050px;
}

.paper-long {
    min-height: 1250px;
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

    /* Assign named pages so each paper size prints with its own @page size */
    .paper-short {
        page: travel-order-short;
        min-height: 10in;
    }

    .paper-long {
        page: travel-order-long;
        min-height: 12in;
    }

    @page travel-order-short {
        size: 8.5in 11in;
        margin: 0.5in;
    }

    @page travel-order-long {
        size: 8.5in 13in;
        margin: 0.5in;
    }
}
</style>