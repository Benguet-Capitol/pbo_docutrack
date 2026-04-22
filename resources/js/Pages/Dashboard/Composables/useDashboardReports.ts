import { ref } from 'vue';

export function useDashboardReports() {
    // ============== Report Generation ==============
    const showReportModal = ref(false);
    const reportData = ref({
        asOfDate: new Date().toISOString().split('T')[0],
        reviewedBy: null as number | null,
        certifiedCorrect: null as number | null,
    });
    const reportErrors = ref<Record<string, string>>({});

    // ============== Summary Report Generation ==============
    const showSummaryModal = ref(false);
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const summaryData = ref({
        month: null as number | null,
        employmentType: '',
        casualPeriod: '' as string,
        preparedBy: null as number | null,
        certifiedCorrect: null as number | null,
        remarks: '' as string,
    });
    const summaryErrors = ref<Record<string, string>>({});

    // ============== Methods ==============
    const generateReport = async (supervisorUsers: any[], administratorUsers: any[]) => {
        // Clear previous errors
        reportErrors.value = {};

        // Validate form
        if (!reportData.value.reviewedBy) {
            reportErrors.value.submit = 'Please select a Supervisor to review the report';
            return;
        }
        if (!reportData.value.certifiedCorrect) {
            reportErrors.value.submit = 'Please select an Administrator to certify the report';
            return;
        }

        try {
            const payload = {
                as_of_date: reportData.value.asOfDate,
                reviewed_by: reportData.value.reviewedBy,
                certified_correct: reportData.value.certifiedCorrect,
            };

            const response = await fetch('/api/reports/budget-proposal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Failed to generate report');
            }

            // Download the PDF
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Budget-Proposal-Report-${reportData.value.asOfDate}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showReportModal.value = false;
        } catch (e) {
            reportErrors.value.submit = e instanceof Error ? e.message : 'Failed to generate report';
            console.error('Error generating report:', e);
        }
    };

    const generateSummaryReport = async (administrativeStaffEmployees: any[], administratorUsers: any[]) => {
        // Clear previous errors
        summaryErrors.value = {};

        // Validate form
        if (!summaryData.value.month) {
            summaryErrors.value.submit = 'Please select a month';
            return;
        }
        if (!summaryData.value.employmentType) {
            summaryErrors.value.submit = 'Please select employment type';
            return;
        }
        if (summaryData.value.employmentType === 'casual' && !summaryData.value.casualPeriod) {
            summaryErrors.value.submit = 'Please select a report period for casual employees';
            return;
        }
        if (!summaryData.value.preparedBy) {
            summaryErrors.value.submit = 'Please select who prepared the report';
            return;
        }
        if (!summaryData.value.certifiedCorrect) {
            summaryErrors.value.submit = 'Please select an Administrator to certify the report';
            return;
        }

        try {
            const payload = {
                month: summaryData.value.month,
                employment_type: summaryData.value.employmentType,
                casual_period: summaryData.value.casualPeriod || null,
                prepared_by: summaryData.value.preparedBy,
                certified_correct: summaryData.value.certifiedCorrect,
                remarks: summaryData.value.remarks || null,
            };

            const response = await fetch('/api/reports/summary', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Failed to generate summary report');
            }

            // Download the PDF
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Summary-Report-${summaryData.value.month}-${new Date().getFullYear()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showSummaryModal.value = false;
        } catch (e) {
            summaryErrors.value.submit = e instanceof Error ? e.message : 'Failed to generate summary report';
            console.error('Error generating summary report:', e);
        }
    };

    return {
        // State
        showReportModal,
        reportData,
        reportErrors,
        showSummaryModal,
        summaryData,
        summaryErrors,
        months,

        // Methods
        generateReport,
        generateSummaryReport,
    };
}
