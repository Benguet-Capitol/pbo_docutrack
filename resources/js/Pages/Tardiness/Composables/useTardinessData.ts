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

export interface TardinessRecord {
    id: number;
    control_no: string;
    date_filed: string;
    type: string;
    requested_date: string;
    employee_id: number;
    employee: Employee;
    requested_time: string;
    reason: string;
    return_time: string | null;
    supervisor_employee_id: number | null;
    supervisor?: Employee;
    created_at: string;
    updated_at: string;
}

export function useTardinessData() {
    const tardiness = ref<TardinessRecord[]>([]);
    const employees = ref<Employee[]>([]);
    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
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

    const filteredTardiness = computed(() => {
        let filtered = tardiness.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(record =>
                record.control_no.toLowerCase().includes(query) ||
                record.employee?.name.toLowerCase().includes(query) ||
                record.reason.toLowerCase().includes(query) ||
                record.type.toLowerCase().includes(query) ||
                formatTimeForSearch(record.requested_time).toLowerCase().includes(query) ||
                formatTimeForSearch(record.return_time).toLowerCase().includes(query)
            );
        }

        // Sort by ID in descending order (newest first)
        return filtered.sort((a, b) => b.id - a.id);
    });

    const sortedEmployees = computed(() => {
        return employees.value.slice().sort((a, b) => {
            const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
            const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
            return lastNameA.localeCompare(lastNameB);
        });
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredTardiness.value.length / itemsPerPage.value);
    });

    const paginatedTardiness = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredTardiness.value.slice(start, end);
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

    const fetchTardiness = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/tardiness');
            if (!response.ok) throw new Error('Failed to fetch records');
            const result = await response.json();
            tardiness.value = result.data || result;
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
        tardiness,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        loading,
        error,
        filteredTardiness,
        sortedEmployees,
        totalPages,
        paginatedTardiness,
        paginationRange,
        fetchTardiness,
        fetchEmployees,
        getProvincialBudgetOfficer,
        getProvincialGovernor,
        getEmployeeById,
    };
}
