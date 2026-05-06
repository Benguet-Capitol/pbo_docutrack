<template>
    <!-- Report Generation Modal -->
    <Teleport to="body" v-if="showReportModalLocal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close-report-modal')">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-pdf text-blue-600 dark:text-blue-400"></i>
                        Generate Report
                    </h3>
                    <button @click="$emit('close-report-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-4">
                    <!-- As of Date -->
                    <div class="space-y-2">
                        <label for="report_as_of_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                        <input
                            v-model="reportAsOfDateLocal"
                            id="report_as_of_date"
                            type="date"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        />
                    </div>

                    <!-- Document Type -->
                    <div class="space-y-2">
                        <label for="report_document_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                        <select
                            v-model="reportDocumentTypeLocal"
                            id="report_document_type"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        >
                            <option value="">All Document Types</option>
                            <option v-for="docType in availableDocumentTypes" :key="docType" :value="docType">
                                {{ docType }}
                            </option>
                        </select>
                    </div>

                    <!-- Reviewed By -->
                    <div class="space-y-2">
                        <label for="report_reviewed_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reviewed By</label>
                        <select
                            v-model.number="reportReviewedByLocal"
                            id="report_reviewed_by"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        >
                            <option :value="null">Select Supervisor</option>
                            <option v-for="user in supervisorUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Certified Correct -->
                    <div class="space-y-2">
                        <label for="report_certified_correct" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Certified Correct</label>
                        <select
                            v-model.number="reportCertifiedCorrectLocal"
                            id="report_certified_correct"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        >
                            <option :value="null">Select Administrator</option>
                            <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="reportError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-red-800 dark:text-red-300 text-xs">{{ reportError }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="$emit('generateReport')"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-check"></i>
                        Generate
                    </button>
                    <button
                        @click="$emit('close-report-modal')"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Summary Report Modal -->
    <Teleport to="body" v-if="showSummaryModalLocal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close-summary-modal')">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-chart-bar text-emerald-600 dark:text-emerald-400"></i>
                        Summary Report
                    </h3>
                    <button @click="$emit('close-summary-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-4">
                    <!-- Month Selection -->
                    <div class="space-y-2">
                        <label for="summary_month" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Month</label>
                        <select
                            v-model.number="summaryMonthLocal"
                            id="summary_month"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option v-for="(monthName, index) in months" :key="index" :value="index + 1">{{ monthName }}</option>
                        </select>
                    </div>

                    <!-- Permanent or Casual Selection -->
                    <div class="space-y-2">
                        <label for="summary_employment_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employment Type</label>
                        <select
                            v-model="summaryEmploymentTypeLocal"
                            id="summary_employment_type"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Select Type</option>
                            <option value="permanent">Permanent</option>
                            <option value="casual">Casual</option>
                        </select>
                    </div>

                    <!-- Casual Period Selection (visible only for casual employment type) -->
                    <div v-if="summaryEmploymentTypeLocal === 'casual'" class="space-y-2">
                        <label for="summary_casual_period" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Report Period</label>
                        <select
                            v-model="summaryCasualPeriodLocal"
                            id="summary_casual_period"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Select Period</option>
                            <option value="1-15">As of 1-15</option>
                            <option value="16-last">As of 16-{{ lastDayOfSelectedMonth }}</option>
                        </select>
                    </div>

                    <!-- Prepared By -->
                    <div class="space-y-2">
                        <label for="summary_prepared_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Prepared By</label>
                        <select
                            v-model.number="summaryPreparedByLocal"
                            id="summary_prepared_by"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option :value="null">Select Prepared By</option>
                            <option v-for="emp in administrativeStaffEmployees" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Certified Correct -->
                    <div class="space-y-2">
                        <label for="summary_certified_correct" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Certified Correct</label>
                        <select
                            v-model.number="summaryCertifiedCorrectLocal"
                            id="summary_certified_correct"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option :value="null">Select Certified Correct</option>
                            <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Remarks -->
                    <div class="space-y-2">
                        <label for="summary_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                        <textarea
                            v-model="summaryRemarksLocal"
                            id="summary_remarks"
                            placeholder="Add any notes or remarks for the report"
                            rows="3"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                        ></textarea>
                    </div>

                    <div v-if="summaryError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-red-800 dark:text-red-300 text-xs">{{ summaryError }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="$emit('generateSummaryReport')"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-check"></i>
                        Generate
                    </button>
                    <button
                        @click="$emit('close-summary-modal')"
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
import { computed, watch, nextTick } from 'vue';

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const props = defineProps({
    showReportModal: Boolean,
    reportAsOfDateValue: String,
    reportDocumentTypeValue: String,
    reportReviewedByValue: [Number, String],
    reportCertifiedCorrectValue: [Number, String],
    availableDocumentTypes: Array<string>,
    reportError: String,
    supervisorUsers: Array<any>,
    administratorUsers: Array<any>,
    showSummaryModal: Boolean,
    summaryMonthValue: Number,
    summaryEmploymentTypeValue: String,
    summaryCasualPeriodValue: String,
    summaryPreparedByValue: [Number, String],
    summaryCertifiedCorrectValue: [Number, String],
    summaryRemarksValue: String,
    summaryError: String,
    administrativeStaffEmployees: Array<any>,
    lastDayOfMonth: Number,
});

const emit = defineEmits([
    'update:show-report-modal',
    'update:report-as-of-date',
    'update:report-document-type',
    'update:report-reviewed-by',
    'update:report-certified-correct',
    'update:show-summary-modal',
    'update:summary-month',
    'update:summary-employment-type',
    'update:summary-casual-period',
    'update:summary-prepared-by',
    'update:summary-certified-correct',
    'update:summary-remarks',
    'close-report-modal',
    'close-summary-modal',
    'generateReport',
    'generateSummaryReport'
]);

// Auto-select first item in Report Generation Modal when it opens
watch(() => props.showReportModal, async (newVal) => {
    if (newVal) {
        await nextTick();
        
        // Auto-select first document type
        if (!props.reportDocumentTypeValue && props.availableDocumentTypes && props.availableDocumentTypes.length > 0) {
            emit('update:report-document-type', props.availableDocumentTypes[0]);
        }
        
        // Auto-select first supervisor for "Reviewed By"
        if (!props.reportReviewedByValue && props.supervisorUsers && props.supervisorUsers.length > 0) {
            emit('update:report-reviewed-by', (props.supervisorUsers[0] as any).id);
        }
        
        // Auto-select first administrator for "Certified Correct"
        if (!props.reportCertifiedCorrectValue && props.administratorUsers && props.administratorUsers.length > 0) {
            emit('update:report-certified-correct', (props.administratorUsers[0] as any).id);
        }
    }
});

// Auto-select first item in Summary Report Modal when it opens
watch(() => props.showSummaryModal, async (newVal) => {
    if (newVal) {
        await nextTick();
        
        // Auto-select first employment type (permanent)
        if (!props.summaryEmploymentTypeValue) {
            emit('update:summary-employment-type', 'permanent');
        }
        
        // Auto-select first report period (1-15) if casual
        if (props.summaryEmploymentTypeValue === 'casual' && !props.summaryCasualPeriodValue) {
            emit('update:summary-casual-period', '1-15');
        }
        
        // Auto-select first prepared by
        if (!props.summaryPreparedByValue && props.administrativeStaffEmployees && props.administrativeStaffEmployees.length > 0) {
            emit('update:summary-prepared-by', (props.administrativeStaffEmployees[0] as any).id);
        }
        
        // Auto-select first certified correct
        if (!props.summaryCertifiedCorrectValue && props.administratorUsers && props.administratorUsers.length > 0) {
            emit('update:summary-certified-correct', (props.administratorUsers[0] as any).id);
        }
    }
});

// Auto-select first report period when employment type changes to casual
watch(() => props.summaryEmploymentTypeValue, (newVal) => {
    if (newVal === 'casual' && !props.summaryCasualPeriodValue) {
        emit('update:summary-casual-period', '1-15');
    }
});

const showReportModalLocal = computed({
    get: () => props.showReportModal,
    set: (val) => emit('update:show-report-modal', val),
});

const reportAsOfDateLocal = computed({
    get: () => props.reportAsOfDateValue,
    set: (val) => emit('update:report-as-of-date', val),
});

const reportDocumentTypeLocal = computed({
    get: () => props.reportDocumentTypeValue,
    set: (val) => emit('update:report-document-type', val),
});

const reportReviewedByLocal = computed({
    get: () => props.reportReviewedByValue,
    set: (val) => emit('update:report-reviewed-by', val),
});

const reportCertifiedCorrectLocal = computed({
    get: () => props.reportCertifiedCorrectValue,
    set: (val) => emit('update:report-certified-correct', val),
});

const showSummaryModalLocal = computed({
    get: () => props.showSummaryModal,
    set: (val) => emit('update:show-summary-modal', val),
});

const summaryMonthLocal = computed({
    get: () => props.summaryMonthValue,
    set: (val) => emit('update:summary-month', val),
});

const summaryEmploymentTypeLocal = computed({
    get: () => props.summaryEmploymentTypeValue,
    set: (val) => emit('update:summary-employment-type', val),
});

const summaryCasualPeriodLocal = computed({
    get: () => props.summaryCasualPeriodValue,
    set: (val) => emit('update:summary-casual-period', val),
});

const summaryPreparedByLocal = computed({
    get: () => props.summaryPreparedByValue,
    set: (val) => emit('update:summary-prepared-by', val),
});

const summaryCertifiedCorrectLocal = computed({
    get: () => props.summaryCertifiedCorrectValue,
    set: (val) => emit('update:summary-certified-correct', val),
});

const summaryRemarksLocal = computed({
    get: () => props.summaryRemarksValue,
    set: (val) => emit('update:summary-remarks', val),
});

const lastDayOfSelectedMonth = computed(() => {
    return props.lastDayOfMonth || 28;
});
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(10px);
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
