import { ref, computed } from 'vue';

export interface Employee {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
}

export interface CertificateOfAppearance {
    id: number;
    control_no: string;
    name: string; // Employee name (string, not ID)
    office: string; // Office name (string, not ID)
    purpose: string;
    date: string;
    remarks?: string | null;
    created_at: string;
    updated_at: string;
}

export function useCoaData() {
    const certificates = ref<CertificateOfAppearance[]>([]);
    const employees = ref<Employee[]>([]);
    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortBy = ref<'id' | 'control_no' | 'date' | 'purpose' | 'name'>('control_no');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const loading = ref(false);
    const error = ref<string | null>(null);

    const formattedDate = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    const filteredCertificates = computed(() => {
        let filtered = certificates.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(cert =>
                cert.control_no.toLowerCase().includes(query) ||
                cert.name.toLowerCase().includes(query) ||
                cert.purpose.toLowerCase().includes(query) ||
                formattedDate(cert.date).toLowerCase().includes(query) ||
                cert.office.toLowerCase().includes(query) ||
                (cert.remarks && cert.remarks.toLowerCase().includes(query))
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
                aVal = a[sortBy.value as keyof CertificateOfAppearance];
                bVal = b[sortBy.value as keyof CertificateOfAppearance];
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
        return Math.ceil(filteredCertificates.value.length / itemsPerPage.value);
    });

    const paginatedCertificates = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredCertificates.value.slice(start, end);
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

    const fetchCertificates = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/certificate-of-appearances');
            if (!response.ok) throw new Error('Failed to fetch certificates');
            certificates.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/employees');
            if (!response.ok) throw new Error('Failed to fetch employees');
            employees.value = await response.json();
        } catch (err) {
            console.error('Error fetching employees:', err);
        }
    };

    const fetchAllData = async () => {
        await Promise.all([fetchCertificates(), fetchEmployees()]);
    };

    return {
        certificates,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        loading,
        error,
        filteredCertificates,
        sortedEmployees,
        totalPages,
        paginatedCertificates,
        paginationRange,
        formattedDate,
        fetchCertificates,
        fetchEmployees,
        fetchAllData,
    };
}
