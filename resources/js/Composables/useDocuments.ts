import { computed, ref, Ref } from 'vue';

interface Document {
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
    transactions: any[];
}

/**
 * Composable for document management and filtering
 */
export const useDocuments = () => {
    const documents = ref<Document[]>([]);
    const searchQuery = ref('');
    const sortBy = ref('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const itemsPerPage = ref(10);
    const currentPage = ref(1);
    const expandedDocumentId = ref<number | null>(null);
    const loading = ref(true);
    const error = ref('');
    const selectedYear = ref<number | null>(new Date().getFullYear());
    const selectedSemester = ref<number | null>(null);
    const selectedUser = ref<number | null>(null);

    // Reference data
    const offices = ref<Array<{ id: number; office_name: string }>>([]);
    const municipalities = ref<Array<{ id: number; name: string }>>([]);
    const users = ref<Array<{ id: number; name: string; usertype?: string }>>([]);
    const employees = ref<Array<{ id: number; employee_id: string; name: string; office_id: number; designation: string }>>([]);

    /**
     * Fetch documents and reference data
     */
    const fetchDocuments = async () => {
        try {
            loading.value = true;
            error.value = '';
            const response = await fetch('/api/documents');
            if (!response.ok) {
                throw new Error('Failed to fetch documents');
            }
            documents.value = await response.json();

            const officesResponse = await fetch('/api/offices');
            if (officesResponse.ok) {
                offices.value = await officesResponse.json();
            }

            const municipalitiesResponse = await fetch('/api/municipalities');
            if (municipalitiesResponse.ok) {
                municipalities.value = await municipalitiesResponse.json();
            }

            const usersResponse = await fetch('/api/users');
            if (usersResponse.ok) {
                users.value = await usersResponse.json();
            }

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

    /**
     * Extract unique years from documents
     */
    const availableYears = computed(() => {
        const years = new Set<number>();
        documents.value.forEach(doc => {
            const year = new Date(doc.date).getFullYear();
            years.add(year);
        });
        return Array.from(years).sort((a, b) => b - a);
    });

    /**
     * Extract users who have transactions
     */
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

    /**
     * Get semester from date (1 = Jan-Jun, 2 = Jul-Dec)
     */
    const getSemester = (date: string | Date): number => {
        const d = new Date(date);
        const month = d.getMonth() + 1;
        return month <= 6 ? 1 : 2;
    };

    /**
     * Check if document falls within selected year and semester
     */
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

    /**
     * Filtered documents based on search and filters
     */
    const filteredDocuments = computed(() => {
        let filtered = documents.value.filter(document => {
            const searchLower = searchQuery.value.toLowerCase();
            
            // Search in basic fields
            const matchesBasicSearch = document.tracking_no.toLowerCase().includes(searchLower) ||
                document.date.toLowerCase().includes(searchLower) ||
                document.document_type.toLowerCase().includes(searchLower) ||
                document.source?.toLowerCase().includes(searchLower) ||
                document.particulars?.toLowerCase().includes(searchLower);
            
            // Search in status field
            const matchesStatus = document.status?.toLowerCase().includes(searchLower);
            
            // Search in custodian name (derived from transactions and reference data)
            const matchesCustodian = (() => {
                if (!document.transactions || document.transactions.length === 0) {
                    return document.user?.name?.toLowerCase().includes(searchLower) || false;
                }

                const latestTransaction = document.transactions[0];
                
                // Check if custodian is an office
                if (latestTransaction.forwarded_to_office_id) {
                    const office = offices.value.find(o => o.id === latestTransaction.forwarded_to_office_id);
                    if (office?.office_name.toLowerCase().includes(searchLower)) return true;
                }
                
                // Check if custodian is a municipality
                if (latestTransaction.forwarded_to_municipality_id) {
                    const municipality = municipalities.value.find(m => m.id === latestTransaction.forwarded_to_municipality_id);
                    if (municipality?.name.toLowerCase().includes(searchLower)) return true;
                }
                
                // Check if custodian is a user
                if (latestTransaction.forwarded_to_user_id) {
                    const user = users.value.find(u => u.id === latestTransaction.forwarded_to_user_id);
                    if (user?.name?.toLowerCase().includes(searchLower)) return true;
                }
                
                // Check original user
                if (latestTransaction.user_id) {
                    const user = users.value.find(u => u.id === latestTransaction.user_id);
                    if (user?.name?.toLowerCase().includes(searchLower)) return true;
                }
                
                return false;
            })();
            
            // Combine all search conditions
            const matchesSearch = matchesBasicSearch || matchesStatus || matchesCustodian;

            if (!matchesSearch) return false;

            if (!matchesYearAndSemester(document.date)) return false;

            if (selectedUser.value) {
                const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
                if (!userHasTransaction) return false;
            }

            return true;
        });

        // Sort
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

    /**
     * Paginated documents
     */
    const paginatedDocuments = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredDocuments.value.slice(start, end);
    });

    /**
     * Total number of pages
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
    });

    /**
     * Toggle document expansion
     */
    const toggleExpanded = (documentId: number) => {
        expandedDocumentId.value = expandedDocumentId.value === documentId ? null : documentId;
    };

    /**
     * Change page
     */
    const changePage = (page: number) => {
        if (page >= 1 && page <= totalPages.value) {
            currentPage.value = page;
        }
    };

    /**
     * Get documents for a specific document type
     */
    const getDocumentsForType = (documentType: string): Document[] => {
        return filteredDocuments.value.filter(doc => {
            return doc.document_type === documentType;
        });
    };

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

    return {
        // State
        documents,
        searchQuery,
        sortBy,
        sortOrder,
        itemsPerPage,
        currentPage,
        expandedDocumentId,
        loading,
        error,
        selectedYear,
        selectedSemester,
        selectedUser,
        offices,
        municipalities,
        users,
        employees,
        
        // Methods
        fetchDocuments,
        
        // Computed properties
        availableYears,
        availableUsers,
        getSemester,
        matchesYearAndSemester,
        filteredDocuments,
        paginatedDocuments,
        totalPages,
        
        // Actions
        toggleExpanded,
        changePage,
        getDocumentsForType,
        getEmployeeDesignation
    };
};
