import { computed, ref } from 'vue';

export function useHRSummaryData() {
    // ============== State ==============
    const selectedHRMonth = ref<number>(new Date().getMonth()); // 0-11
    const selectedHRYear = ref<number>(new Date().getFullYear());
    const expandedHRType = ref<'leaves' | 'travelOrders' | 'passSlips' | 'tardiness' | null>(null);
    const hrLoading = ref(true);

    const leaves = ref<any[]>([]);
    const travelOrders = ref<any[]>([]);
    const passSlips = ref<any[]>([]);
    const tardiness = ref<any[]>([]);

    // ============== Fetch Methods ==============
    const fetchLeaves = async () => {
        try {
            const response = await fetch('/api/leaves');
            if (response.ok) {
                leaves.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching leaves:', e);
        }
    };

    const fetchTravelOrders = async () => {
        try {
            const response = await fetch('/api/travel-orders');
            if (response.ok) {
                travelOrders.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching travel orders:', e);
        }
    };

    const fetchPassSlips = async () => {
        try {
            const response = await fetch('/api/pass-slips');
            if (response.ok) {
                passSlips.value = await response.json();
            }
        } catch (e) {
            console.error('Error fetching pass slips:', e);
        }
    };

    const fetchTardiness = async () => {
        try {
            const response = await fetch('/api/tardiness');
            if (response.ok) {
                const result = await response.json();
                tardiness.value = result.data || result;
            }
        } catch (e) {
            console.error('Error fetching tardiness:', e);
        }
    };

    // ============== Helper Functions ==============
    const leaveInSelectedMonth = (leave: any): boolean => {
        if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;
        
        const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
        const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);
        
        for (const dateEntry of leave.inclusive_dates) {
            if (!dateEntry) continue;
            
            if (dateEntry.includes(' - ')) {
                const [startStr, endStr] = dateEntry.split(' - ');
                const rangeStart = new Date(startStr.trim());
                const rangeEnd = new Date(endStr.trim());
                
                if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                    return true;
                }
            } else {
                const date = new Date(dateEntry.trim());
                if (date.getMonth() === selectedHRMonth.value && date.getFullYear() === selectedHRYear.value) {
                    return true;
                }
            }
        }
        return false;
    };

    const travelOrderInSelectedMonth = (to: any): boolean => {
        if (!to.from_date || !to.to_date) return false;
        
        const fromDate = new Date(to.from_date);
        const toDate = new Date(to.to_date);
        const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
        const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);
        
        return fromDate <= monthEnd && toDate >= monthStart;
    };

    const tardinessInSelectedMonth = (tu: any): boolean => {
        if (!tu.date_filed) return false;
        
        const tuDate = new Date(tu.date_filed);
        return tuDate.getMonth() === selectedHRMonth.value && tuDate.getFullYear() === selectedHRYear.value;
    };

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

    const formatInclusiveDates = (dates: any): string => {
        if (Array.isArray(dates)) {
            return dates.join(' - ');
        }
        return dates || '';
    };

    // ============== Computed Properties ==============
    const currentMonthLeaves = computed(() => {
        return leaves.value.filter(leave => leaveInSelectedMonth(leave));
    });

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
            if (to.from_date) {
                const year = new Date(to.from_date).getFullYear();
                yearsSet.add(year);
            }
            if (to.to_date) {
                const year = new Date(to.to_date).getFullYear();
                yearsSet.add(year);
            }
        });
        
        passSlips.value.forEach(ps => {
            const year = new Date(ps.date).getFullYear();
            yearsSet.add(year);
        });
        
        tardiness.value.forEach(tu => {
            if (tu.date_filed) {
                const year = new Date(tu.date_filed).getFullYear();
                yearsSet.add(year);
            }
        });
        
        return Array.from(yearsSet).sort((a, b) => a - b);
    });

    const currentMonthLeavesByType = computed(() => {
        const grouped: Record<string, number> = {};
        currentMonthLeaves.value.forEach(leave => {
            const type = leave.type_of_leave;
            grouped[type] = (grouped[type] || 0) + 1;
        });
        return grouped;
    });

    const currentMonthLeavesByTypeWithEmployees = computed(() => {
        const grouped: Record<string, { count: number; entries: Array<{ name: string; dates: string; isHalfDay: boolean; halfDayPeriod: string | null }> }> = {};
        currentMonthLeaves.value.forEach(leave => {
            const type = leave.type_of_leave;
            const empName = leave.employee?.name || 'Unknown Employee';
            
            let datesStr = formatInclusiveDates(leave.inclusive_dates);
            
            if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 2) {
                datesStr = `${leave.inclusive_dates[0]} - ${leave.inclusive_dates[1]}`;
                datesStr = formatInclusiveDates(datesStr);
            } else if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 1) {
                datesStr = leave.inclusive_dates[0];
                datesStr = formatInclusiveDates(datesStr);
            }
            
            if (!grouped[type]) {
                grouped[type] = { count: 0, entries: [] };
            }
            grouped[type].count += 1;
            grouped[type].entries.push({ 
                name: empName, 
                dates: datesStr,
                isHalfDay: leave.is_half_day || false,
                halfDayPeriod: leave.half_day_period || null
            });
        });
        
        Object.keys(grouped).forEach(type => {
            grouped[type].entries.sort((a, b) => a.name.localeCompare(b.name));
        });
        return grouped;
    });

    const currentMonthTravelOrders = computed(() => {
        return travelOrders.value.filter(to => travelOrderInSelectedMonth(to));
    });

    const uniqueEmployeesTravelOrders = computed(() => {
        const empSet = new Set<string>();
        currentMonthTravelOrders.value.forEach(to => {
            if (to.employees && Array.isArray(to.employees)) {
                to.employees.forEach(emp => {
                    empSet.add(emp.name);
                });
            }
        });
        return empSet;
    });

    const currentMonthTravelOrdersByEmp = computed(() => {
        const grouped: Record<string, any[]> = {};
        currentMonthTravelOrders.value.forEach(to => {
            if (to.employees && Array.isArray(to.employees)) {
                to.employees.forEach(emp => {
                    if (!grouped[emp.name]) {
                        grouped[emp.name] = [];
                    }
                    grouped[emp.name].push(to);
                });
            }
        });
        return grouped;
    });

    const currentMonthPassSlips = computed(() => {
        return passSlips.value.filter(ps => {
            const psDate = new Date(ps.date);
            return psDate.getMonth() === selectedHRMonth.value && psDate.getFullYear() === selectedHRYear.value;
        });
    });

    const uniqueEmployeesPassSlips = computed(() => {
        const empSet = new Set<string>();
        currentMonthPassSlips.value.forEach(ps => {
            if (ps.employees && Array.isArray(ps.employees)) {
                ps.employees.forEach(emp => {
                    empSet.add(emp.name);
                });
            }
        });
        return empSet;
    });

    const currentMonthPassSlipsByEmp = computed(() => {
        const grouped: Record<string, any[]> = {};
        currentMonthPassSlips.value.forEach(ps => {
            if (ps.employees && Array.isArray(ps.employees)) {
                ps.employees.forEach(emp => {
                    if (!grouped[emp.name]) {
                        grouped[emp.name] = [];
                    }
                    grouped[emp.name].push(ps);
                });
            }
        });
        return grouped;
    });

    const currentMonthTardiness = computed(() => {
        return tardiness.value.filter(tu => tardinessInSelectedMonth(tu));
    });

    const uniqueEmployeesTardiness = computed(() => {
        const empSet = new Set<string>();
        currentMonthTardiness.value.forEach(tu => {
            if (tu.employee) {
                empSet.add(tu.employee.name);
            }
        });
        return empSet;
    });

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

    const toggleHRExpanded = (type: 'leaves' | 'travelOrders' | 'passSlips' | 'tardiness') => {
        expandedHRType.value = expandedHRType.value === type ? null : type;
    };

    return {
        // State
        selectedHRMonth,
        selectedHRYear,
        expandedHRType,
        hrLoading,
        leaves,
        travelOrders,
        passSlips,
        tardiness,

        // Methods
        fetchLeaves,
        fetchTravelOrders,
        fetchPassSlips,
        fetchTardiness,
        toggleHRExpanded,

        // Computed
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
    };
}
