import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface Record {
    id: number;
    record_no: string;
    record_type: string;
    record_subtype: string | null;
    title: string;
    remarks: string | null;
    image_path: string | null;
    file_extension?: string;
    file_size?: number;
    user_id: number;
    created_at: string;
    updated_at: string;
}

export function useRecordsData() {
    const records = ref<Record[]>([]);
    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const loading = ref(true);
    const error = ref<string | null>(null);
    const activeTab = ref('Provincial Budget');
    const activeSubtype = ref<string | null>(null);
    const sortBy = ref<'id' | 'record_no' | 'title' | 'created_at' | 'record_subtype' | 'remarks' | 'file_extension'>('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');

    const page = usePage();
    const usertype = computed(() => (page.props as any).auth?.user?.usertype || '');

    // Record Types with Hierarchical Structure (Types and Sub-types)
    // "Administrative" is only visible to users with the Administrative role
    const recordTypesHierarchy = computed(() => {
        const hierarchy: { [key: string]: string[] } = {
            'Provincial Budget': [],
            'Municipal Budget': ['Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
            'Issuances / Circulars / Other References and Documents': [
                'Provincial Ordinances',
                'DILG Memorandum Circulars',
                'DOF Department Orders',
                'Memorandum Circulars (Office of the President)',
                'PAG-IBIG',
                'COMELEC Resolutions',
                'DBM Budget Circulars',
                'COA Circulars',
                'CSC Circulars',
                'Local Budget Circulars',
                'Local Budget Memorandums',
                'DBM Orders / Circular Letters',
                'Joint Circulars',
                'Executive Orders (Office of the President)',
                'Presidential Decrees',
                'Republic Acts',
                'GPPB Circulars',
                'GSIS Memorandum Circulars',
                'DOH Circulars / Administrative Orders',
                'PHIC Circulars',
                'National Budget Circulars / Memorandums',
                'CHED Memorandum Circulars',
                'Budget Call',
                'Queries',
                'Annual Budget Transmittal / Indorsement to SPO',
                'Transmitted PPMPs to BAC',
                'SP Indorsement to DBM (APB)',
                'PBO Certifications to Plans / Other Reports',
                'DBM Letters / Reports / Matters',
                'List of Documentary Requirements',
                'Provincial NTAs',
                'Municipal NTAs',
                'PLGU Annual Budget Review'
            ]
        };

        if (['Administrative', 'Administrator', 'Developer'].includes(usertype.value)) {
            hierarchy['Administrative'] = [];
        }

        return hierarchy;
    });

    const recordTypes = computed(() => Object.keys(recordTypesHierarchy.value));

    const filteredRecords = computed(() => {
        let filtered = records.value.filter(record => {
            const typeMatches = record.record_type === activeTab.value;
            const subtypeMatches = activeSubtype.value === null || record.record_subtype === activeSubtype.value;
            return typeMatches && subtypeMatches;
        });

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(record =>
                record.record_no.toLowerCase().includes(query) ||
                record.record_type.toLowerCase().includes(query) ||
                (record.record_subtype?.toLowerCase() || '').includes(query) ||
                record.title.toLowerCase().includes(query) ||
                (record.file_extension?.toLowerCase() || '').includes(query) ||
                (record.file_size?.toString() || '').includes(query) ||
                (record.remarks?.toLowerCase() || '').includes(query)
            );
        }

        filtered.sort((a, b) => {
            let aVal: any = a[sortBy.value as keyof Record];
            let bVal: any = b[sortBy.value as keyof Record];

            // Handle numeric fields
            if (sortBy.value === 'id') {
                const aNum = Number(aVal) || 0;
                const bNum = Number(bVal) || 0;
                return sortOrder.value === 'asc' ? aNum - bNum : bNum - aNum;
            }

            aVal = aVal?.toString().toLowerCase() || '';
            bVal = bVal?.toString().toLowerCase() || '';

            let comparison = 0;
            if (aVal < bVal) comparison = -1;
            if (aVal > bVal) comparison = 1;
            return sortOrder.value === 'asc' ? comparison : -comparison;
        });

        return filtered;
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredRecords.value.length / itemsPerPage.value);
    });

    const paginatedRecords = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredRecords.value.slice(start, end);
    });

    const paginationPages = computed(() => {
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

    const fetchRecords = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/records');
            if (!response.ok) throw new Error('Failed to fetch records');
            records.value = await response.json();
        } catch (err: any) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    const toggleSort = (field: 'id' | 'record_no' | 'title' | 'created_at' | 'record_subtype' | 'remarks' | 'file_extension') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    return {
        records,
        searchQuery,
        currentPage,
        itemsPerPage,
        loading,
        error,
        activeTab,
        activeSubtype,
        sortBy,
        sortOrder,
        recordTypesHierarchy,
        recordTypes,
        filteredRecords,
        totalPages,
        paginatedRecords,
        paginationPages,
        fetchRecords,
        toggleSort,
    };
}
