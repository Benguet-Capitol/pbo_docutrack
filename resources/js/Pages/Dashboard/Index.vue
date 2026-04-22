<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- HR Summary Panel Component -->
            <HRSummaryPanel
                v-if="canViewHRSummary"
                :canViewHRSummary="canViewHRSummary"
                :hrLoading="hrLoading"
                :selectedHRYear="selectedHRYear"
                :selectedHRMonth="selectedHRMonth"
                :availableHRYears="availableHRYears"
                :currentMonthLeaves="currentMonthLeaves"
                :currentMonthLeavesByType="currentMonthLeavesByType"
                :currentMonthLeavesByTypeWithEmployees="currentMonthLeavesByTypeWithEmployees"
                :currentMonthTravelOrders="currentMonthTravelOrders"
                :uniqueEmployeesTravelOrders="uniqueEmployeesTravelOrders"
                :currentMonthTravelOrdersByEmp="currentMonthTravelOrdersByEmp"
                :currentMonthPassSlips="currentMonthPassSlips"
                :uniqueEmployeesPassSlips="uniqueEmployeesPassSlips"
                :currentMonthPassSlipsByEmp="currentMonthPassSlipsByEmp"
                :currentMonthTardiness="currentMonthTardiness"
                :uniqueEmployeesTardiness="uniqueEmployeesTardiness"
                :currentMonthTardinesssByEmp="currentMonthTardinesssByEmp"
                :employeeLeavesSummary="employeeLeavesSummary"
                :uniqueLeaveTypes="uniqueLeaveTypes"
                :formatDateRange="formatDateRange"
                :formatTime="formatTime"
                :formatInclusiveDates="formatInclusiveDates"
                :getOtherLeaveTypes="getOtherLeaveTypes"
                :getOtherLeaveTypesMap="getOtherLeaveTypesMap"
                @update:selectedHRYear="handleUpdateHRYear"
                @update:selectedHRMonth="handleUpdateHRMonth"
                @showSummaryModal="handleShowSummaryModal"
            />

            <!-- Documents Section Component -->
            <DocumentsSection
                :documents="documents"
                :searchQuery="searchQuery"
                :sortBy="sortBy"
                :sortOrder="sortOrder"
                :itemsPerPage="itemsPerPage"
                :currentPage="currentPage"
                :expandedDocumentId="expandedDocumentId"
                :selectedYear="selectedYear"
                :selectedSemester="selectedSemester"
                :selectedUser="selectedUser"
                :filteredDocuments="filteredDocuments"
                :paginatedDocuments="paginatedDocuments"
                :totalPages="totalPages"
                :availableYears="availableYears"
                :availableUsers="availableUsers"
                :loading="loading"
                :error="error"
                :calculateProcessingTime="calculateProcessingTime"
                :getTimeLeftText="getTimeLeftText"
                :getTimeLeftStyles="getTimeLeftStyles"
                :getCustodianName="getCustodianName"
                :formatDuration="formatDuration"
                :getActionType="getActionType"
                :matchesYearAndSemester="matchesYearAndSemester"
                @update:searchQuery="handleUpdateSearchQuery"
                @update:itemsPerPage="handleUpdateItemsPerPage"
                @update:currentPage="handleUpdateCurrentPage"
                @update:selectedYear="handleUpdateSelectedYear"
                @update:selectedSemester="handleUpdateSelectedSemester"
                @update:selectedUser="handleUpdateSelectedUser"
                @sort="handleSort"
                @toggleExpanded="toggleExpanded"
                @showReportModal="handleShowReportModal"
            />

            <!-- Statistics Panels Grid -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- User Statistics Component -->
                <UserStatisticsSection
                    :filteredUserStatistics="filteredUserStatistics"
                    :expandedUserId="expandedUserId"
                    :selectedUserDetails="selectedUserDetails"
                    :formatHours="formatHours"
                    @expandUser="toggleUserExpanded"
                />

                <!-- Document Statistics Component -->
                <DocumentStatisticsSection
                    :documentProcessingStatistics="documentProcessingStatistics"
                    :expandedDocumentType="expandedDocumentType"
                    :filteredDocuments="filteredDocuments"
                    :formatHours="formatHours"
                    :calculateProcessingTime="calculateProcessingTime"
                    :getTimeLeftText="getTimeLeftText"
                    :getTimeLeftStyles="getTimeLeftStyles"
                    @expandDocumentType="toggleDocumentTypeExpanded"
                />
            </div>

            <!-- Report Modals Component -->
            <ReportModals
                :showReportModal="showReportModal"
                :reportData="reportData"
                :reportErrors="reportErrors"
                :showSummaryModal="showSummaryModal"
                :summaryData="summaryData"
                :summaryErrors="summaryErrors"
                :supervisorUsers="supervisorUsers"
                :administratorUsers="administratorUsers"
                :administrativeStaffEmployees="administrativeStaffEmployees"
                @update:showReportModal="handleUpdateReportModal"
                @update:reportData="handleUpdateReportData"
                @update:reportErrors="handleUpdateReportErrors"
                @update:showSummaryModal="handleUpdateSummaryModal"
                @update:summaryData="handleUpdateSummaryData"
                @update:summaryErrors="handleUpdateSummaryErrors"
                @generateReport="generateReport"
                @generateSummaryReport="handleGenerateSummaryReport"
            />
        </div>

        </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import HRSummaryPanel from '@/Components/Dashboard/HRSummaryPanel.vue';
import DocumentsSection from '@/Components/Dashboard/DocumentsSection.vue';
import UserStatisticsSection from '@/Components/Dashboard/UserStatisticsSection.vue';
import DocumentStatisticsSection from '@/Components/Dashboard/DocumentStatisticsSection.vue';
import ReportModals from '@/Components/Dashboard/ReportModals.vue';
import { useDocuments } from '@/Composables/useDocuments';
import { useHRData } from '@/Composables/useHRData';
import { useProcessingTime } from '@/Composables/useProcessingTime';
import { useStatistics } from '@/Composables/useStatistics';
import { useUtilities } from '@/Composables/useUtilities';
import { useReports } from '@/Composables/useReports';

// ============== Initialize Composables ==============
const documentsComposable = useDocuments();
const hrComposable = useHRData();
const utilitiesComposable = useUtilities();

// ============== Computed Properties (before reportsComposable initialization) ==============
const page = usePage();
const canViewHRSummary = computed(() => {
    const usertype = (page.props.auth?.user as any)?.usertype || '';
    return ['Developer', 'Administrator', 'Administrative'].includes(usertype);
});

const supervisorUsers = computed(() => {
    return documentsComposable.users.value.filter(user => user.usertype === 'Supervisor');
});

const administratorUsers = computed(() => {
    return documentsComposable.users.value.filter(user => user.usertype === 'Administrator');
});

const administrativeStaffEmployees = computed(() => {
    const adminUsers = documentsComposable.users.value.filter(user => user.usertype === 'Administrative');
    return adminUsers.map(user => {
        const emp = documentsComposable.employees.value.find(e => e.name === user.name);
        return emp || { id: user.id, name: user.name, designation: '' };
    });
});

// Now initialize reportsComposable with administrativeStaffEmployees
const reportsComposable = useReports(
    documentsComposable.documents,
    documentsComposable.users,
    documentsComposable.employees,
    hrComposable.leaves,
    hrComposable.travelOrders,
    hrComposable.passSlips,
    hrComposable.tardiness,
    administrativeStaffEmployees as any
);

// ============== Extract States from Composables ==============
const {
    documents,
    searchQuery,
    sortBy,
    sortOrder,
    itemsPerPage,
    currentPage,
    expandedDocumentId,
    loading,
    error,
    selectedYear,
    selectedSemester,
    selectedUser,
    offices,
    municipalities,
    users,
    employees,
    availableYears,
    availableUsers,
    filteredDocuments,
    paginatedDocuments,
    totalPages,
    fetchDocuments,
    toggleExpanded,
    changePage,
    getDocumentsForType,
    getEmployeeDesignation,
    matchesYearAndSemester
} = documentsComposable;

const {
    leaves,
    travelOrders,
    passSlips,
    tardiness,
    hrLoading,
    selectedHRMonth,
    selectedHRYear,
    expandedHRType,
    fetchLeaves,
    fetchTravelOrders,
    fetchPassSlips,
    fetchTardiness,
    fetchAllHRData,
    currentMonthLeaves,
    availableHRYears,
    currentMonthLeavesByType,
    currentMonthLeavesByTypeWithEmployees,
    currentMonthTravelOrders,
    uniqueEmployeesTravelOrders,
    currentMonthTravelOrdersByEmp,
    currentMonthPassSlips,
    uniqueEmployeesPassSlips,
    currentMonthPassSlipsByEmp,
    currentMonthTardiness,
    uniqueEmployeesTardiness,
    currentMonthTardinesssByEmp,
    uniqueLeaveTypes,
    employeeLeavesSummary,
    toggleHRExpanded
} = hrComposable;

const {
    formatHours,
    formatDuration,
    formatDateRange,
    formatInclusiveDates,
    formatTime,
    getOtherLeaveTypes,
    getOtherLeaveTypesMap,
    getActionType
} = utilitiesComposable;

const {
    showReportModal,
    reportData,
    reportErrors,
    showSummaryModal,
    summaryData,
    summaryErrors,
    months,
    generateReport,
    generateSummaryReport
} = reportsComposable;

// ============== Processing Time and Statistics ==============
const currentTime = ref(new Date());
let timeUpdateInterval: number | null = null;

const processingTimeComposable = useProcessingTime(
    currentTime,
    offices,
    municipalities,
    formatHours,
    users
);

const {
    calculateProcessingTime,
    getTimeLeftText,
    getTimeLeftStyles,
    getCustodianName
} = processingTimeComposable;

// ============== Expand State ==============
const expandedUserId = ref<number | undefined>(undefined);
const expandedDocumentType = ref<string | undefined>(undefined);

// Pass the needed refs to statistics composable
const statisticsComposable = useStatistics(
    documents,
    selectedYear,
    selectedSemester,
    selectedUser,
    expandedUserId,
    expandedDocumentType
);

const {
    filteredUserStatistics,
    selectedUserDetails,
    documentProcessingStatistics
} = statisticsComposable;

const lastDayOfSelectedMonth = computed(() => {
    if (!summaryData.value.month) return 28;
    const currentYear = new Date().getFullYear();
    return new Date(currentYear, summaryData.value.month, 0).getDate();
});

// ============== Event Handlers ==============
const handleUpdateHRMonth = (val: number) => {
    selectedHRMonth.value = val;
};

const handleUpdateHRYear = (val: number) => {
    selectedHRYear.value = val;
};

const handleUpdateSearchQuery = (val: string) => {
    searchQuery.value = val;
};

const handleUpdateItemsPerPage = (val: number) => {
    itemsPerPage.value = val;
};

const handleUpdateCurrentPage = (val: number) => {
    currentPage.value = val;
};

const handleUpdateSelectedYear = (val: number | null) => {
    selectedYear.value = val;
};

const handleUpdateSelectedSemester = (val: number | null) => {
    selectedSemester.value = val;
};

const handleUpdateSelectedUser = (val: number | null) => {
    selectedUser.value = val;
};

const handleSort = (by: string, order: string) => {
    sortBy.value = by;
    sortOrder.value = order as 'asc' | 'desc';
};

const handleUpdateReportModal = (val: boolean) => {
    showReportModal.value = val;
};

const handleUpdateReportData = (val: any) => {
    reportData.value = val;
};

const handleUpdateReportErrors = (val: any) => {
    reportErrors.value = val;
};

const handleUpdateSummaryModal = (val: boolean) => {
    showSummaryModal.value = val;
};

const handleUpdateSummaryData = (val: any) => {
    summaryData.value = val;
};

const handleUpdateSummaryErrors = (val: any) => {
    summaryErrors.value = val;
};

const handleShowSummaryModal = () => {
    // Set the summary month to the selected HR month from the panel
    summaryData.value = {
        ...summaryData.value,
        month: selectedHRMonth.value !== null ? selectedHRMonth.value + 1 : new Date().getMonth() + 1,
        year: selectedHRYear.value || new Date().getFullYear()
    };
    showSummaryModal.value = true;
};

const handleShowReportModal = () => {
    showReportModal.value = true;
};

const handleGenerateSummaryReport = async () => {
    await generateSummaryReport();
};

// ============== Actions ==============
const toggleUserExpanded = (userIdOrIndex: number | { userId: number }) => {
    const userId = typeof userIdOrIndex === 'object' ? userIdOrIndex.userId : userIdOrIndex;
    expandedUserId.value = expandedUserId.value === userId ? undefined : userId;
};

const toggleDocumentTypeExpanded = (documentType: string) => {
    expandedDocumentType.value = expandedDocumentType.value === documentType ? undefined : documentType;
};

// ============== Lifecycle Hooks ==============
onMounted(async () => {
    try {
        // Fetch all data
        await Promise.all([
            fetchDocuments(),
            fetchAllHRData()
        ]);
    } catch (e) {
        console.error('Error fetching dashboard data:', e);
    }

    // Set up real-time updates
    timeUpdateInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
        timeUpdateInterval = null;
    }
});
</script>
