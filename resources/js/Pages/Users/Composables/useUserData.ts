import { ref, computed, onMounted } from 'vue';

/**
 * User interface defines the structure of user data
 */
export interface User {
    id: number;
    name: string;
    username: string;
    usertype: string;
    office?: number | null;
    fk_office_id?: number | null;
}

/**
 * Employee interface for dropdown selection
 */
export interface Employee {
    id: number;
    employee_id: string;
    name: string;
    office_id: number;
    designation: string;
}

/**
 * Office interface for dropdown selection
 */
export interface Office {
    id: number;
    office_name: string;
}

/**
 * useUserData: Composable for managing user data
 * Handles fetching, filtering, sorting, and pagination of users
 */
export function useUserData() {
    // ============== State ==============
    
    /** Stores the list of all users fetched from the API */
    const users = ref<User[]>([]);

    /** Stores the list of all employees for reference */
    const employees = ref<Employee[]>([]);

    /** Stores the list of all offices for reference */
    const offices = ref<Office[]>([]);

    /** Tracks the loading state of the page */
    const loading = ref(true);

    /** Stores error messages if any API call fails */
    const error = ref<string | null>(null);

    /** Stores the search query for filtering users */
    const searchQuery = ref('');

    /** Current page number for pagination */
    const currentPage = ref(1);

    /** Number of items to display per page */
    const itemsPerPage = ref(10);

    /** The field to sort users by */
    const sortBy = ref<'id' | 'name' | 'username' | 'usertype'>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'asc' | 'desc'>('asc');

    // ============== Computed Properties ==============

    /**
     * filteredUsers: Filters and sorts users based on search query and sort settings
     */
    const filteredUsers = computed(() => {
        let filtered = users.value.filter(user => {
            const query = searchQuery.value.toLowerCase();
            return (
                user.name.toLowerCase().includes(query) ||
                user.username.toLowerCase().includes(query) ||
                user.usertype.toLowerCase().includes(query)
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
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredUsers.value.length / itemsPerPage.value);
    });

    /**
     * paginatedUsers: Slices filtered users to show only the current page
     */
    const paginatedUsers = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredUsers.value.slice(start, end);
    });

    // ============== Methods ==============

    /**
     * fetchUsers: Fetches users from the API
     */
    const fetchUsers = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/users', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                },
            });
            if (!response.ok) {
                throw new Error('Failed to fetch users');
            }
            const data = await response.json();
            users.value = data.data || data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    };

    /**
     * fetchEmployees: Fetches employees from the API
     */
    const fetchEmployees = async () => {
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
        }
    };

    /**
     * fetchOffices: Fetches offices from the API
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
     */
    const changePage = (page: number) => {
        if (page >= 1 && page <= totalPages.value) {
            currentPage.value = page;
        }
    };

    /**
     * toggleSort: Toggle sorting by a specific field
     */
    const toggleSort = (field: 'id' | 'name' | 'username' | 'usertype') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    /**
     * addUser: Adds a new user to the list
     */
    const addUser = (user: User) => {
        users.value.push(user);
    };

    /**
     * updateUser: Updates a user in the list
     */
    const updateUser = (id: number, updatedUser: User) => {
        const index = users.value.findIndex(u => u.id === id);
        if (index !== -1) {
            users.value[index] = updatedUser;
        }
    };

    /**
     * deleteUser: Removes a user from the list
     */
    const deleteUser = (id: number) => {
        users.value = users.value.filter(u => u.id !== id);
    };

    /**
     * getOfficeNameById: Get the office name for a given ID
     */
    const getOfficeNameById = (officeId: number | null | undefined): string => {
        if (!officeId || officeId === 0) {
            return 'Not Assigned';
        }
        
        const office = offices.value.find(o => o.id === officeId);
        return office ? office.office_name : `Office ID: ${officeId}`;
    };

    /**
     * getEmployeeByName: Get employee by name (used for auto-completing user name)
     */
    const getEmployeeByName = (name: string): Employee | undefined => {
        return employees.value.find(emp => emp.name === name);
    };

    /**
     * getEmployeeById: Get employee by ID
     */
    const getEmployeeById = (id: number): Employee | undefined => {
        return employees.value.find(emp => emp.id === id);
    };

    // ============== Lifecycle ==============

    /**
     * Initialize: Fetch all data on component mount
     */
    onMounted(async () => {
        await Promise.all([
            fetchUsers(),
            fetchEmployees(),
            fetchOffices()
        ]);
    });

    return {
        // State
        users,
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
        filteredUsers,
        totalPages,
        paginatedUsers,

        // Methods
        fetchUsers,
        fetchEmployees,
        fetchOffices,
        changePage,
        toggleSort,
        addUser,
        updateUser,
        deleteUser,
        getOfficeNameById,
        getEmployeeByName,
        getEmployeeById,
    };
}
