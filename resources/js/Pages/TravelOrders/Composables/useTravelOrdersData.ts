import { ref, computed } from 'vue';

export interface Employee {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
    office_id?: number;
    office?: {
        id: number;
        name: string;
    };
}

export interface TravelOrder {
    id: number;
    control_no: string;
    date: string;
    going_to: string;
    from_date: string;
    to_date: string;
    inclusive_dates?: string[];
    purpose: string[];
    vehicle: string;
    plate_number?: string;
    employees: Employee[];
    driver?: string;
    supervisor_employee_id: number | null;
    supervisor?: Employee;
    approver_employee_id: number | null;
    is_acting_approver?: boolean;
    acting_approver_name?: string | null;
    acting_approver_designation?: string | null;
    created_at: string;
    updated_at: string;
}

export function useTravelOrdersData() {
    const travelOrders = ref<TravelOrder[]>([]);
    const employees = ref<Employee[]>([]);

    const searchQuery = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortBy = ref('id');
    const sortOrder = ref<'asc' | 'desc'>('desc');
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchTravelOrders = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/travel-orders');
            if (!response.ok) throw new Error('Failed to fetch travel orders');
            travelOrders.value = await response.json();
        } catch (err: any) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchEmployees = async () => {
        try {
            const response = await fetch('/api/employees');
            if (!response.ok) throw new Error('Failed to fetch employees');
            employees.value = await response.json();
        } catch (err: any) {
            console.error('Failed to load employees:', err);
        }
    };

    const formatDateForDisplay = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatInclusiveDateForSearch = (dateEntry: string): string => {
        if (!dateEntry) return '';
        
        if (dateEntry.includes(' - ')) {
            const [startStr, endStr] = dateEntry.split(' - ');
            const startDate = new Date(startStr.trim());
            const endDate = new Date(endStr.trim());
            const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            return `${start} - ${end}`;
        } else {
            const date = new Date(dateEntry.trim());
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    };

    const filteredTravelOrders = computed(() => {
        let filtered = travelOrders.value;

        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(order => {
                // Search in control_no
                if (order.control_no.toLowerCase().includes(query)) return true;
                
                // Search in date (both raw and formatted)
                if (order.date.toLowerCase().includes(query)) return true;
                if (formatDateForDisplay(order.date).toLowerCase().includes(query)) return true;
                
                // Search in going_to
                if (order.going_to.toLowerCase().includes(query)) return true;
                
                // Search in employee names
                if (order.employees.some(emp => emp.name.toLowerCase().includes(query))) return true;
                
                // Search in inclusive_dates array (both raw and formatted)
                if (order.inclusive_dates && order.inclusive_dates.length > 0) {
                    if (order.inclusive_dates.some(date => date.toLowerCase().includes(query))) return true;
                    if (order.inclusive_dates.some(date => formatInclusiveDateForSearch(date).toLowerCase().includes(query))) return true;
                }
                
                // Search in from_date and to_date (both raw and formatted) - fallback for when inclusive_dates not available
                if (order.from_date && order.from_date.toLowerCase().includes(query)) return true;
                if (order.from_date && formatDateForDisplay(order.from_date).toLowerCase().includes(query)) return true;
                if (order.to_date && order.to_date.toLowerCase().includes(query)) return true;
                if (order.to_date && formatDateForDisplay(order.to_date).toLowerCase().includes(query)) return true;
                
                // Search in purpose array
                if (order.purpose.some(p => p.toLowerCase().includes(query))) return true;
                
                // Search in vehicle
                if (order.vehicle.toLowerCase().includes(query)) return true;
                
                // Search in plate_number
                if (order.plate_number && order.plate_number.toLowerCase().includes(query)) return true;
                
                // Search in driver
                if (order.driver && order.driver.toLowerCase().includes(query)) return true;
                
                // Search in supervisor name
                if (order.supervisor && order.supervisor.name.toLowerCase().includes(query)) return true;
                
                return false;
            });
        }

        // Apply sorting
        filtered.sort((a, b) => {
            let aValue: any;
            let bValue: any;

            if (sortBy.value === 'id') {
                aValue = a.id;
                bValue = b.id;
            } else if (sortBy.value === 'date') {
                aValue = a.date;
                bValue = b.date;
            } else if (sortBy.value === 'control_no') {
                aValue = a.control_no;
                bValue = b.control_no;
            } else if (sortBy.value === 'employees') {
                aValue = a.employees.length;
                bValue = b.employees.length;
            } else {
                aValue = a.going_to;
                bValue = b.going_to;
            }

            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }

            if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
            if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
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

    const paginatedTravelOrders = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredTravelOrders.value.slice(start, end);
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredTravelOrders.value.length / itemsPerPage.value);
    });

    const paginationRange = computed(() => {
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

    const toggleSort = (field: string) => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
        currentPage.value = 1;
    };

    return {
        // State
        travelOrders,
        employees,
        searchQuery,
        currentPage,
        itemsPerPage,
        sortBy,
        sortOrder,
        loading,
        error,

        // Computed
        filteredTravelOrders,
        sortedEmployees,
        paginatedTravelOrders,
        totalPages,
        paginationRange,

        // Methods
        fetchTravelOrders,
        fetchEmployees,
        toggleSort,
    };
}