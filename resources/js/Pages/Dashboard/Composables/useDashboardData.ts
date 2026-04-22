import { computed, ref, Ref } from 'vue';

export interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id: number | null;
    forwarded_to_office_id: number | null;
    forwarded_to_municipality_id: number | null;
    action: string;
    remarks: string;
    created_at: string;
    duration_hours: number;
    user?: { id: number; name: string; email: string } | null;
    forwardedToUser?: { id: number; name: string; email: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
}

export interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string;
    source: string;
    status: string;
    remarks: string;
    user_id: number;
    user?: { id: number; name: string; email: string } | null;
    transactions: DocumentTransaction[];
}

export function useDashboardData(currentTime: Ref<Date>) {
    // ============== State ==============
    const documents = ref<Document[]>([]);
    const searchQuery = ref('');
    const sortBy = ref('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const itemsPerPage = ref(10);
    const currentPage = ref(1);
    const selectedYear = ref<number | null>(new Date().getFullYear());
    const selectedSemester = ref<number | null>(null);
    const selectedUser = ref<number | null>(null);
    const loading = ref(true);
    const error = ref('');

    const offices = ref<Array<{id: number; office_name: string}>>([]);
    const municipalities = ref<Array<{id: number; name: string}>>([]);
    const users = ref<Array<{id: number; name: string; usertype?: string}>>([]);
    const employees = ref<Array<{id: number; employee_id: string; name: string; office_id: number; designation: string}>>([]);

    // ============== Fetch Data ==============
    const fetchDocuments = async () => {
        try {
            loading.value = true;
            error.value = '';
            const response = await fetch('/api/documents');
            if (!response.ok) {
                throw new Error('Failed to fetch documents');
            }
            documents.value = await response.json();
            
            // Fetch offices and municipalities
            const officesResponse = await fetch('/api/offices');
            if (officesResponse.ok) {
                offices.value = await officesResponse.json();
            }
            
            const municipalitiesResponse = await fetch('/api/municipalities');
            if (municipalitiesResponse.ok) {
                municipalities.value = await municipalitiesResponse.json();
            }
            
            // Fetch users for lookup
            const usersResponse = await fetch('/api/users');
            if (usersResponse.ok) {
                users.value = await usersResponse.json();
            }
            
            // Fetch employees for designations
            const employeesResponse = await fetch('/api/employees');
            if (employeesResponse.ok) {
                employees.value = await employeesResponse.json();
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred while fetching documents';
            console.error('Error fetching documents:', error.value);
        } finally {
            loading.value = false;
        }
    };

    // ============== Computed Properties ==============
    const availableYears = computed(() => {
        const years = new Set<number>();
        documents.value.forEach(doc => {
            const year = new Date(doc.date).getFullYear();
            years.add(year);
        });
        return Array.from(years).sort((a, b) => b - a);
    });

    const availableUsers = computed(() => {
        const usersMap = new Map<number, string>();
        documents.value.forEach(doc => {
            doc.transactions?.forEach(transaction => {
                if (transaction.user && !usersMap.has(transaction.user.id)) {
                    usersMap.set(transaction.user.id, transaction.user.name);
                }
            });
        });
        return Array.from(usersMap, ([id, name]) => ({ id, name }))
            .sort((a, b) => a.name.localeCompare(b.name));
    });

    const getSemester = (date: string | Date): number => {
        const d = new Date(date);
        const month = d.getMonth() + 1;
        return month <= 6 ? 1 : 2;
    };

    const matchesYearAndSemester = (date: string): boolean => {
        if (!selectedYear.value && !selectedSemester.value) {
            return true;
        }
        
        const docDate = new Date(date);
        const docYear = docDate.getFullYear();
        const docSemester = getSemester(date);
        
        if (selectedYear.value && docYear !== selectedYear.value) {
            return false;
        }
        if (selectedSemester.value && docSemester !== selectedSemester.value) {
            return false;
        }
        
        return true;
    };

    const filteredDocuments = computed(() => {
        let filtered = documents.value.filter(document => {
            const matchesSearch = document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase());
            
            if (!matchesSearch) return false;
            
            if (!matchesYearAndSemester(document.date)) return false;
            
            if (selectedUser.value) {
                const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
                if (!userHasTransaction) return false;
            }
            
            return true;
        });

        filtered.sort((a, b) => {
            let aValue = a[sortBy.value as keyof Document];
            let bValue = b[sortBy.value as keyof Document];

            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
            }
            if (typeof bValue === 'string') {
                bValue = bValue.toLowerCase();
            }

            if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
        });

        return filtered;
    });

    const paginatedDocuments = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredDocuments.value.slice(start, end);
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
    });

    const supervisorUsers = computed(() => {
        return users.value.filter(user => user.usertype === 'Supervisor');
    });

    const administratorUsers = computed(() => {
        return users.value.filter(user => user.usertype === 'Administrator');
    });

    const administrativeStaffEmployees = computed(() => {
        const adminUsers = users.value.filter(user => user.usertype === 'Administrative');
        return adminUsers.map(user => {
            const emp = employees.value.find(e => e.name === user.name);
            return emp || { id: user.id, name: user.name, designation: '' };
        });
    });

    return {
        // State
        documents,
        searchQuery,
        sortBy,
        sortOrder,
        itemsPerPage,
        currentPage,
        selectedYear,
        selectedSemester,
        selectedUser,
        loading,
        error,
        offices,
        municipalities,
        users,
        employees,

        // Methods
        fetchDocuments,

        // Computed
        availableYears,
        availableUsers,
        filteredDocuments,
        paginatedDocuments,
        totalPages,
        supervisorUsers,
        administratorUsers,
        administrativeStaffEmployees,
        getSemester,
        matchesYearAndSemester,
    };
}
