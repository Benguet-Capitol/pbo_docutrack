import { ref, computed } from 'vue';

export interface Employee {
    id: number;
    name: string;
    employee_id: string;
    position: string;
    salary: number;
}

export interface Leave {
    id: number;
    control_no: string;
    employee_id: number;
    employee: Employee;
    date_of_filing: string;
    type_of_leave: string;
    number_of_working_days_applied_for: number;
    is_half_day: boolean;
    half_day_period: string | null;
    inclusive_dates: string[];
    off_days: string[];
    within_philippines: boolean | null;
    purpose: string | null;
    in_hospital: boolean | null;
    illness: string | null;
    completion_type: string | null;
    other_type: string | null;
    created_at: string;
    updated_at: string;
}

export function useLeavesData() {
    const leaves = ref<Leave[]>([]);
    const employees = ref<Employee[]>([]);

    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortBy = ref('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchLeaves = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/leaves');
            if (!response.ok) throw new Error('Failed to fetch leaves');
            leaves.value = await response.json();
        } catch (err: any) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/employees');
            if (!response.ok) throw new Error('Failed to fetch employees');
            employees.value = await response.json();
        } catch (err: any) {
            console.error('Failed to load employees:', err);
        }
    };

    const formatDateForDisplay = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatInclusiveDateForSearch = (dateEntry: string): string => {
        if (!dateEntry) return '';
        
        if (dateEntry.includes(' - ')) {
            const [startStr, endStr] = dateEntry.split(' - ');
            const startDate = new Date(startStr.trim());
            const endDate = new Date(endStr.trim());
            const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            return `${start} - ${end}`;
        } else {
            const date = new Date(dateEntry.trim());
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    };

    const filteredLeaves = computed(() => {
        let filtered = leaves.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(leave => {
                // Search in control_no
                if (leave.control_no.toLowerCase().includes(query)) return true;
                
                // Search in employee name
                if (leave.employee.name.toLowerCase().includes(query)) return true;
                
                // Search in type_of_leave
                if (leave.type_of_leave.toLowerCase().includes(query)) return true;
                
                // Search in date_of_filing (both raw and formatted)
                if (leave.date_of_filing.toLowerCase().includes(query)) return true;
                if (formatDateForDisplay(leave.date_of_filing).toLowerCase().includes(query)) return true;
                
                // Search in number_of_working_days_applied_for
                if (leave.number_of_working_days_applied_for.toString().includes(query)) return true;
                
                // Search in half_day_period
                if (leave.half_day_period && leave.half_day_period.toLowerCase().includes(query)) return true;
                
                // Search in inclusive_dates (both raw and formatted)
                if (leave.inclusive_dates && leave.inclusive_dates.length > 0) {
                    if (leave.inclusive_dates.some(date => date.toLowerCase().includes(query))) return true;
                    if (leave.inclusive_dates.some(date => formatInclusiveDateForSearch(date).toLowerCase().includes(query))) return true;
                }
                
                // Search in purpose
                if (leave.purpose && leave.purpose.toLowerCase().includes(query)) return true;
                
                // Search in illness
                if (leave.illness && leave.illness.toLowerCase().includes(query)) return true;
                
                // Search in other_type
                if (leave.other_type && leave.other_type.toLowerCase().includes(query)) return true;
                
                return false;
            });
        }

        // Apply sorting
        filtered.sort((a, b) => {
            let aValue: any;
            let bValue: any;

            if (sortBy.value === 'id') {
                aValue = a.id;
                bValue = b.id;
            } else if (sortBy.value === 'date_of_filing') {
                aValue = a.date_of_filing;
                bValue = b.date_of_filing;
            } else if (sortBy.value === 'type_of_leave') {
                aValue = a.type_of_leave;
                bValue = b.type_of_leave;
            } else {
                aValue = a.employee.name;
                bValue = b.employee.name;
                aValue = aValue?.toString().toLowerCase() || '';
                bValue = bValue?.toString().toLowerCase() || '';
            }

            if (typeof aValue === 'string' && !aValue.match(/^\d{4}-\d{2}/)) {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
        });

        return filtered;
    });

    const sortedEmployees = computed(() => {
        return employees.value.slice().sort((a, b) => {
            const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
            const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
            return lastNameA.localeCompare(lastNameB);
        });
    });

    const paginatedLeaves = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredLeaves.value.slice(start, end);
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredLeaves.value.length / itemsPerPage.value);
    });

    const paginationRange = computed(() => {
        const pages: (number | string)[] = [];
        const total = totalPages.value;
        const current = currentPage.value;
        const maxButtons = 5;
        const sidePages = 1;
        const edgePages = 1;

        if (total <= maxButtons + 2) {
            for (let i = 1; i <= total; i++) {
                pages.push(i);
            }
        } else {
            for (let i = 1; i <= Math.min(edgePages + 1, total); i++) {
                pages.push(i);
            }

            const rangeStart = Math.max(edgePages + 2, current - sidePages);
            const rangeEnd = Math.min(total - edgePages - 1, current + sidePages);

            if (rangeStart > edgePages + 2) {
                if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }

            for (let i = rangeStart; i <= rangeEnd; i++) {
                if (i > edgePages + 1 && i < total - edgePages) {
                    pages.push(i);
                }
            }

            if (rangeEnd < total - edgePages - 1) {
                if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }

            for (let i = Math.max(edgePages + 2, total - edgePages); i <= total; i++) {
                if (!pages.includes(i)) {
                    pages.push(i);
                }
            }
        }

        return pages;
    });

    const toggleSort = (field: string) => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
        currentPage.value = 1;
    };

    return {
        // State
        leaves,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        loading,
        error,

        // Computed
        filteredLeaves,
        sortedEmployees,
        paginatedLeaves,
        totalPages,
        paginationRange,

        // Methods
        fetchLeaves,
        fetchEmployees,
        toggleSort,
    };
}
