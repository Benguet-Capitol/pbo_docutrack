<template>
    <Teleport to="body" v-if="show && formData">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="relative w-full max-w-7xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                        Pass Slip Preview
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Preview Content - Two Column Layout -->
                <div class="grid grid-cols-2 gap-4 p-4" style="background-color: white;">
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
                        <p class="font-bold text-lg text-center text-gray-900 mb-6">PASS SLIP</p>
                    </div>

                    <!-- Top Information Row -->
                    <div class="mb-6 text-xs space-y-1">
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">Provincial Budget Office</span></p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                        </div>
                    </div>

                    <!-- Provincial Budget Officer Info -->
                    <div class="text-left mb-6 w-40">
                        <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer() }}</p>
                        <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                    </div>

                    <!-- Main Content -->
                    <div class="mb-4 text-xs leading-relaxed text-gray-900">
                        <p class="mb-4">
                            Permission is respectfully requested to leave at <span class="border-b border-gray-400 w-24 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span> 
                            on <span class="border-b border-gray-400 w-36 inline-block text-center font-semibold">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span> 
                            to <span :class="`border-b border-gray-400 inline-block text-center font-semibold ${getPurposeSpanClass()}`">{{ formData.purpose }}</span> 
                            at the <span class="border-b border-gray-400 w-96 inline-block text-center font-semibold">{{ formData.location }}</span>.
                        </p>
                        
                        <div class="ml-4 space-y-1">
                            <p>
                                <span>Expected Time of Return: </span> 
                                <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">
                                    {{ formData.returnType === 'asap' ? 'As soon as possible' : 
                                       formData.returnType === 'nwd' ? 'Next Working Day' : 
                                       formData.returnType === 'time_slip' ? 'Time Slip' : 
                                       formData.returnType === 'nom' ? 'NOM' : 
                                       formData.returnType === 'memo' ? 'Memo' : 
                                       formatTimeDisplay(formData.expected_return_time) }}
                                </span>
                            </p>
                            <p>
                                <span>Vehicle to be used: </span> 
                                <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">{{ formData.vehicle }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Requesting Employee Section -->
                    <div class="mb-6">
                        <p class="text-xs text-gray-700 mb-6">Requesting Employee:</p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="empId in formData.employee_ids" :key="empId" class="text-center">
                                <div class="w-48">
                                    <p class="font-bold text-xs text-gray-900 border-b border-gray-400 text-center uppercase">{{ getEmployeeName(empId) }}</p>
                                    <p class="text-xs text-gray-700 text-center">{{ getEmployeeDesignation(empId) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommending Approval -->
                    <div v-if="getRecommendingApprovalEmployee() && !isProvincialBudgetOfficerRequesting()" class="mb-6">
                        <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                        <div class="space-y-8">
                            <div class="w-96">
                                <p class="font-bold text-xs text-center text-gray-900 w-96 uppercase">{{ getRecommendingApprovalEmployee().name }}</p>
                                <p class="text-xs text-center text-gray-700 border-b border-gray-400 w-96">{{ getRecommendingApprovalEmployee().designation }}</p>
                                <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Section -->
                    <div class="mb-6 flex justify-end pr-8">
                        <div class="w-96 text-center">
                            <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                            <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialGovernor().name }}</p>
                            <p v-else class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialBudgetOfficer() }}</p>
                            <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Governor</p>
                            <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Budget Officer</p>
                            <p class="text-xs text-center text-gray-700">Department Head</p>
                        </div>
                    </div>

                    <!-- Certificate of Appearance -->
                    <div class="mt-4 pt-4" style="border-top: 3px double #050505;">
                        <p class="font-bold text-xs text-center text-gray-900 mb-2">CERTIFICATE OF APPEARANCE</p>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            This is to CERTIFY that the above mentioned person appeared in this office on {{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}.
                        </p>
                        
                        <!-- Signature and Printed Name Section -->
                        <div class="mt-4 w-96 text-center">
                                <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-6 mb-1"></p>
                                <p class="text-xs text-center text-gray-700">Signature over Printed Name</p>
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
                        <p class="font-bold text-lg text-center text-gray-900 mb-6">PASS SLIP</p>
                    </div>

                     <!-- Top Information Row -->
                    <div class="mb-6 text-xs space-y-1">
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">Provincial Budget Office</span></p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                        </div>
                    </div>

                    <!-- Provincial Budget Officer Info -->
                    <div class="text-left mb-6 w-40">
                        <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer() }}</p>
                        <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                    </div>

                    <!-- Main Content -->
                    <div class="mb-4 text-xs leading-relaxed text-gray-900">
                        <p class="mb-4">
                            Permission is respectfully requested to leave at <span class="border-b border-gray-400 w-24 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span> 
                            on <span class="border-b border-gray-400 w-36 inline-block text-center font-semibold">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span> 
                            to <span :class="`border-b border-gray-400 inline-block text-center font-semibold ${getPurposeSpanClass()}`">{{ formData.purpose }}</span> 
                            at the <span class="border-b border-gray-400 w-96 inline-block text-center font-semibold">{{ formData.location }}</span>.
                        </p>
                        
                        <div class="ml-4 space-y-1">
                            <p>
                                <span>Expected Time of Return: </span> 
                                <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">
                                    {{ formData.returnType === 'asap' ? 'As soon as possible' : 
                                       formData.returnType === 'nwd' ? 'Next Working Day' : 
                                       formData.returnType === 'time_slip' ? 'Time Slip' : 
                                       formData.returnType === 'nom' ? 'NOM' : 
                                       formData.returnType === 'memo' ? 'Memo' : 
                                       formatTimeDisplay(formData.expected_return_time) }}
                                </span>
                            </p>
                            <p>
                                <span>Vehicle to be used: </span> 
                                <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">{{ formData.vehicle }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Requesting Employee Section -->
                    <div class="mb-6">
                        <p class="text-xs text-gray-700 mb-6">Requesting Employee:</p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="empId in formData.employee_ids" :key="empId" class="text-center">
                                <div class="w-48">
                                    <p class="font-bold text-xs text-gray-900 border-b border-gray-400 text-center uppercase">{{ getEmployeeName(empId) }}</p>
                                    <p class="text-xs text-gray-700 text-center">{{ getEmployeeDesignation(empId) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommending Approval -->
                    <div v-if="getRecommendingApprovalEmployee() && !isProvincialBudgetOfficerRequesting()" class="mb-6">
                        <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                        <div class="space-y-8">
                            <div class="w-96">
                                <p class="font-bold text-xs text-center text-gray-900 w-96 uppercase">{{ getRecommendingApprovalEmployee().name }}</p>
                                <p class="text-xs text-center text-gray-700 border-b border-gray-400 w-96">{{ getRecommendingApprovalEmployee().designation }}</p>
                                <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Section -->
                    <div class="mb-6 flex justify-end pr-8">
                        <div class="w-96 text-center">
                            <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                            <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialGovernor().name }}</p>
                            <p v-else class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialBudgetOfficer() }}</p>
                            <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Governor</p>
                            <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Budget Officer</p>
                            <p class="text-xs text-center text-gray-700">Department Head</p>
                        </div>
                    </div>

                    <!-- Certificate of Appearance -->
                    <div class="mt-4 pt-4" style="border-top: 3px double #050505;">
                        <p class="font-bold text-xs text-center text-gray-900 mb-2">CERTIFICATE OF APPEARANCE</p>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            This is to CERTIFY that the above mentioned person appeared in this office on {{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}.
                        </p>
                        
                        <!-- Signature and Printed Name Section -->
                        <div class="mt-4 w-96 text-center">
                                <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-6 mb-1"></p>
                                <p class="text-xs text-center text-gray-700">Signature over Printed Name</p>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                    <button @click="printPassSlip" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                    <button @click="$emit('confirm')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-check"></i>
                        Confirm & Save
                    </button>
                    <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-arrow-left"></i>
                        Back to Form
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { Employee } from '../Composables/usePassSlipsData';

const props = defineProps<{
    show: boolean;
    formData: any;
    employees: Employee[];
    sortedEmployees: Employee[];
}>();

defineEmits<{
    confirm: [];
    close: [];
}>();

const formatTimeDisplay = (timeStr: string): string => {
    if (!timeStr) return '';
    try {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    } catch (e) {
        return '';
    }
};

const getEmployeeName = (empId: number): string => {
    const employee = props.employees?.find((emp: Employee) => emp.id === empId);
    return employee ? employee.name : '[Employee Name]';
};

const getEmployeeDesignation = (empId: number): string => {
    const employee = props.employees?.find((emp: Employee) => emp.id === empId);
    return employee?.designation || '';
};

const getProvincialBudgetOfficer = (): string => {
    const pbo = props.employees?.find((emp: Employee) => emp.designation?.toLowerCase().includes('budget officer'));
    return pbo?.name || 'Provincial Budget Officer';
};

const getProvincialGovernor = (): any => {
    const gov = props.employees?.find((emp: Employee) => emp.designation?.toLowerCase().includes('governor'));
    return gov || null;
};

const getRecommendingApprovalEmployee = (): any => {
    if (!props.formData?.recommending_approval_employee_id) return null;
    return props.employees?.find((emp: Employee) => emp.id === props.formData.recommending_approval_employee_id) || null;
};

const isProvincialBudgetOfficerRequesting = (): boolean => {
    if (!props.formData?.employee_ids || !Array.isArray(props.formData.employee_ids)) return false;
    return props.formData.employee_ids.some((id: number) => {
        const emp = props.employees?.find((e: Employee) => e.id === id);
        return emp?.designation?.toLowerCase().includes('budget officer');
    });
};

const getPurposeSpanClass = (): string => {
    const purpose = props.formData?.purpose || '';
    return purpose.length > 30 ? 'w-auto' : 'w-[560px]';
};

const printPassSlip = (): void => {
    window.print();
};
</script>

<style scoped>
@media print {
    .sticky {
        position: static;
    }
}
</style>
