import { ref, Ref } from 'vue';

/**
 * Composable for report generation
 */
export const useReports = (
    documents: Ref<any[]>,
    users: Ref<any[]>,
    employees: Ref<any[]>,
    leaves?: Ref<any[]>,
    travelOrders?: Ref<any[]>,
    passSlips?: Ref<any[]>,
    tardiness?: Ref<any[]>,
    administrativeStaffEmployees?: Ref<any[]>
) => {
    const showReportModal = ref(false);
    const reportData = ref({
        asOfDate: new Date().toISOString().split('T')[0],
        reviewedBy: null as number | null,
        certifiedCorrect: null as number | null,
    });
    const reportErrors = ref<Record<string, string>>({});

    const showSummaryModal = ref(false);
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const summaryData = ref({
        month: null as number | null,
        year: new Date().getFullYear() as number,
        employmentType: '',
        casualPeriod: '' as string,
        preparedBy: null as number | null,
        certifiedCorrect: null as number | null,
        remarks: '' as string,
    });
    const summaryErrors = ref<Record<string, string>>({});

    /**
     * Get employee designation by user ID
     */
    const getEmployeeDesignation = (userId: number | null | undefined): string => {
        if (!userId) return 'N/A';
        const user = users.value.find(u => u.id === userId);
        if (!user) return 'N/A';
        const employee = employees.value.find(e => e.name === user.name);
        return employee?.designation || 'N/A';
    };

    /**
     * Capitalize first letter of each word
     */
    const capitalizeWords = (str: string): string => {
        return str.toUpperCase();
    };

    /**
     * Format date to words (e.g., February 12, 2026)
     */
    const formatDateToWords = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    /**
     * Generate Budget Proposals Report
     */
    const generateReport = async () => {
        reportErrors.value = {};

        try {
            const asOfDate = new Date(reportData.value.asOfDate);
            const selectedYear = asOfDate.getFullYear();

            const budgetProposals = documents.value.filter(doc => {
                if (!doc.document_type.toLowerCase().includes('proposal')) return false;
                const docDate = new Date(doc.date);
                return docDate.getFullYear() === selectedYear;
            });

            const reportData_items: Array<{
                dateReceived: string;
                trackingNo: string;
                source: string;
                particulars: string;
            }> = [];

            budgetProposals.forEach(doc => {
                if (!doc.transactions || doc.transactions.length === 0) return;
                const createdTx = doc.transactions[doc.transactions.length - 1];
                reportData_items.push({
                    dateReceived: formatDateToWords(createdTx.created_at),
                    trackingNo: doc.tracking_no,
                    source: doc.source || 'N/A',
                    particulars: doc.particulars || 'N/A'
                });
            });

            const reviewedByUser = users.value.find(u => u.id === reportData.value.reviewedBy);
            const certifiedByUser = users.value.find(u => u.id === reportData.value.certifiedCorrect);
            const reviewedByDesignation = getEmployeeDesignation(reportData.value.reviewedBy);
            const certifiedCorrectDesignation = getEmployeeDesignation(reportData.value.certifiedCorrect);

            const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Budget Proposals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10px; line-height: 1.5; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; font-size: 12px; border-bottom: 3px double black; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .header h1 { margin: 5px 0; font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid black;}
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 11px; }
        td { border-top: 1px dotted black; border-bottom: 1px dotted black; border-left: 1px solid black; border-right: 1px solid black;}
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-around; }
        .signature-box { width: 30%; text-align: center; }
        .signature-title { font-size: 11px; margin-bottom: 50px; text-align: left; }
        .signature-line { border-top: 1px solid #000; margin: 40px 0 5px 0; }
        .signature-name { font-weight: bold; font-size: 11px; margin-top: 5px; }
        .signature-designation { font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="text-align: center; margin-bottom: 10px; display: flex; justify-content: center; gap: 20px; align-items: center;">
            <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 80px; height: auto;">
            <div>
                <p style="margin-bottom: 0;">Republic of the Philippines</p>
                <p style="margin-top: 0; margin-bottom: 0; font-weight: 600;">PROVINCE OF BENGUET</p>
                <p style="font-weight: bold; margin-top: 0; margin-bottom: 0; font-size: 14px;">PROVINCIAL BUDGET OFFICE</p>
                <p style="margin-top: 0; margin-bottom: 0;">Poblacion, La Trinidad, Benguet 2601</p>
            </div>
            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 80px; height: auto;">
        </div>
    </div>

    <p style="text-align: center; font-size: 14px; margin-bottom: 0; margin-top: 10px; font-weight: bold;">BUDGET PROPOSALS</p>
    <p style="text-align: center; margin-top:0; margin-bottom: 20px;">As of ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>

    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Date Received</th>
                <th style="text-align: center;">Tracking No</th>
                <th style="text-align: center;">Source</th>
                <th style="text-align: center;">Particulars</th>
            </tr>
        </thead>
        <tbody>
            ${reportData_items.length > 0 ? reportData_items.map(item => `
            <tr>
                <td>${item.dateReceived}</td>
                <td>${item.trackingNo}</td>
                <td>${item.source}</td>
                <td>${item.particulars}</td>
            </tr>
            `).join('') : '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>'}
        </tbody>
    </table>

    ${reportData.value.reviewedBy || reportData.value.certifiedCorrect ? `
    <div class="signature-section">
        ${reportData.value.reviewedBy ? `
        <div class="signature-box">
            <div class="signature-title">Reviewed By:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(reviewedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${reviewedByDesignation}</div>
        </div>
        ` : ''}

        ${reportData.value.certifiedCorrect ? `
        <div class="signature-box">
            <div class="signature-title">Certified Correct:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(certifiedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${certifiedCorrectDesignation}</div>
        </div>
        ` : ''}
    </div>
    ` : ''}
</body>
</html>
            `;

            const reportWindow = window.open('', 'PRINT_REPORT', 'height=900,width=1500');
            if (reportWindow) {
                reportWindow.document.write(htmlContent);
                reportWindow.document.close();
                reportWindow.print();
            }

            showReportModal.value = false;
        } catch (e) {
            reportErrors.value.submit = e instanceof Error ? e.message : 'An error occurred';
        }
    };

    /**
     * Format inclusive dates for display
     */
    const formatInclusiveDatesToString = (inclusiveDates: any[]): string => {
        if (!inclusiveDates || inclusiveDates.length === 0) return '';
        
        return inclusiveDates.map((date: any) => {
            if (!date) return '';
            
            // Handle date ranges like "2026-01-29 - 2026-02-04"
            if (typeof date === 'string' && date.includes(' - ')) {
                const [startStr, endStr] = date.split(' - ');
                const startDate = new Date(startStr.trim());
                const endDate = new Date(endStr.trim());
                const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                return `${start} - ${end}`;
            } else {
                const d = new Date(date);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
        }).filter(d => d).join(', ');
    };

    /**
     * Format time (HH:MM format)
     */
    const formatTime = (timeStr: string | undefined): string => {
        if (!timeStr) return '';
        try {
            const parts = String(timeStr).split(':');
            if (parts.length >= 2) {
                const hour = parseInt(parts[0], 10);
                const minute = parseInt(parts[1], 10);
                return `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            }
        } catch (e) {
            // Ignore errors
        }
        return timeStr;
    };

    /**
     * Calculate time difference between two HH:MM time strings
     */
    const calculateTimeDifference = (startTime: string, endTime: string): { hours: number; minutes: number; display: string } => {
        if (!startTime || !endTime) return { hours: 0, minutes: 0, display: 'N/A' };
        
        try {
            const startClean = String(startTime).trim();
            const endClean = String(endTime).trim();
            
            if (!startClean || !endClean) return { hours: 0, minutes: 0, display: 'N/A' };
            
            const startParts = startClean.split(':');
            const endParts = endClean.split(':');
            
            if (startParts.length < 2 || endParts.length < 2) return { hours: 0, minutes: 0, display: 'N/A' };
            
            const startHour = parseInt(startParts[0], 10);
            const startMin = parseInt(startParts[1], 10);
            const endHour = parseInt(endParts[0], 10);
            const endMin = parseInt(endParts[1], 10);
            
            if (isNaN(startHour) || isNaN(startMin) || isNaN(endHour) || isNaN(endMin)) {
                return { hours: 0, minutes: 0, display: 'N/A' };
            }
            
            let totalMinutes = (endHour * 60 + endMin) - (startHour * 60 + startMin);
            
            // Handle case where end time is next day (e.g., 11 PM to 1 AM)
            if (totalMinutes < 0) {
                totalMinutes += 24 * 60;
            }
            
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            
            return {
                hours,
                minutes,
                display: hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
            };
        } catch (e) {
            console.error('Error calculating time difference:', e);
            return { hours: 0, minutes: 0, display: 'N/A' };
        }
    };

    /**
     * Generate Summary Report with full HR data
     */
    const generateSummaryReport = async () => {
        summaryErrors.value = {};

        try {
            // Validate inputs
            if (!summaryData.value.month) {
                summaryErrors.value.submit = 'Month is required';
                return;
            }
            if (!summaryData.value.employmentType) {
                summaryErrors.value.submit = 'Employment type is required';
                return;
            }
            if (summaryData.value.employmentType === 'casual' && !summaryData.value.casualPeriod) {
                summaryErrors.value.submit = 'Report period is required for casual employees';
                return;
            }
            if (!summaryData.value.preparedBy) {
                summaryErrors.value.submit = 'Prepared by is required';
                return;
            }
            if (!summaryData.value.certifiedCorrect) {
                summaryErrors.value.submit = 'Certified correct is required';
                return;
            }

            // Fetch HR data if not already available
            const leavesData = leaves?.value || [];
            const travelOrdersData = travelOrders?.value || [];
            const passSilpsData = passSlips?.value || [];
            const tardinessData = tardiness?.value || [];

            // Get current year
            const currentYear = summaryData.value.year || new Date().getFullYear();
            const monthName = months[summaryData.value.month - 1];

            // Helper function to check if date falls in specified month
            const isInMonth = (dateString: string) => {
                const date = new Date(dateString);
                return date.getMonth() === (summaryData.value.month! - 1) && 
                       date.getFullYear() === currentYear;
            };

            // Helper function to get employment type based on designation
            const getEmploymentType = (emp: any) => {
                if (!emp || !emp.designation) return 'permanent';
                const designation = emp.designation.toLowerCase();
                return designation.includes('casual') ? 'casual' : 'permanent';
            };

            // Helper function to check if a date falls within the casual period
            const isDateInCasualPeriod = (dateString: string): boolean => {
                if (!summaryData.value.casualPeriod) return true;
                
                const date = new Date(dateString);
                const day = date.getDate();
                const lastDay = new Date(currentYear, summaryData.value.month!, 0).getDate();
                
                if (summaryData.value.casualPeriod === '1-15') {
                    return day >= 1 && day <= 15;
                } else if (summaryData.value.casualPeriod === '16-last') {
                    return day >= 16 && day <= lastDay;
                }
                return true;
            };

            // Helper function to check if a leave falls in the specified month
            const leaveInMonth = (leave: any): boolean => {
                if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;
                
                const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
                const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
                
                for (const dateEntry of leave.inclusive_dates) {
                    if (!dateEntry) continue;
                    
                    if (dateEntry.includes(' - ')) {
                        const [startStr, endStr] = dateEntry.split(' - ');
                        const rangeStart = new Date(startStr.trim());
                        const rangeEnd = new Date(endStr.trim());
                        
                        if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                            if (summaryData.value.employmentType === 'casual') {
                                if (isDateRangeInCasualPeriod(startStr.trim(), endStr.trim())) {
                                    return true;
                                }
                            } else {
                                return true;
                            }
                        }
                    } else {
                        const date = new Date(dateEntry.trim());
                        if (date.getMonth() === (summaryData.value.month! - 1) && date.getFullYear() === currentYear) {
                            if (summaryData.value.employmentType === 'casual') {
                                if (isDateInCasualPeriod(dateEntry.trim())) {
                                    return true;
                                }
                            } else {
                                return true;
                            }
                        }
                    }
                }
                return false;
            };

            // Helper function to check if a pass slip falls in the specified month (based on inclusive_dates)
            const passSlipInMonth = (ps: any): boolean => {
                // If inclusive_dates exist, use them; otherwise fall back to date
                if (ps.inclusive_dates && Array.isArray(ps.inclusive_dates) && ps.inclusive_dates.length > 0) {
                    const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
                    const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
                    
                    for (const dateEntry of ps.inclusive_dates) {
                        if (!dateEntry) continue;
                        
                        if (dateEntry.includes(' - ')) {
                            const [startStr, endStr] = dateEntry.split(' - ');
                            const rangeStart = new Date(startStr.trim());
                            const rangeEnd = new Date(endStr.trim());
                            
                            if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                                if (summaryData.value.employmentType === 'casual') {
                                    if (isDateRangeInCasualPeriod(startStr.trim(), endStr.trim())) {
                                        return true;
                                    }
                                } else {
                                    return true;
                                }
                            }
                        } else {
                            const date = new Date(dateEntry.trim());
                            if (date.getMonth() === (summaryData.value.month! - 1) && date.getFullYear() === currentYear) {
                                if (summaryData.value.employmentType === 'casual') {
                                    if (isDateInCasualPeriod(dateEntry.trim())) {
                                        return true;
                                    }
                                } else {
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                }
                
                // Fallback to date if inclusive_dates not available
                if (!isInMonth(ps.date)) return false;
                if (summaryData.value.employmentType === 'casual' && !isDateInCasualPeriod(ps.date)) return false;
                return true;
            };

            // Helper function to check if a date range overlaps with the casual period
            const isDateRangeInCasualPeriod = (startDateString: string, endDateString: string): boolean => {
                if (!summaryData.value.casualPeriod) return true;
                
                const startDate = new Date(startDateString);
                const endDate = new Date(endDateString);
                const lastDay = new Date(currentYear, summaryData.value.month!, 0).getDate();
                
                const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
                const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
                
                const effectiveStart = startDate > monthStart ? startDate : monthStart;
                const effectiveEnd = endDate < monthEnd ? endDate : monthEnd;
                
                const effectiveStartDay = effectiveStart.getDate();
                const effectiveEndDay = effectiveEnd.getDate();
                
                if (summaryData.value.casualPeriod === '1-15') {
                    return effectiveStartDay <= 15 && effectiveEndDay >= 1;
                } else if (summaryData.value.casualPeriod === '16-last') {
                    return effectiveStartDay <= lastDay && effectiveEndDay >= 16;
                }
                return true;
            };

            // Helper function to check if travel order falls in the specified month
            const travelOrderInMonth = (to: any): boolean => {
                if (!to.from_date || !to.to_date) return false;
                
                const fromDate = new Date(to.from_date);
                const toDate = new Date(to.to_date);
                const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
                const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
                
                if (fromDate <= monthEnd && toDate >= monthStart) {
                    if (summaryData.value.employmentType === 'casual') {
                        return isDateRangeInCasualPeriod(to.from_date, to.to_date);
                    }
                    return true;
                }
                return false;
            };

            // Group data by employee
            const employeeData = new Map<number, {
                employee_id: string;
                id: number;
                name: string;
                designation: string;
                leaves: any[];
                passSlips: any[];
                travelOrders: any[];
                tardiness: any[];
            }>();

            // Process leaves
            leavesData.forEach((leave: any) => {
                if (!leaveInMonth(leave)) return;
                
                const empId = leave.employee_id;
                let employee = leave.employee;
                if (!employee && empId) {
                    employee = employees.value.find(e => e.id === empId);
                }
                if (!employee) return;
                if (getEmploymentType(employee) !== summaryData.value.employmentType) return;

                if (!employeeData.has(empId)) {
                    employeeData.set(empId, {
                        employee_id: employee?.employee_id || '',
                        id: empId,
                        name: employee?.name || 'N/A',
                        designation: employee?.designation || '',
                        leaves: [],
                        passSlips: [],
                        travelOrders: [],
                        tardiness: []
                    });
                }
                employeeData.get(empId)!.leaves.push(leave);
            });

            // Process pass slips
            passSilpsData.forEach((ps: any) => {
                // Check if pass slip falls in the specified month based on inclusive_dates
                if (!passSlipInMonth(ps)) return;
                
                const empsInPassSlip = ps.employees && Array.isArray(ps.employees) ? ps.employees : [];
                
                empsInPassSlip.forEach((emp: any) => {
                    if (!emp || !emp.id) return;
                    if (getEmploymentType(emp) !== summaryData.value.employmentType) return;
                    
                    const empId = emp.id;
                    if (!employeeData.has(empId)) {
                        employeeData.set(empId, {
                            employee_id: emp?.employee_id || '',
                            id: empId,
                            name: emp?.name || 'N/A',
                            designation: emp?.designation || '',
                            leaves: [],
                            passSlips: [],
                            travelOrders: [],
                            tardiness: []
                        });
                    }
                    
                    // If pass slip has inclusive_dates, create separate entries for each date
                    if (ps.inclusive_dates && Array.isArray(ps.inclusive_dates) && ps.inclusive_dates.length > 0) {
                        ps.inclusive_dates.forEach((dateEntry: string) => {
                            employeeData.get(empId)!.passSlips.push({
                                ...ps,
                                date: dateEntry  // Override with individual date for display
                            });
                        });
                    } else {
                        // Fallback to regular date field
                        employeeData.get(empId)!.passSlips.push(ps);
                    }
                });
            });

            // Process travel orders
            travelOrdersData.forEach((to: any) => {
                if (!travelOrderInMonth(to)) return;
                
                let empsToProcess = [];
                
                if (to.employees && Array.isArray(to.employees)) {
                    empsToProcess = to.employees;
                } else if (to.employee) {
                    empsToProcess = [to.employee];
                } else if (to.employee_id) {
                    const emp = employees.value.find(e => e.id === to.employee_id);
                    if (emp) {
                        empsToProcess = [emp];
                    }
                }
                
                empsToProcess.forEach((emp: any) => {
                    if (!emp || !emp.id) return;
                    if (getEmploymentType(emp) !== summaryData.value.employmentType) return;
                    
                    const empId = emp.id;
                    if (!employeeData.has(empId)) {
                        employeeData.set(empId, {
                            employee_id: emp?.employee_id || '',
                            id: empId,
                            name: emp.name || 'N/A',
                            designation: emp?.designation || '',
                            leaves: [],
                            passSlips: [],
                            travelOrders: [],
                            tardiness: []
                        });
                    }
                    employeeData.get(empId)!.travelOrders.push(to);
                });
            });

            // Process tardiness/undertime
            tardinessData.forEach((tu: any) => {
                if (!isInMonth(tu.date_filed)) return;
                
                if (summaryData.value.employmentType === 'casual' && tu.requested_date && !isDateInCasualPeriod(tu.requested_date)) return;
                
                const empId = tu.employee_id;
                let employee = tu.employee;
                if (!employee && empId) {
                    employee = employees.value.find(e => e.id === empId);
                }
                if (!employee) return;
                if (getEmploymentType(employee) !== summaryData.value.employmentType) return;

                if (!employeeData.has(empId)) {
                    employeeData.set(empId, {
                        employee_id: employee?.employee_id || '',
                        id: empId,
                        name: employee?.name || 'N/A',
                        designation: employee?.designation || '',
                        leaves: [],
                        passSlips: [],
                        travelOrders: [],
                        tardiness: []
                    });
                }
                employeeData.get(empId)!.tardiness.push(tu);
            });

            // Get signatory names
            const preparedByData = administrativeStaffEmployees?.value?.find(e => e.id === summaryData.value.preparedBy);
            const preparedByEmployee = preparedByData || employees.value.find(e => e.id === summaryData.value.preparedBy);
            const certifiedByUser = users.value.find(u => u.id === summaryData.value.certifiedCorrect);
            const certifiedByEmployee = certifiedByUser ? employees.value.find(e => e.name === certifiedByUser.name) : null;

            // Generate HTML report
            const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary of Leaves, Pass Slips and Travel Orders</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10px; line-height: 1.5; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 3px double black;}
        .header p { margin: 5px 0; font-size: 12px; }
        .header h1 { margin: 5px 0; font-size: 14px; font-weight: bold; }
        .as-of { text-align: center; margin-bottom: 20px; font-size: 12px; }
        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid black; }
        th, td { border: 1px solid #000; padding: 4px 3px; text-align: left; font-size: 11px; }
        td { border-top: 1px dotted black; border-bottom: 1px dotted black; border-left: 1px solid black; border-right: 1px solid black;}
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature-section { margin-top: 20px; display: flex; justify-content: space-around; }
        .signature-box { width: 30%; text-align: center; }
        .signature-title { font-size: 11px; margin-bottom: 50px; text-align: left; }
        .signature-line { border-top: 1px solid #000; margin: 40px 0 5px 0; }
        .signature-name { font-weight: bold; font-size: 11px; margin-top: 5px; }
        .signature-designation { font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="text-align: center; margin-bottom: 10px; display: flex; justify-content: center; gap: 20px; align-items: center;">
            <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 80px; height: auto;">
            <div>
                <p style="margin-bottom: 0;">Republic of the Philippines</p>
                <p style="margin-top: 0; margin-bottom: 0; font-weight: 600;">PROVINCE OF BENGUET</p>
                <p style="font-weight: bold; margin-top: 0; margin-bottom: 0; font-size: 14px;">PROVINCIAL BUDGET OFFICE</p>
                <p style="margin-top: 0; margin-bottom: 0;">Poblacion, La Trinidad, Benguet 2601</p>
            </div>
            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 80px; height: auto;">
        </div>
    </div>
    
    <p style="text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 0;">SUMMARY OF LEAVES, TRAVEL ORDERS, PASS SLIPS AND TARDINESS/UNDERTIME</p>
    <p style="text-align: center; font-size: 12px; margin-top: 0; margin-bottom: 20px;">For the month of ` + (summaryData.value.employmentType === 'casual' ? (summaryData.value.casualPeriod === '1-15' ? monthName + ' 1-15, ' + currentYear : monthName + ' 16-' + new Date(currentYear, summaryData.value.month!, 0).getDate() + ', ' + currentYear) : monthName + ' ' + currentYear) + ` (` + (summaryData.value.employmentType.charAt(0).toUpperCase() + summaryData.value.employmentType.slice(1).toLowerCase()) + `)</p>
    
    ${Array.from(employeeData.values()).length > 0 ? `
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="text-align: center; font-weight: bold; vertical-align: middle; width: 8%;">ID NO</th>
                <th rowspan="2" style="text-align: center; font-weight: bold; vertical-align: middle; width: 12%;">NAME</th>
                <th colspan="2" style="text-align: center; font-weight: bold;">LEAVES</th>
                <th colspan="1" style="text-align: center; font-weight: bold;">TRAVEL ORDERS</th>
                <th colspan="3" style="text-align: center; font-weight: bold;">PASS SLIPS</th>
                <th colspan="7" style="text-align: center; font-weight: bold;">TARDINESS/UNDERTIME</th>
            </tr>
            <tr>
                <th style="text-align: center; font-weight: bold; width: 10%;">Type of Leave</th>
                <th style="text-align: center; font-weight: bold; width: 8%;">Inclusive Dates</th>
                <th style="text-align: center; font-weight: bold; width: 8%;">Inclusive Dates</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Date</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Time of Departure</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Return Time</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Tard. Date</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Tard. Hrs/Mins</th>
                <th style="text-align: center; font-weight: bold; width: 4%;">Tard. Count</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Undert. Date</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Undert. Hrs/Mins</th>
                <th style="text-align: center; font-weight: bold; width: 4%;">Undert. Count</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Total Hrs/Mins</th>
            </tr>
        </thead>
        <tbody>
            ${(() => {
                const sortedEntries = Array.from(employeeData.entries()).sort((a, b) => {
                    const aIsProvincialBudgetOfficer = a[1].designation?.toLowerCase().includes('provincial budget officer') || false;
                    const bIsProvincialBudgetOfficer = b[1].designation?.toLowerCase().includes('provincial budget officer') || false;
                    
                    if (aIsProvincialBudgetOfficer && !bIsProvincialBudgetOfficer) return -1;
                    if (!aIsProvincialBudgetOfficer && bIsProvincialBudgetOfficer) return 1;
                    
                    const aName = a[1].name.split(' ').pop() || '';
                    const bName = b[1].name.split(' ').pop() || '';
                    return aName.localeCompare(bName);
                });
                
                return sortedEntries.map(([empId, emp]) => {
                    const tardinessRecords = emp.tardiness.filter((r: any) => r.type === 'Tardiness');
                    const undertimeRecords = emp.tardiness.filter((r: any) => r.type === 'Undertime');
                    
                    let tardinessDates: string[] = [];
                    let tardinessHoursTotal = 0;
                    let tardinessMinutesTotal = 0;
                    tardinessRecords.forEach((tu: any) => {
                        const tuDate = new Date(tu.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        if (!tardinessDates.includes(tuDate)) {
                            tardinessDates.push(tuDate);
                        }
                        if (tu.requested_time) {
                            const returnTime = tu.return_time === 'nwd' ? '17:00' : tu.return_time;
                            if (returnTime) {
                                const timeDiff = calculateTimeDifference(tu.requested_time, returnTime);
                                tardinessHoursTotal += timeDiff.hours;
                                tardinessMinutesTotal += timeDiff.minutes;
                            }
                        }
                    });
                    tardinessHoursTotal += Math.floor(tardinessMinutesTotal / 60);
                    tardinessMinutesTotal = tardinessMinutesTotal % 60;
                    
                    let undertimeDates: string[] = [];
                    let undertimeHoursTotal = 0;
                    let undertimeMinutesTotal = 0;
                    undertimeRecords.forEach((tu: any) => {
                        const tuDate = new Date(tu.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        if (!undertimeDates.includes(tuDate)) {
                            undertimeDates.push(tuDate);
                        }
                        if (tu.requested_time) {
                            const returnTime = tu.return_time === 'nwd' ? '17:00' : tu.return_time;
                            if (returnTime) {
                                const timeDiff = calculateTimeDifference(tu.requested_time, returnTime);
                                undertimeHoursTotal += timeDiff.hours;
                                undertimeMinutesTotal += timeDiff.minutes;
                            }
                        }
                    });
                    undertimeHoursTotal += Math.floor(undertimeMinutesTotal / 60);
                    undertimeMinutesTotal = undertimeMinutesTotal % 60;
                    
                    let totalHours = tardinessHoursTotal + undertimeHoursTotal;
                    let totalMinutes = tardinessMinutesTotal + undertimeMinutesTotal;
                    totalHours += Math.floor(totalMinutes / 60);
                    totalMinutes = totalMinutes % 60;
                    
                    const tardinessDisplay = tardinessRecords.length > 0 
                        ? (tardinessHoursTotal > 0 ? tardinessHoursTotal + 'h ' + tardinessMinutesTotal + 'm' : tardinessMinutesTotal + 'm')
                        : '';
                    const undertimeDisplay = undertimeRecords.length > 0 
                        ? (undertimeHoursTotal > 0 ? undertimeHoursTotal + 'h ' + undertimeMinutesTotal + 'm' : undertimeMinutesTotal + 'm')
                        : '';
                    const totalDisplay = (tardinessRecords.length > 0 || undertimeRecords.length > 0)
                        ? (totalHours > 0 ? totalHours + 'h ' + totalMinutes + 'm' : totalMinutes + 'm')
                        : '';
                    
                    const maxRows = Math.max(emp.leaves.length, emp.passSlips.length, emp.travelOrders.length, 1);
                    return Array.from({length: maxRows}).map((_, rowIdx) => {
                        const leave = emp.leaves[rowIdx];
                        const ps = emp.passSlips[rowIdx];
                        const to = emp.travelOrders[rowIdx];
                        
                        let html = '<tr>';
                        
                        if (rowIdx === 0) {
                            html += '<td rowspan="' + maxRows + '" style="text-align: center; font-weight: bold; vertical-align: top; padding: 4px 3px; width: 8%;">' + emp.employee_id + '</td>';
                            html += '<td rowspan="' + maxRows + '" style="vertical-align: top; padding: 4px 3px; width: 12%;">' + emp.name + '</td>';
                        }
                        
                        html += '<td style="text-align: center; padding: 4px 3px; width: 10%; font-size: 11px;">' + (leave ? leave.type_of_leave : '') + '</td>';
                        let inclusiveDates = leave && leave.inclusive_dates ? formatInclusiveDatesToString(leave.inclusive_dates) : '';
                        if (leave && leave.is_half_day && leave.half_day_period) {
                            inclusiveDates += ' (' + leave.half_day_period + ')';
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 8%; font-size: 11px;">' + inclusiveDates + '</td>';
                        
                        let toDates = '';
                        if (to) {
                            if (to.inclusive_dates) {
                                toDates = formatInclusiveDatesToString(to.inclusive_dates);
                            } else if (to.from_date && to.to_date) {
                                const fromDate = new Date(to.from_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                const toDate = new Date(to.to_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                toDates = fromDate === toDate ? fromDate : fromDate + ' - ' + toDate;
                            } else if (to.date) {
                                toDates = new Date(to.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            }
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 8%; font-size: 11px;">' + toDates + '</td>';
                        
                        let psDate = '';
                        if (ps && ps.date) {
                            // Handle both single dates and date ranges in the date field
                            if (ps.date.includes(' - ')) {
                                const [startStr, endStr] = ps.date.split(' - ');
                                const startDate = new Date(startStr.trim()).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                const endDate = new Date(endStr.trim()).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                psDate = startDate + ' - ' + endDate;
                            } else {
                                psDate = new Date(ps.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            }
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + psDate + '</td>';
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + (ps ? formatTime(ps.requested_time || '') : '') + '</td>';
                        
                        let returnTimeDisplay = '';
                        if (ps) {
                            let returnTypeLabel = ps.expected_return_time || '';
                            if (ps.remarks) {
                                returnTimeDisplay = returnTypeLabel + ' (' + ps.remarks + ')';
                            } else {
                                returnTimeDisplay = returnTypeLabel;
                            }
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + returnTimeDisplay + '</td>';
                        
                        if (rowIdx === 0) {
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + tardinessDates.join(', ') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + tardinessDisplay + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;">' + (tardinessRecords.length > 0 ? tardinessRecords.length : '') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + undertimeDates.join(', ') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + undertimeDisplay + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;">' + (undertimeRecords.length > 0 ? undertimeRecords.length : '') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + totalDisplay + '</td>';
                        } else {
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                        }
                        
                        html += '</tr>';
                        return html;
                    }).join('');
                }).join('');
            })()}
        </tbody>
    </table>
    ` : `
    <table>
        <tbody>
            <tr>
                <td style="text-align: center; padding: 20px;">No data available for the selected criteria</td>
            </tr>
        </tbody>
    </table>
    `}
    
    ${summaryData.value.remarks ? `<p style="margin: 0; font-weight: bold; font-size: 11px;"><strong>*</strong> <em>${summaryData.value.remarks.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</em></p>` : ''}
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Prepared By:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(preparedByEmployee?.name || 'N/A')}</div>
            <div class="signature-designation">${preparedByEmployee?.designation || ''}</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-title">Reviewed/Certified Correct:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(certifiedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${certifiedByEmployee?.designation || ''}</div>
        </div>
    </div>
</body>
</html>
            `;

            // Open print dialog
            const reportWindow = window.open('', 'PRINT_SUMMARY', 'height=900,width=1500');
            if (reportWindow) {
                reportWindow.document.write(htmlContent);
                reportWindow.document.close();
                reportWindow.print();
            }

            showSummaryModal.value = false;
            // Reset form
            summaryData.value = {
                month: null,
                year: new Date().getFullYear(),
                employmentType: '',
                casualPeriod: '',
                preparedBy: null,
                certifiedCorrect: null,
                remarks: '',
            };
        } catch (e) {
            summaryErrors.value.submit = e instanceof Error ? e.message : 'An error occurred while generating the report';
        }
    };

    return {
        showReportModal,
        reportData,
        reportErrors,
        showSummaryModal,
        summaryData,
        summaryErrors,
        months,

        generateReport,
        generateSummaryReport,
        getEmployeeDesignation,
        capitalizeWords,
        formatDateToWords
    };
};
