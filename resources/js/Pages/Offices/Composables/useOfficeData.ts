import { ref, computed, onMounted } from 'vue';

/**
 * Office interface defines the structure of office data
 * Represents a single office entity with all its properties
 */
export interface Office {
    id: number;
    office_abbreviation: string;
    office_name: string;
    sub_office: string | null;
    fund: string | null;
    fpp_code: string | null;
    responsibility_code: string | null;
    branch: string | null;
}

/**
 * useOfficeData: Composable for managing office data
 * Handles fetching, filtering, sorting, and pagination of offices
 */
export function useOfficeData() {
    // ============== State ==============
    
    /** Stores the list of all offices fetched from the API */
    const offices = ref<Office[]>([]);

    /** Tracks the loading state of the page */
    const loading = ref(true);

    /** Stores error messages if any API call fails */
    const error = ref<string | null>(null);

    /** Stores the search query for filtering offices */
    const searchQuery = ref('');

    /** Current page number for pagination */
    const currentPage = ref(1);

    /** Number of items to display per page */
    const itemsPerPage = ref(10);

    /** The field to sort offices by */
    const sortBy = ref<'id' | 'office_name' | 'office_abbreviation' | 'fpp_code' | 'responsibility_code' | 'branch' | 'fund' | 'sub_office'>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'asc' | 'desc'>('asc');

    // ============== Computed Properties ==============

    /**
     * filteredOffices: Filters and sorts offices based on search query and sort settings
     * - Filters offices by multiple fields (abbreviation, name, fund, codes, etc.)
     * - Applies sorting based on sortBy and sortOrder
     * Returns the filtered and sorted array of offices
     */
    const filteredOffices = computed(() => {
        let filtered = offices.value.filter(office => 
            office.office_abbreviation.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            office.office_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            office.fund?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            office.fpp_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            office.responsibility_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            office.branch?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (office.sub_office?.toLowerCase().includes(searchQuery.value.toLowerCase()) ?? false)
        );
        
        // Sort
        filtered.sort((a, b) => {
            let aVal = a[sortBy.value] || '';
            let bVal = b[sortBy.value] || '';
            let comparison = 0;
            if (aVal < bVal) comparison = -1;
            if (aVal > bVal) comparison = 1;
            return sortOrder.value === 'asc' ? comparison : -comparison;
        });
        
        return filtered;
    });

    /**
     * totalPages: Calculates the total number of pages needed for pagination
     * Based on the filtered offices count and items per page
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredOffices.value.length / itemsPerPage.value);
    });

    /**
     * paginatedOffices: Slices filtered offices to show only the current page
     * Calculates start and end indices based on currentPage and itemsPerPage
     * Returns the offices for the current page only
     */
    const paginatedOffices = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredOffices.value.slice(start, end);
    });

    // ============== Methods ==============

    /**
     * fetchOffices: Fetches offices from the API
     * - Sets loading state to true initially
     * - Makes API request to /api/offices
     * - Populates offices.value with response data
     * - Handles errors and sets loading state to false on completion
     */
    const fetchOffices = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/offices');
            if (!response.ok) {
                throw new Error('Failed to fetch offices');
            }
            offices.value = await response.json();
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    /**
     * changePage: Navigate to a specific page
     * @param {number} page - The page number to navigate to
     * Validates that page is within valid range (1 to totalPages)
     */
    const changePage = (page: number) => {
        if (page >= 1 && page <= totalPages.value) {
            currentPage.value = page;
        }
    };

    /**
     * toggleSort: Toggle sorting by a specific field
     * @param {string} field - The field to sort by
     * If already sorting by this field, toggles between asc/desc
     * If sorting by a different field, sets it as the new sort and resets to asc
     */
    const toggleSort = (field: 'id' | 'office_name' | 'office_abbreviation' | 'fpp_code' | 'responsibility_code' | 'branch' | 'fund' | 'sub_office') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    /**
     * addOffice: Adds a new office to the list
     * @param {Office} office - The office to add
     */
    const addOffice = (office: Office) => {
        offices.value.push(office);
    };

    /**
     * updateOffice: Updates an office in the list
     * @param {number} id - The ID of the office to update
     * @param {Office} updatedOffice - The updated office data
     */
    const updateOffice = (id: number, updatedOffice: Office) => {
        const index = offices.value.findIndex(o => o.id === id);
        if (index !== -1) {
            offices.value[index] = updatedOffice;
        }
    };

    /**
     * deleteOffice: Removes an office from the list
     * @param {number} id - The ID of the office to delete
     */
    const deleteOffice = (id: number) => {
        offices.value = offices.value.filter(o => o.id !== id);
    };

    // ============== Lifecycle ==============

    /**
     * Initialize: Fetch offices on component mount
     */
    onMounted(async () => {
        await fetchOffices();
    });

    return {
        // State
        offices,
        loading,
        error,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,

        // Computed
        filteredOffices,
        totalPages,
        paginatedOffices,

        // Methods
        fetchOffices,
        changePage,
        toggleSort,
        addOffice,
        updateOffice,
        deleteOffice,
    };
}
