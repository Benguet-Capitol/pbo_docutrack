import { ref, computed, onMounted } from 'vue';

/**
 * Employee interface defines the structure of employee data
 * Represents a single employee entity with all its properties
 */
export interface Employee {
    id: number;
    employee_id: string;
    name: string;
    designation: string;
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

/**
 * useEmployeeData: Composable for managing employee data
 * Handles fetching, filtering, sorting, and pagination of employees
 */
export function useEmployeeData() {
    // ============== State ==============
    
    /** Stores the list of all employees fetched from the API */
    const employees = ref<Employee[]>([]);

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
    const sortBy = ref<'id' | 'employee_id' | 'name' | 'designation'>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'asc' | 'desc'>('asc');

    // ============== Computed Properties ==============

    /**
     * filteredEmployees: Filters and sorts employees based on search query and sort settings
     * - Filters employees by employee_id, name, and designation
     * - Applies sorting based on sortBy and sortOrder
     * Returns the filtered and sorted array of employees
     */
    const filteredEmployees = computed(() => {
        let filtered = employees.value.filter(employee => {
            const query = searchQuery.value.toLowerCase();
            return (
                employee.employee_id.toLowerCase().includes(query) ||
                employee.name.toLowerCase().includes(query) ||
                employee.designation.toLowerCase().includes(query)
            );
        });
        
        // Sort
        filtered.sort((a, b) => {
            let aVal: any = a[sortBy.value];
            let bVal: any = b[sortBy.value];

            if (aVal === null || aVal === undefined) aVal = '';
            if (bVal === null || bVal === undefined) bVal = '';

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
        });
        
        return filtered;
    });

    /**
     * totalPages: Calculates the total number of pages needed for pagination
     * Based on the filtered employees count and items per page
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredEmployees.value.length / itemsPerPage.value);
    });

    /**
     * paginatedEmployees: Slices filtered employees to show only the current page
     * Calculates start and end indices based on currentPage and itemsPerPage
     * Returns the employees for the current page only
     */
    const paginatedEmployees = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredEmployees.value.slice(start, end);
    });

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

    /**
     * fetchEmployees: Fetches employees from the API
     * - Sets loading state to true initially
     * - Makes API request to /api/employees
     * - Populates employees.value with response data
     * - Handles errors and sets loading state to false on completion
     */
    const fetchEmployees = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/employees', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                },
            });
            if (!response.ok) {
                throw new Error('Failed to fetch employees');
            }
            const data = await response.json();
            employees.value = data.data || data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

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
    const toggleSort = (field: 'id' | 'employee_id' | 'name' | 'designation') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    /**
     * addEmployee: Adds a new employee to the list
     * @param {Employee} employee - The employee to add
     */
    const addEmployee = (employee: Employee) => {
        employees.value.push(employee);
    };

    /**
     * updateEmployee: Updates an employee in the list
     * @param {number} id - The ID of the employee to update
     * @param {Employee} updatedEmployee - The updated employee data
     */
    const updateEmployee = (id: number, updatedEmployee: Employee) => {
        const index = employees.value.findIndex(e => e.id === id);
        if (index !== -1) {
            employees.value[index] = updatedEmployee;
        }
    };

    /**
     * deleteEmployee: Removes an employee from the list
     * @param {number} id - The ID of the employee to delete
     */
    const deleteEmployee = (id: number) => {
        employees.value = employees.value.filter(e => e.id !== id);
    };

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

        // Computed
        filteredEmployees,
        totalPages,
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
