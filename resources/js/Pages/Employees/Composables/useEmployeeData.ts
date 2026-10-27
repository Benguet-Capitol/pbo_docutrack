import { ref, computed, onMounted, watch } from 'vue';

/**
 * Employee interface defines the structure of employee data
 * Represents a single employee entity with all its properties
 */
export interface Employee {
    id: number;
    employee_id: string;
    name: string;
    /** Printed title, derived from the position (a department head's parenthetical title) */
    designation: string;
    position_title?: string | null;
    parenthetical_title?: string | null;
    status?: string | null;
    full_position?: string;
    office_id?: number | null;
    /** Another office's employee working at the PBO: counts as PBO staff in every module */
    detailed_to_pbo?: boolean;
    /** No longer in HRIS (set by the sync) */
    separated?: boolean;
    separated_at?: string | null;
    office?: number | {
        id: number;
        office_name: string;
        office_abbreviation?: string;
    } | null;
}

/**
 * Office interface for dropdown selection
 */
export interface Office {
    id: number;
    office_name: string;
}

/** Sortable columns (the server sorts) */
export type EmployeeSort = 'id' | 'employee_id' | 'name' | 'position_title' | 'parenthetical_title' | 'status' | 'designation';

/**
 * useEmployeeData: the Employees page list, one page at a time from the server (searched, sorted and paged
 * there: HRIS brings in thousands of employees), plus the offices for the forms.
 */
export function useEmployeeData() {
    // ============== State ==============

    /** The employees on the current page */
    const employees = ref<Employee[]>([]);

    /** Employees matching the search, on every page */
    const totalCount = ref(0);

    /** Pages for the search */
    const totalPages = ref(0);

    /** Stores the list of all offices for reference */
    const offices = ref<Office[]>([]);

    /** Tracks the loading state of the page */
    const loading = ref(true);

    /** Stores error messages if any API call fails */
    const error = ref<string | null>(null);

    /** Stores the search query for filtering employees */
    const searchQuery = ref('');

    /** Current page number for pagination */
    const currentPage = ref(1);

    /** Number of items to display per page */
    const itemsPerPage = ref(10);

    /** The field to sort employees by */
    const sortBy = ref<EmployeeSort>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'asc' | 'desc'>('asc');

    // ============== Computed Properties ==============

    /** The current page (the server already searched, sorted and paged) */
    const paginatedEmployees = computed(() => employees.value);

    /**
     * officeMap: Creates a map of office IDs to office names for quick lookup
     */
    const officeMap = computed(() => {
        const map = new Map();
        offices.value.forEach(office => {
            map.set(office.id, office.office_name);
        });
        return map;
    });

    // ============== Methods ==============

    /** Only the latest request's answer is shown (typing fires several) */
    let requestId = 0;

    /**
     * fetchEmployees: the current page for the search and sort. A page past the end (e.g. after a delete) moves
     * to the last page.
     */
    const fetchEmployees = async () => {
        const id = ++requestId;
        loading.value = employees.value.length === 0;
        error.value = null;
        try {
            const params = new URLSearchParams({
                page: String(currentPage.value),
                per_page: String(itemsPerPage.value),
                sort: sortBy.value,
                order: sortOrder.value,
            });
            if (searchQuery.value.trim()) params.set('search', searchQuery.value.trim());

            const response = await fetch(`/api/employees?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('Failed to fetch employees');
            }
            const data = await response.json();
            if (id !== requestId) return;

            if (data.last_page > 0 && currentPage.value > data.last_page) {
                currentPage.value = data.last_page;
                return;
            }
            employees.value = data.data;
            totalCount.value = data.total;
            totalPages.value = data.last_page;
        } catch (e) {
            if (id === requestId) error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            if (id === requestId) loading.value = false;
        }
    };

    // Searching waits for a pause in typing; the page, page size and sort reload at once
    let searchTimer: ReturnType<typeof setTimeout> | undefined;
    watch(searchQuery, () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            if (currentPage.value !== 1) currentPage.value = 1;
            else fetchEmployees();
        }, 300);
    });
    watch(itemsPerPage, () => {
        if (currentPage.value !== 1) currentPage.value = 1;
        else fetchEmployees();
    });
    watch([currentPage, sortBy, sortOrder], () => fetchEmployees());

    /**
     * fetchOffices: Fetches offices from the API
     * - Makes API request to /api/offices
     * - Populates offices.value with response data
     * - Used for office dropdown in forms
     */
    const fetchOffices = async () => {
        try {
            const response = await fetch('/api/offices', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                },
            });
            if (!response.ok) {
                throw new Error('Failed to fetch offices');
            }
            const data = await response.json();
            offices.value = data.data || data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
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
    const toggleSort = (field: EmployeeSort) => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    // After a change the current page is reloaded (where the employee lands depends on the search and sort)
    const addEmployee = (_employee?: Employee) => fetchEmployees();
    const updateEmployee = (_id?: number, _employee?: Employee) => fetchEmployees();
    const deleteEmployee = (_id?: number) => fetchEmployees();

    /**
     * getOfficeNameById: Get the office name for a given ID
     * @param {number} officeId - The office ID
     * @param {Employee} employee - Optional: The employee object for reference
     */
    const getOfficeNameById = (officeId: number | null | undefined, employee?: Employee): string => {
        // Try to use the office object/id from the employee data first (from API)
        if (employee?.office) {
            if (typeof employee.office === 'object' && employee.office.office_name) {
                return employee.office.office_name;
            }
            if (typeof employee.office === 'number') {
                const officeFromArray = offices.value.find(o => o.id === employee.office);
                if (officeFromArray) {
                    return officeFromArray.office_name;
                }
            }
        }
        
        if (!officeId || officeId === 0) {
            return 'Not Assigned';
        }
        
        const id = typeof officeId === 'string' ? parseInt(officeId) : officeId;
        const office = offices.value.find(o => o.id === id);
        if (office) {
            return office.office_name;
        }
        
        const mapName = officeMap.value.get(id);
        if (mapName) {
            return mapName;
        }
        
        return `Office ID: ${id}`;
    };

    // ============== Lifecycle ==============

    /**
     * Initialize: Fetch employees and offices on component mount
     */
    onMounted(async () => {
        await Promise.all([
            fetchEmployees(),
            fetchOffices()
        ]);
    });

    return {
        // State
        employees,
        offices,
        loading,
        error,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        totalCount,
        totalPages,

        // Computed
        paginatedEmployees,
        officeMap,

        // Methods
        fetchEmployees,
        fetchOffices,
        changePage,
        toggleSort,
        addEmployee,
        updateEmployee,
        deleteEmployee,
        getOfficeNameById,
    };
}
