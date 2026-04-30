import { computed, ref } from 'vue';

/**
 * Composable for HR data management
 */
export const useHRData = () => {
    const leaves = ref<any[]>([]);
    const travelOrders = ref<any[]>([]);
    const passSlips = ref<any[]>([]);
    const tardiness = ref<any[]>([]);
    const timeSlips = ref<any[]>([]);
    const hrLoading = ref(true);

    const selectedHRMonth = ref<number>(new Date().getMonth());
    const selectedHRYear = ref<number>(new Date().getFullYear());
    const expandedHRType = ref<'leaves' | 'travelOrders' | 'passSlips' | 'tardiness' | null>(null);

    /**
     * Fetch leaves from API
     */
    const fetchLeaves = async () => {
        try {
            const response = await fetch('/api/leaves');
            if (response.ok) {
                leaves.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching leaves:', e);
        } finally {
            hrLoading.value = false;
        }
    };

    /**
     * Fetch travel orders from API
     */
    const fetchTravelOrders = async () => {
        try {
            const response = await fetch('/api/travel-orders');
            if (response.ok) {
                travelOrders.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching travel orders:', e);
        } finally {
            hrLoading.value = false;
        }
    };

    /**
     * Fetch pass slips from API
     */
    const fetchPassSlips = async () => {
        try {
            const response = await fetch('/api/pass-slips');
            if (response.ok) {
                passSlips.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching pass slips:', e);
        } finally {
            hrLoading.value = false;
        }
    };

    /**
     * Fetch tardiness/undertime from API
     */
    const fetchTardiness = async () => {
        try {
            const response = await fetch('/api/tardiness');
            if (response.ok) {
                const result = await response.json();
                tardiness.value = result.data || result;
            }
        } catch (e) {
            console.error('Error fetching tardiness:', e);
        } finally {
            hrLoading.value = false;
        }
    };

    /**
     * Fetch time slips from API
     */
    const fetchTimeSlips = async () => {
        try {
            const response = await fetch('/api/time-slips');
            if (response.ok) {
                timeSlips.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching time slips:', e);
        } finally {
            hrLoading.value = false;
        }
    };

    /**
     * Parse date string in YYYY-MM-DD format using local time to avoid timezone issues
     */
    const parseDateString = (dateString: string): Date => {
        const [year, month, day] = dateString.trim().split('-').map(Number);
        return new Date(year, month - 1, day);
    };

    /**
     * Check if a leave falls within the selected month
     */
    const leaveInSelectedMonth = (leave: any): boolean => {
        if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;

        const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
        const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);

        for (const dateEntry of leave.inclusive_dates) {
            if (!dateEntry) continue;

            // Check if this is a date range (contains " - " with exactly 2 YYYY-MM-DD dates)
            if (dateEntry.includes(' - ')) {
                const parts = dateEntry.split(' - ');
                if (parts.length === 2) {
                    const startStr = parts[0].trim();
                    const endStr = parts[1].trim();
                    const rangeStart = parseDateString(startStr);
                    const rangeEnd = parseDateString(endStr);

                    if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                        return true;
                    }
                }
            } else {
                const date = parseDateString(dateEntry);
                if (date.getMonth() === selectedHRMonth.value && date.getFullYear() === selectedHRYear.value) {
                    return true;
                }
            }
        }
        return false;
    };

    /**
     * Get leaves for current month
     */
    const currentMonthLeaves = computed(() => {
        // Explicitly reference selectedHRMonth and selectedHRYear to ensure reactivity
        const month = selectedHRMonth.value;
        const year = selectedHRYear.value;
        return leaves.value.filter(leave => leaveInSelectedMonth(leave));
    });

    /**
     * Get available years from HR data
     */
    const availableHRYears = computed(() => {
        const yearsSet = new Set<number>();

        leaves.value.forEach(leave => {
            if (leave.inclusive_dates && Array.isArray(leave.inclusive_dates)) {
                leave.inclusive_dates.forEach((dateEntry: string) => {
                    if (dateEntry) {
                        const dateStr = dateEntry.includes(' - ') ? dateEntry.split(' - ')[0] : dateEntry;
                        const year = new Date(dateStr.trim()).getFullYear();
                        yearsSet.add(year);
                    }
                });
            }
        });

        travelOrders.value.forEach(to => {
            if (to.inclusive_dates && Array.isArray(to.inclusive_dates)) {
                to.inclusive_dates.forEach((dateEntry: string) => {
                    if (dateEntry) {
                        const dateStr = dateEntry.includes(' - ') ? dateEntry.split(' - ')[0] : dateEntry;
                        const year = new Date(dateStr.trim()).getFullYear();
                        yearsSet.add(year);
                    }
                });
            }
        });

        passSlips.value.forEach(ps => {
            if (ps.inclusive_dates && Array.isArray(ps.inclusive_dates)) {
                ps.inclusive_dates.forEach((dateEntry: string) => {
                    if (dateEntry) {
                        const dateStr = dateEntry.includes(' - ') ? dateEntry.split(' - ')[0] : dateEntry;
                        const year = new Date(dateStr.trim()).getFullYear();
                        yearsSet.add(year);
                    }
                });
            } else {
                const year = new Date(ps.date).getFullYear();
                yearsSet.add(year);
            }
        });

        tardiness.value.forEach(tu => {
            if (tu.date_filed) {
                const year = new Date(tu.date_filed).getFullYear();
                yearsSet.add(year);
            }
        });

        return Array.from(yearsSet).sort((a, b) => a - b);
    });

    /**
     * Get leaves grouped by type for current month
     */
    const currentMonthLeavesByType = computed(() => {
        const grouped: Record<string, number> = {};
        currentMonthLeaves.value.forEach(leave => {
            const type = leave.type_of_leave;
            grouped[type] = (grouped[type] || 0) + 1;
        });
        return grouped;
    });

    /**
     * Get leaves grouped by type with employees for current month
     */
    const currentMonthLeavesByTypeWithEmployees = computed(() => {
        const grouped: Record<string, { count: number; entries: Array<{ name: string; dates: string; isHalfDay: boolean; halfDayPeriod: string | null }> }> = {};
        currentMonthLeaves.value.forEach(leave => {
            const type = leave.type_of_leave;
            const empName = leave.employee?.name || 'Unknown Employee';

            if (!grouped[type]) {
                grouped[type] = { count: 0, entries: [] };
            }
            grouped[type].count += 1;
            grouped[type].entries.push({
                name: empName,
                dates: '',
                isHalfDay: leave.is_half_day || false,
                halfDayPeriod: leave.half_day_period || null
            });
        });

        Object.keys(grouped).forEach(type => {
            grouped[type].entries.sort((a, b) => a.name.localeCompare(b.name));
        });
        return grouped;
    });

    /**
     * Check if a travel order falls within the selected month
     */
    const travelOrderInSelectedMonth = (to: any): boolean => {
        const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
        const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);

        // If inclusive_dates exist and have data, use them
        if (to.inclusive_dates && Array.isArray(to.inclusive_dates) && to.inclusive_dates.length > 0) {
            for (const dateEntry of to.inclusive_dates) {
                if (!dateEntry) continue;

                // Check if this is a date range (contains " - " with exactly 2 YYYY-MM-DD dates)
                if (dateEntry.includes(' - ')) {
                    const parts = dateEntry.split(' - ');
                    if (parts.length === 2) {
                        const startStr = parts[0].trim();
                        const endStr = parts[1].trim();
                        
                        // Verify both parts look like dates (contain dashes)
                        if (startStr.match(/^\d{4}-\d{2}-\d{2}$/) && endStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            const rangeStart = parseDateString(startStr);
                            const rangeEnd = parseDateString(endStr);

                            if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                                return true;
                            }
                        }
                    }
                } else if (dateEntry.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    // Single date
                    const date = parseDateString(dateEntry);
                    if (date.getMonth() === selectedHRMonth.value && date.getFullYear() === selectedHRYear.value) {
                        return true;
                    }
                }
            }
            return false;
        }

        // Fallback to from_date and to_date for backwards compatibility (for old records)
        if ((to.from_date && to.from_date.match(/^\d{4}-\d{2}-\d{2}/)) || (to.to_date && to.to_date.match(/^\d{4}-\d{2}-\d{2}/))) {
            const fromDate = to.from_date && to.from_date.match(/^\d{4}-\d{2}-\d{2}/) ? parseDateString(to.from_date) : null;
            const toDate = to.to_date && to.to_date.match(/^\d{4}-\d{2}-\d{2}/) ? parseDateString(to.to_date) : null;

            if (fromDate && toDate) {
                return fromDate <= monthEnd && toDate >= monthStart;
            } else if (fromDate) {
                return fromDate.getMonth() === selectedHRMonth.value && fromDate.getFullYear() === selectedHRYear.value;
            } else if (toDate) {
                return toDate.getMonth() === selectedHRMonth.value && toDate.getFullYear() === selectedHRYear.value;
            }
        }

        return false;
    };

    /**
     * Get travel orders for current month
     */
    const currentMonthTravelOrders = computed(() => {
        // Explicitly reference selectedHRMonth and selectedHRYear to ensure reactivity
        const month = selectedHRMonth.value;
        const year = selectedHRYear.value;
        return travelOrders.value.filter(to => travelOrderInSelectedMonth(to));
    });

    /**
     * Get unique employees from travel orders
     */
    const uniqueEmployeesTravelOrders = computed(() => {
        const empSet = new Set<string>();
        currentMonthTravelOrders.value.forEach(to => {
            if (to.employees && Array.isArray(to.employees)) {
                to.employees.forEach((emp: any) => {
                    empSet.add(emp.name);
                });
            }
        });
        return empSet;
    });

    /**
     * Get travel orders grouped by employee
     */
    const currentMonthTravelOrdersByEmp = computed(() => {
        const grouped: Record<string, any[]> = {};
        currentMonthTravelOrders.value.forEach(to => {
            if (to.employees && Array.isArray(to.employees)) {
                to.employees.forEach((emp: any) => {
                    if (!grouped[emp.name]) {
                        grouped[emp.name] = [];
                    }
                    grouped[emp.name].push(to);
                });
            }
        });
        return grouped;
    });

    /**
     * Check if a pass slip falls within the selected month
     */
    const passSlipInSelectedMonth = (ps: any): boolean => {
        // If inclusive_dates exist, use them; otherwise fall back to date
        if (ps.inclusive_dates && Array.isArray(ps.inclusive_dates) && ps.inclusive_dates.length > 0) {
            const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
            const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);
            
            for (const dateEntry of ps.inclusive_dates) {
                if (!dateEntry) continue;
                
                // Check if this is a date range (contains " - " with exactly 2 YYYY-MM-DD dates)
                if (dateEntry.includes(' - ')) {
                    const parts = dateEntry.split(' - ');
                    if (parts.length === 2) {
                        const startStr = parts[0].trim();
                        const endStr = parts[1].trim();
                        const rangeStart = parseDateString(startStr);
                        const rangeEnd = parseDateString(endStr);
                        
                        if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                            return true;
                        }
                    }
                } else {
                    const date = parseDateString(dateEntry);
                    if (date.getMonth() === selectedHRMonth.value && date.getFullYear() === selectedHRYear.value) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        // Fallback to date if inclusive_dates not available
        const psDate = parseDateString(ps.date);
        return psDate.getMonth() === selectedHRMonth.value && psDate.getFullYear() === selectedHRYear.value;
    };

    /**
     * Get pass slips for current month
     */
    const currentMonthPassSlips = computed(() => {
        // Explicitly reference selectedHRMonth and selectedHRYear to ensure reactivity
        const month = selectedHRMonth.value;
        const year = selectedHRYear.value;
        return passSlips.value.filter(ps => passSlipInSelectedMonth(ps));
    });

    /**
     * Get unique employees from pass slips
     */
    const uniqueEmployeesPassSlips = computed(() => {
        const empSet = new Set<string>();
        currentMonthPassSlips.value.forEach(ps => {
            if (ps.employees && Array.isArray(ps.employees)) {
                ps.employees.forEach((emp: any) => {
                    empSet.add(emp.name);
                });
            }
        });
        return empSet;
    });

    /**
     * Get pass slips grouped by employee
     */
    const currentMonthPassSlipsByEmp = computed(() => {
        const grouped: Record<string, any[]> = {};
        currentMonthPassSlips.value.forEach(ps => {
            if (ps.employees && Array.isArray(ps.employees)) {
                ps.employees.forEach((emp: any) => {
                    if (!grouped[emp.name]) {
                        grouped[emp.name] = [];
                    }
                    grouped[emp.name].push(ps);
                });
            }
        });
        return grouped;
    });

    /**
     * Check if a tardiness record falls within the selected month
     */
    const tardinessInSelectedMonth = (tu: any): boolean => {
        if (!tu.date_filed) return false;

        const tuDate = new Date(tu.date_filed);
        return tuDate.getMonth() === selectedHRMonth.value && tuDate.getFullYear() === selectedHRYear.value;
    };

    /**
     * Get tardiness for current month
     */
    const currentMonthTardiness = computed(() => {
        // Explicitly reference selectedHRMonth and selectedHRYear to ensure reactivity
        const month = selectedHRMonth.value;
        const year = selectedHRYear.value;
        return tardiness.value.filter(tu => tardinessInSelectedMonth(tu));
    });

    /**
     * Get unique employees from tardiness
     */
    const uniqueEmployeesTardiness = computed(() => {
        const empSet = new Set<string>();
        currentMonthTardiness.value.forEach(tu => {
            if (tu.employee) {
                empSet.add(tu.employee.name);
            }
        });
        return empSet;
    });

    /**
     * Get tardiness grouped by employee
     */
    const currentMonthTardinesssByEmp = computed(() => {
        const grouped: Record<string, any[]> = {};
        currentMonthTardiness.value.forEach(tu => {
            const empName = tu.employee?.name || 'Unknown Employee';
            if (!grouped[empName]) {
                grouped[empName] = [];
            }
            grouped[empName].push(tu);
        });
        return grouped;
    });

    /**
     * Get unique leave types
     */
    const uniqueLeaveTypes = computed(() => {
        const allowedTypes = [
            'Sick Leave',
            'Vacation Leave',
            'Mandatory/Forced Leave',
            'Special Privilege Leave',
            'Wellness Leave'
        ];

        const typesSet = new Set<string>();
        leaves.value.forEach(leave => {
            const leaveYear = new Date(leave.date_of_filing).getFullYear();
            if (leaveYear === selectedHRYear.value && allowedTypes.includes(leave.type_of_leave)) {
                typesSet.add(leave.type_of_leave);
            }
        });

        return allowedTypes.filter(type => typesSet.has(type));
    });

    /**
     * Get leave business days
     */
    const getLeaveBusinessDays = (leave: any): number => {
        if (leave.is_half_day === true) {
            return 0.5;
        }

        if (leave.number_of_working_days_applied_for && leave.number_of_working_days_applied_for > 0) {
            return leave.number_of_working_days_applied_for;
        }

        if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length > 0) {
            let businessDays = 0;
            leave.inclusive_dates.forEach((dateStr: string) => {
                const date = new Date(dateStr);
                const dayOfWeek = date.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    businessDays++;
                }
            });
            return businessDays;
        }

        return 1;
    };

    /**
     * Get employee leaves summary
     */
    const employeeLeavesSummary = computed(() => {
        const empMap = new Map<number, {
            employeeId: number;
            employeeName: string;
            leaveTypes: Record<string, number>;
            totalLeaves: number
        }>();

        leaves.value.forEach(leave => {
            const leaveYear = new Date(leave.date_of_filing).getFullYear();
            if (leaveYear !== selectedHRYear.value) return;

            const empId = leave.employee_id;
            const empName = leave.employee?.name || 'Unknown Employee';
            const leaveType = leave.type_of_leave;
            const businessDays = getLeaveBusinessDays(leave);

            if (!empMap.has(empId)) {
                empMap.set(empId, {
                    employeeId: empId,
                    employeeName: empName,
                    leaveTypes: {},
                    totalLeaves: 0
                });
            }

            const empData = empMap.get(empId)!;
            empData.leaveTypes[leaveType] = (empData.leaveTypes[leaveType] || 0) + businessDays;
            empData.totalLeaves += businessDays;
        });

        return Array.from(empMap.values()).sort((a, b) => {
            const getLastName = (name: string) => {
                const parts = name.trim().split(/\s+/);
                return parts[parts.length - 1];
            };

            const lastNameA = getLastName(a.employeeName);
            const lastNameB = getLastName(b.employeeName);
            const lastNameComparison = lastNameA.localeCompare(lastNameB);
            if (lastNameComparison !== 0) return lastNameComparison;

            return a.employeeName.localeCompare(b.employeeName);
        });
    });

    /**
     * Toggle HR expansion
     */
    const toggleHRExpanded = (type: 'leaves' | 'travelOrders' | 'passSlips' | 'tardiness') => {
        expandedHRType.value = expandedHRType.value === type ? null : type;
    };

    /**
     * Fetch all HR data at once
     */
    const fetchAllHRData = async () => {
        hrLoading.value = true;
        try {
            await Promise.all([
                fetchLeaves(),
                fetchTravelOrders(),
                fetchPassSlips(),
                fetchTardiness(),
                fetchTimeSlips()
            ]);
        } finally {
            hrLoading.value = false;
        }
    };

    return {
        // State
        leaves,
        travelOrders,
        passSlips,
        tardiness,
        timeSlips,
        hrLoading,
        selectedHRMonth,
        selectedHRYear,
        expandedHRType,

        // Methods
        fetchLeaves,
        fetchTravelOrders,
        fetchPassSlips,
        fetchTardiness,
        fetchTimeSlips,
        fetchAllHRData,

        // Computed properties
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

        // Actions
        toggleHRExpanded
    };
};
