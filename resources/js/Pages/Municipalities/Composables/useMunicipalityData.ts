import { ref, computed, onMounted } from 'vue';

/**
 * Municipality interface defines the structure of municipality data
 * Represents a single municipality entity with all its properties
 */
export interface Municipality {
    id: number;
    name: string;
    code: string;
    city_class: string | null;
    municipal_budget_officer: string | null;
    representative: string | null;
}

/**
 * useMunicipalityData: Composable for managing municipality data
 * Handles fetching, filtering, sorting, and pagination of municipalities
 */
export function useMunicipalityData() {
    // ============== State ==============
    
    /** Stores the list of all municipalities fetched from the API */
    const municipalities = ref<Municipality[]>([]);

    /** Tracks the loading state of the page */
    const loading = ref(true);

    /** Stores error messages if any API call fails */
    const error = ref<string | null>(null);

    /** Stores the search query for filtering municipalities */
    const searchQuery = ref('');

    /** Current page number for pagination */
    const currentPage = ref(1);

    /** Number of items to display per page */
    const itemsPerPage = ref(10);

    /** The field to sort municipalities by */
    const sortBy = ref<'id' | 'name' | 'code' | 'city_class' | 'municipal_budget_officer'>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'asc' | 'desc'>('asc');

    // ============== Computed Properties ==============

    /**
     * filteredMunicipalities: Filters and sorts municipalities based on search query and sort settings
     * - Filters municipalities by multiple fields (name, code, budget officer, representative, etc.)
     * - Applies sorting based on sortBy and sortOrder
     * Returns the filtered and sorted array of municipalities
     */
    const filteredMunicipalities = computed(() => {
        let filtered = municipalities.value.filter(municipality => 
            municipality.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            municipality.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            municipality.city_class?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            municipality.municipal_budget_officer?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            municipality.representative?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
        
        // Sort
        filtered.sort((a, b) => {
            let aVal: any = a[sortBy.value] || '';
            let bVal: any = b[sortBy.value] || '';
            
            // Handle numeric sorting for id
            if (sortBy.value === 'id') {
                aVal = Number(aVal);
                bVal = Number(bVal);
            } else {
                aVal = aVal.toString();
                bVal = bVal.toString();
            }
            
            let comparison = 0;
            if (aVal < bVal) comparison = -1;
            if (aVal > bVal) comparison = 1;
            return sortOrder.value === 'asc' ? comparison : -comparison;
        });
        
        return filtered;
    });

    /**
     * totalPages: Calculates the total number of pages needed for pagination
     * Based on the filtered municipalities count and items per page
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredMunicipalities.value.length / itemsPerPage.value);
    });

    /**
     * paginatedMunicipalities: Slices filtered municipalities to show only the current page
     * Calculates start and end indices based on currentPage and itemsPerPage
     * Returns the municipalities for the current page only
     */
    const paginatedMunicipalities = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredMunicipalities.value.slice(start, end);
    });

    // ============== Methods ==============

    /**
     * fetchMunicipalities: Fetches municipalities from the API
     * - Sets loading state to true initially
     * - Makes API request to /api/municipalities
     * - Populates municipalities.value with response data
     * - Handles errors and sets loading state to false on completion
     */
    const fetchMunicipalities = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/municipalities');
            if (!response.ok) {
                throw new Error('Failed to fetch municipalities');
            }
            municipalities.value = await response.json();
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
    const toggleSort = (field: 'id' | 'name' | 'code' | 'city_class' | 'municipal_budget_officer') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    /**
     * addMunicipality: Adds a new municipality to the list
     * @param {Municipality} municipality - The municipality to add
     */
    const addMunicipality = (municipality: Municipality) => {
        municipalities.value.push(municipality);
    };

    /**
     * updateMunicipality: Updates a municipality in the list
     * @param {number} id - The ID of the municipality to update
     * @param {Municipality} updatedMunicipality - The updated municipality data
     */
    const updateMunicipality = (id: number, updatedMunicipality: Municipality) => {
        const index = municipalities.value.findIndex(m => m.id === id);
        if (index !== -1) {
            municipalities.value[index] = updatedMunicipality;
        }
    };

    /**
     * deleteMunicipality: Removes a municipality from the list
     * @param {number} id - The ID of the municipality to delete
     */
    const deleteMunicipality = (id: number) => {
        municipalities.value = municipalities.value.filter(m => m.id !== id);
    };

    // ============== Lifecycle ==============

    /**
     * Initialize: Fetch municipalities on component mount
     */
    onMounted(async () => {
        await fetchMunicipalities();
    });

    return {
        // State
        municipalities,
        loading,
        error,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,

        // Computed
        filteredMunicipalities,
        totalPages,
        paginatedMunicipalities,

        // Methods
        fetchMunicipalities,
        changePage,
        toggleSort,
        addMunicipality,
        updateMunicipality,
        deleteMunicipality,
    };
}
