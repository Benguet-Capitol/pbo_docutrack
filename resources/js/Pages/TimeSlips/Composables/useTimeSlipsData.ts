import { ref, computed } from 'vue';

export interface Employee {
    id: number;
    name: string;
    designation?: string;
}

export interface TimeSlip {
    id: number;
    control_no: string;
    requesting_employee_id: number;
    date: string;
    time_in_am: string | null;
    time_out_am: string | null;
    time_in_pm: string | null;
    time_out_pm: string | null;
    reason: string;
    certified_by_employee_id: number | null;
    requesting_employee?: Employee;
    certified_by_employee?: Employee | null;
    created_at: string;
    updated_at: string;
}

export function useTimeSlipsData() {
    const timeSlips = ref<TimeSlip[]>([]);
    const employees = ref<Employee[]>([]);
    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortBy = ref<'id' | 'control_no' | 'date' | 'reason'>('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchTimeSlips = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/time-slips');
            if (!response.ok) throw new Error('Failed to fetch time slips');
            timeSlips.value = await response.json();
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/employees');
            if (!response.ok) throw new Error('Failed to fetch employees');
            employees.value = await response.json();
        } catch (e) {
            console.error('Error fetching employees:', e);
        }
    };

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

    const formatDateForDisplay = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const filteredTimeSlips = computed(() => {
        let filtered = timeSlips.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(slip => {
                // Search in control_no
                if (slip.control_no.toLowerCase().includes(query)) return true;
                
                // Search in date (both raw and formatted)
                if (slip.date.toLowerCase().includes(query)) return true;
                if (formatDateForDisplay(slip.date).toLowerCase().includes(query)) return true;
                
                // Search in employee name
                if (slip.requesting_employee?.name.toLowerCase().includes(query)) return true;
                
                // Search in time fields (both raw and formatted)
                if (slip.time_in_am && slip.time_in_am.toLowerCase().includes(query)) return true;
                if (slip.time_in_am && formatTimeForSearch(slip.time_in_am).toLowerCase().includes(query)) return true;
                
                if (slip.time_out_am && slip.time_out_am.toLowerCase().includes(query)) return true;
                if (slip.time_out_am && formatTimeForSearch(slip.time_out_am).toLowerCase().includes(query)) return true;
                
                if (slip.time_in_pm && slip.time_in_pm.toLowerCase().includes(query)) return true;
                if (slip.time_in_pm && formatTimeForSearch(slip.time_in_pm).toLowerCase().includes(query)) return true;
                
                if (slip.time_out_pm && slip.time_out_pm.toLowerCase().includes(query)) return true;
                if (slip.time_out_pm && formatTimeForSearch(slip.time_out_pm).toLowerCase().includes(query)) return true;
                
                // Search in reason
                if (slip.reason.toLowerCase().includes(query)) return true;
                
                // Search in certified_by_employee name
                if (slip.certified_by_employee?.name.toLowerCase().includes(query)) return true;
                
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
            } else if (sortBy.value === 'date') {
                aValue = a.date;
                bValue = b.date;
            } else if (sortBy.value === 'control_no') {
                aValue = a.control_no;
                bValue = b.control_no;
            } else {
                aValue = a.reason;
                bValue = b.reason;
            }

            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
        });

        return filtered;
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredTimeSlips.value.length / itemsPerPage.value);
    });

    const paginatedTimeSlips = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredTimeSlips.value.slice(start, end);
    });

    const sortedEmployees = computed(() => {
        return employees.value.slice().sort((a, b) => {
            const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
            const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
            return lastNameA.localeCompare(lastNameB);
        });
    });

    const createTimeSlip = async (data: any) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/time-slips', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const error = await response.json();
            throw error;
        }

        return await response.json();
    };

    const updateTimeSlip = async (id: number, data: any) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/time-slips/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            const error = await response.json();
            throw error;
        }

        return await response.json();
    };

    const deleteTimeSlip = async (id: number) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/time-slips/${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw error;
        }

        return await response.json();
    };

    return {
        timeSlips,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        filteredTimeSlips,
        paginatedTimeSlips,
        totalPages,
        sortedEmployees,
        loading,
        error,
        fetchTimeSlips,
        fetchEmployees,
        createTimeSlip,
        updateTimeSlip,
        deleteTimeSlip,
    };
}
