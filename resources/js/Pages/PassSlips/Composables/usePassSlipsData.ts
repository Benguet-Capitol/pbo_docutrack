import { ref, computed } from 'vue';

export interface Employee {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
    office_id?: number;
    office?: {
        id: number;
        name: string;
    };
}

export interface PassSlip {
    id: number;
    control_no: string;
    date: string;
    inclusive_dates?: string[];
    requested_time: string;
    purpose: string;
    location: string;
    expected_return_time: string;
    remarks: string | null;
    vehicle?: string;
    employees: Employee[];
    recommending_approval_employee_id: number | null;
    created_at: string;
    updated_at: string;
}

export function usePassSlipsData() {
    const passSlips = ref<PassSlip[]>([]);
    const employees = ref<Employee[]>([]);
    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortBy = ref<'id' | 'control_no' | 'date' | 'requested_time' | 'purpose' | 'location'>('control_no');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Helper function to format time for search
    const formatTimeForSearch = (timeStr: string | null): string => {
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

    const formattedDate = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const filteredPassSlips = computed(() => {
        let filtered = passSlips.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(slip =>
                slip.control_no.toLowerCase().includes(query) ||
                formattedDate(slip.date).toLowerCase().includes(query) ||
                formatTimeForSearch(slip.requested_time).toLowerCase().includes(query) ||
                slip.purpose.toLowerCase().includes(query) ||
                slip.location.toLowerCase().includes(query) ||
                formatTimeForSearch(slip.expected_return_time).toLowerCase().includes(query) ||
                slip.employees.some(emp => emp.name.toLowerCase().includes(query))
            );
        }

        // Sort by field and order
        filtered.sort((a, b) => {
            let aVal: any;
            let bVal: any;

            if (sortBy.value === 'id') {
                aVal = a.id;
                bVal = b.id;
            } else {
                aVal = a[sortBy.value as keyof PassSlip];
                bVal = b[sortBy.value as keyof PassSlip];
                aVal = aVal?.toString().toLowerCase() || '';
                bVal = bVal?.toString().toLowerCase() || '';
            }

            let comparison = 0;
            if (aVal < bVal) comparison = -1;
            if (aVal > bVal) comparison = 1;
            return sortOrder.value === 'asc' ? comparison : -comparison;
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

    const totalPages = computed(() => {
        return Math.ceil(filteredPassSlips.value.length / itemsPerPage.value);
    });

    const paginatedPassSlips = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredPassSlips.value.slice(start, end);
    });

    const paginationRange = computed(() => {
        const range: (number | string)[] = [];
        const maxPages = 5;
        const halfPages = Math.floor(maxPages / 2);

        if (totalPages.value <= maxPages) {
            for (let i = 1; i <= totalPages.value; i++) {
                range.push(i);
            }
        } else {
            let start = Math.max(1, currentPage.value - halfPages);
            let end = Math.min(totalPages.value, start + maxPages - 1);

            if (end - start < maxPages - 1) {
                start = Math.max(1, end - maxPages + 1);
            }

            if (start > 1) {
                range.push(1);
                if (start > 2) range.push('...');
            }

            for (let i = start; i <= end; i++) {
                range.push(i);
            }

            if (end < totalPages.value) {
                if (end < totalPages.value - 1) range.push('...');
                range.push(totalPages.value);
            }
        }

        return range;
    });

    const fetchPassSlips = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/pass-slips');
            if (!response.ok) throw new Error('Failed to fetch pass slips');
            const result = await response.json();
            passSlips.value = result.data || result;
        } catch (err: any) {
            error.value = err.message;
            throw err;
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

    const getProvincialBudgetOfficer = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer') || null;
    };

    const getProvincialGovernor = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
    };

    const getEmployeeById = (id: number): Employee | null => {
        return employees.value.find(emp => emp.id === id) || null;
    };

    return {
        passSlips,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        loading,
        error,
        filteredPassSlips,
        sortedEmployees,
        totalPages,
        paginatedPassSlips,
        paginationRange,
        fetchPassSlips,
        fetchEmployees,
        getProvincialBudgetOfficer,
        getProvincialGovernor,
        getEmployeeById,
        formattedDate,
        formatTimeForSearch,
    };
}
