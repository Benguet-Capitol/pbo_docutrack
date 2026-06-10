import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Employee } from './useEmployeeData';

export interface FormData {
    employee_id: string;
    name: string;
    designation: string;
    office: number | '';
}

/**
 * useEmployeeForm: Composable for handling form operations (create, update, delete)
 * Manages form state, validation, and API operations for employees
 */
export function useEmployeeForm() {
    // ============== State ==============

    /** Controls visibility of the Create Employee modal */
    const showCreateModal = ref(false);

    /** Controls visibility of the Edit Employee modal */
    const showEditModal = ref(false);

    /** Controls visibility of the Delete Employee modal */
    const showDeleteModal = ref(false);

    /** Stores the employee being edited */
    const editingEmployee = ref<Employee | null>(null);

    /** Stores the employee to be deleted */
    const employeeToDelete = ref<Employee | null>(null);

    /** Form data for creating/updating an employee */
    const formData = ref<FormData>({
        employee_id: '',
        name: '',
        designation: '',
        office: ''
    });

    /** Stores validation errors for form fields */
    const formErrors = ref<Record<string, string>>({});

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => (page.props.auth.user as any)?.usertype || '');

    /**
     * Check if current user can create/edit employees
     * Developer, Administrator, and Administrative can create/edit employees
     */
    const canCreateEditEmployees = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    /**
     * Check if current user can delete employees
     * Developer, Administrator, and Administrative can delete employees
     */
    const canDeleteEmployees = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    // ============== Form Methods ==============

    /**
     * openCreateModal: Opens the Create Employee modal
     * - Resets formData to empty values
     * - Clears any existing form errors
     * - Sets showCreateModal to true
     */
    const openCreateModal = () => {
        formData.value = {
            employee_id: '',
            name: '',
            designation: '',
            office: ''
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    /**
     * closeCreateModal: Closes the Create Employee modal
     */
    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    /**
     * openEditModal: Opens the Edit Employee modal with the selected employee data
     * @param {Employee} employee - The employee object to edit
     */
    const openEditModal = (employee: Employee) => {
        editingEmployee.value = employee;

        // Extract office ID, handling both object and number formats
        let officeId: number | '' = '';
        if (employee.office) {
            if (typeof employee.office === 'number') {
                officeId = employee.office;
            } else if (typeof employee.office === 'object' && 'id' in employee.office) {
                officeId = employee.office.id;
            }
        }

        formData.value = {
            employee_id: employee.employee_id,
            name: employee.name,
            designation: employee.designation,
            office: officeId
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    /**
     * closeEditModal: Closes the Edit Employee modal
     */
    const closeEditModal = () => {
        showEditModal.value = false;
        editingEmployee.value = null;
    };

    /**
     * openDeleteModal: Opens the Delete Employee confirmation modal
     * @param {Employee} employee - The employee object to delete
     */
    const openDeleteModal = (employee: Employee) => {
        employeeToDelete.value = employee;
        showDeleteModal.value = true;
    };

    /**
     * closeDeleteModal: Closes the Delete Employee modal
     */
    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        employeeToDelete.value = null;
    };

    /**
     * validateForm: Validates the form data for creating/updating an employee
     * - employee_id must be set (meaning an employee was selected from the dropdown)
     * - name and designation must be non-empty (auto-filled on selection)
     * Returns true if all validations pass, false otherwise
     */
    const validateForm = (): boolean => {
        formErrors.value = {};

        if (!formData.value.employee_id.trim()) {
            formErrors.value['employee_id'] = 'Please select a valid Employee ID from the search results.';
        }

        if (!formData.value.name.trim()) {
            formErrors.value['name'] = 'Name is required.';
        }

        if (!formData.value.designation.trim()) {
            formErrors.value['designation'] = 'Designation is required.';
        }

        return Object.keys(formErrors.value).length === 0;
    };

    // ============== API Methods ==============

    /**
     * createEmployee: Submits the form to create a new employee
     * - Validates form data first
     * - Makes POST request to /api/employees with form data and CSRF token
     * - Returns the newly created employee or null on validation failure
     * - Throws on server errors so the caller can handle them
     */
    const createEmployee = async (): Promise<Employee | null> => {
        if (!validateForm()) return null;

        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

            const response = await fetch('/api/employees', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(formData.value)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));

                // Handle Laravel validation errors (422)
                if (response.status === 422 && errorData.errors) {
                    Object.entries(errorData.errors).forEach(([field, messages]) => {
                        formErrors.value[field] = Array.isArray(messages) ? messages[0] as string : messages as string;
                    });
                    return null;
                }

                throw new Error(errorData.message || errorData.error || 'Failed to create employee');
            }

            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * updateEmployee: Submits the form to update an existing employee
     * - Validates form data first
     * - Makes PUT request to /api/employees/{id} with updated data and CSRF token
     * - Returns the updated employee or null on validation failure
     */
    const updateEmployee = async (id: number): Promise<Employee | null> => {
        if (!validateForm()) return null;

        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

            const response = await fetch(`/api/employees/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(formData.value)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));

                // Handle Laravel validation errors (422)
                if (response.status === 422 && errorData.errors) {
                    Object.entries(errorData.errors).forEach(([field, messages]) => {
                        formErrors.value[field] = Array.isArray(messages) ? messages[0] as string : messages as string;
                    });
                    return null;
                }

                throw new Error(errorData.message || errorData.error || 'Failed to update employee');
            }

            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * deleteEmployee: Submits the form to delete an employee
     * - Makes DELETE request to /api/employees/{id}
     * - Returns true on success
     */
    const deleteEmployee = async (id: number): Promise<boolean> => {
        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

            const response = await fetch(`/api/employees/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to delete employee');
            }

            return true;
        } catch (e) {
            throw e;
        }
    };

    return {
        // State
        showCreateModal,
        showEditModal,
        showDeleteModal,
        editingEmployee,
        employeeToDelete,
        formData,
        formErrors,

        // Permissions
        canCreateEditEmployees,
        canDeleteEmployees,

        // Modal Methods
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,

        // Form Methods
        validateForm,

        // API Methods
        createEmployee,
        updateEmployee,
        deleteEmployee,
    };
}