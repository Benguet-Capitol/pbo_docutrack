import { ref } from 'vue';

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
        loading,
        error,
        fetchTimeSlips,
        fetchEmployees,
        createTimeSlip,
        updateTimeSlip,
        deleteTimeSlip,
    };
}
