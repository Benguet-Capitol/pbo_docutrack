<template>
    <!-- Report Generation Modal -->
    <Teleport to="body" v-if="showReportModalLocal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showReportModalLocal = false">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-pdf text-blue-600 dark:text-blue-400"></i>
                        Generate Report
                    </h3>
                    <button @click="showReportModalLocal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-4">
                    <!-- As of Date -->
                    <div class="space-y-2">
                        <label for="report_as_of_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                        <input
                            v-model="reportDataLocal.asOfDate"
                            id="report_as_of_date"
                            type="date"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        />
                    </div>

                    <!-- Reviewed By -->
                    <div class="space-y-2">
                        <label for="report_reviewed_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reviewed By</label>
                        <select
                            v-model.number="reportDataLocal.reviewedBy"
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
                            v-model.number="reportDataLocal.certifiedCorrect"
                            id="report_certified_correct"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                        >
                            <option :value="null">Select Administrator</option>
                            <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="reportErrorsLocal.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-red-800 dark:text-red-300 text-xs">{{ reportErrorsLocal.submit }}</p>
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
                        @click="showReportModalLocal = false"
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showSummaryModalLocal = false">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-chart-bar text-emerald-600 dark:text-emerald-400"></i>
                        Summary Report
                    </h3>
                    <button @click="showSummaryModalLocal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-4">
                    <!-- Month Selection -->
                    <div class="space-y-2">
                        <label for="summary_month" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Month</label>
                        <select
                            v-model.number="summaryDataLocal.month"
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
                            v-model="summaryDataLocal.employmentType"
                            id="summary_employment_type"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Select Type</option>
                            <option value="permanent">Permanent</option>
                            <option value="casual">Casual</option>
                        </select>
                    </div>

                    <!-- Casual Period Selection (visible only for casual employment type) -->
                    <div v-if="summaryDataLocal.employmentType === 'casual'" class="space-y-2">
                        <label for="summary_casual_period" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Report Period</label>
                        <select
                            v-model="summaryDataLocal.casualPeriod"
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
                            v-model.number="summaryDataLocal.preparedBy"
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
                            v-model.number="summaryDataLocal.certifiedCorrect"
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
                            v-model="summaryDataLocal.remarks"
                            id="summary_remarks"
                            placeholder="Add any notes or remarks for the report"
                            rows="3"
                            class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                        ></textarea>
                    </div>

                    <div v-if="summaryErrorsLocal.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-red-800 dark:text-red-300 text-xs">{{ summaryErrorsLocal.submit }}</p>
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
                        @click="showSummaryModalLocal = false"
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
    reportData: Object,
    reportErrors: Object,
    showSummaryModal: Boolean,
    summaryData: Object,
    summaryErrors: Object,
    supervisorUsers: Array<any>,
    administratorUsers: Array<any>,
    administrativeStaffEmployees: Array<any>,
});

const emit = defineEmits([
    'update:showReportModal',
    'update:reportData',
    'update:reportErrors',
    'update:showSummaryModal',
    'update:summaryData',
    'update:summaryErrors',
    'generateReport',
    'generateSummaryReport'
]);

// Auto-select first item in Report Generation Modal when it opens
watch(() => props.showReportModal, async (newVal) => {
    if (newVal) {
        await nextTick();
        
        if (props.reportData && props.supervisorUsers && props.administratorUsers) {
            const updates: any = {};
            
            // Auto-select first supervisor for "Reviewed By" - force select on every open
            if (props.supervisorUsers.length > 0) {
                updates.reviewedBy = (props.supervisorUsers[0] as any).id;
            }
            
            // Auto-select first administrator for "Certified Correct" - force select on every open
            if (props.administratorUsers.length > 0) {
                updates.certifiedCorrect = (props.administratorUsers[0] as any).id;
            }
            
            if (Object.keys(updates).length > 0) {
                const updatedData = { ...props.reportData, ...updates };
                emit('update:reportData', updatedData);
            }
        }
    }
});

// Auto-select first item in Summary Report Modal when it opens
watch(() => props.showSummaryModal, async (newVal) => {
    if (newVal) {
        await nextTick();
        
        if (props.summaryData) {
            const updates: any = {};
            
            // Auto-select first employment type (permanent)
            if (!props.summaryData.employmentType) {
                updates.employmentType = 'permanent';
            }
            
            // Auto-select first report period (1-15) if casual
            if (props.summaryData.employmentType === 'casual' && !props.summaryData.casualPeriod) {
                updates.casualPeriod = '1-15';
            }
            
            // Auto-select first prepared by
            if (props.administrativeStaffEmployees && props.administrativeStaffEmployees.length > 0 && !props.summaryData.preparedBy) {
                updates.preparedBy = (props.administrativeStaffEmployees[0] as any).id;
            }
            
            // Auto-select first certified correct
            if (props.administratorUsers && props.administratorUsers.length > 0 && !props.summaryData.certifiedCorrect) {
                updates.certifiedCorrect = (props.administratorUsers[0] as any).id;
            }
            
            if (Object.keys(updates).length > 0) {
                const updatedData = { ...props.summaryData, ...updates };
                emit('update:summaryData', updatedData);
            }
        }
    }
});

// Auto-select first report period when employment type changes to casual
watch(() => props.summaryData?.employmentType, (newVal) => {
    if (newVal === 'casual' && props.summaryData && !props.summaryData.casualPeriod) {
        const updatedData = { ...props.summaryData, casualPeriod: '1-15' };
        emit('update:summaryData', updatedData);
    }
});

const showReportModalLocal = computed({
    get: () => props.showReportModal,
    set: (val) => emit('update:showReportModal', val),
});

const reportDataLocal = computed({
    get: () => props.reportData,
    set: (val) => emit('update:reportData', val),
});

const reportErrorsLocal = computed({
    get: () => props.reportErrors,
    set: (val) => emit('update:reportErrors', val),
});

const showSummaryModalLocal = computed({
    get: () => props.showSummaryModal,
    set: (val) => emit('update:showSummaryModal', val),
});

const summaryDataLocal = computed({
    get: () => props.summaryData,
    set: (val) => emit('update:summaryData', val),
});

const summaryErrorsLocal = computed({
    get: () => props.summaryErrors,
    set: (val) => emit('update:summaryErrors', val),
});

const lastDayOfSelectedMonth = computed(() => {
    if (!props.summaryData?.month) return 28;
    const currentYear = new Date().getFullYear();
    return new Date(currentYear, props.summaryData.month, 0).getDate();
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
