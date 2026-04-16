import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { User, Employee } from './useUserData';

export interface FormData {
    employee_id: string | number;
    name: string;
    username: string;
    usertype: string;
    office: string | number;
    password: string;
    password_confirmation: string;
}

/**
 * useUserForm: Composable for handling form operations (create, update, delete)
 * Manages form state, validation, and API operations for users
 */
export function useUserForm(employees: any) {
    // ============== State ==============

    /** Controls visibility of the Create User modal */
    const showCreateModal = ref(false);

    /** Controls visibility of the Edit User modal */
    const showEditModal = ref(false);

    /** Controls visibility of the Delete User modal */
    const showDeleteModal = ref(false);

    /** Stores the user being edited */
    const editingUser = ref<User | null>(null);

    /** Stores the user to be deleted */
    const userToDelete = ref<User | null>(null);

    /** Form data for creating/updating a user */
    const formData = ref<FormData>({
        employee_id: '',
        name: '',
        username: '',
        usertype: '',
        office: '',
        password: '',
        password_confirmation: ''
    });

    /** Stores validation errors for form fields */
    const formErrors = ref<Record<string, string>>({});

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => (page.props.auth.user as any)?.usertype || '');

    /**
     * Check if current user can manage users
     * Only Developer and Administrator can manage users
     */
    const canManageUsers = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    // ============== Form Methods ==============

    /**
     * openCreateModal: Opens the Create User modal
     */
    const openCreateModal = () => {
        formData.value = {
            employee_id: '',
            name: '',
            username: '',
            usertype: '',
            office: '',
            password: '',
            password_confirmation: ''
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    /**
     * closeCreateModal: Closes the Create User modal
     */
    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    /**
     * openEditModal: Opens the Edit User modal with the selected user data
     */
    const openEditModal = (user: User) => {
        editingUser.value = user;
        formData.value = {
            employee_id: '',
            name: user.name,
            username: user.username,
            usertype: user.usertype,
            office: user.office ? Number(user.office) : (user.fk_office_id ? Number(user.fk_office_id) : ''),
            password: '',
            password_confirmation: ''
        };

        // Auto-select employee with the same name
        const matchingEmployee = employees.value.find((emp: any) => emp.name === user.name);
        if (matchingEmployee) {
            formData.value.employee_id = matchingEmployee.id;
            formData.value.office = matchingEmployee.office_id || '';
        }

        formErrors.value = {};
        showEditModal.value = true;
    };

    /**
     * closeEditModal: Closes the Edit User modal
     */
    const closeEditModal = () => {
        showEditModal.value = false;
        editingUser.value = null;
    };

    /**
     * openDeleteModal: Opens the Delete User confirmation modal
     */
    const openDeleteModal = (user: User) => {
        userToDelete.value = user;
        showDeleteModal.value = true;
    };

    /**
     * closeDeleteModal: Closes the Delete User modal
     */
    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        userToDelete.value = null;
    };

    /**
     * currentOfficeName: Get the office name for the currently editing user
     */
    const getCurrentOfficeName = (offices: any): string => {
        if (!editingUser.value) return 'Not Assigned';
        
        const officeId = editingUser.value.office || editingUser.value.fk_office_id;
        
        if (!officeId || officeId === 0) {
            return 'Not Assigned';
        }
        
        const office = offices.find((o: any) => o.id === officeId);
        return office ? office.office_name : `Office ID: ${officeId}`;
    };

    /**
     * validateFormForCreate: Validates the form data for creating a user
     */
    const validateFormForCreate = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.employee_id.toString().trim()) {
            formErrors.value['employee_id'] = 'Employee is required';
        }
        
        if (!formData.value.username.trim()) {
            formErrors.value['username'] = 'Username is required';
        }
        
        if (!formData.value.usertype.trim()) {
            formErrors.value['usertype'] = 'Role is required';
        }
        
        if (!formData.value.office || Number(formData.value.office) === 0) {
            formErrors.value['office'] = 'The office field is required';
        }
        
        if (!formData.value.password.trim()) {
            formErrors.value['password'] = 'Password is required';
        }
        
        if (!formData.value.password_confirmation.trim()) {
            formErrors.value['password_confirmation'] = 'Password confirmation is required';
        }
        
        if (formData.value.password !== formData.value.password_confirmation) {
            formErrors.value['password_confirmation'] = 'Passwords do not match';
        }
        
        return Object.keys(formErrors.value).length === 0;
    };

    /**
     * validateFormForUpdate: Validates the form data for updating a user
     */
    const validateFormForUpdate = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.name.trim()) {
            formErrors.value['name'] = 'Name is required';
        }
        
        if (!formData.value.username.trim()) {
            formErrors.value['username'] = 'Username is required';
        }
        
        if (!formData.value.usertype.trim()) {
            formErrors.value['usertype'] = 'Role is required';
        }
        
        if (!formData.value.office || Number(formData.value.office) === 0) {
            formErrors.value['office'] = 'The office field is required';
        }
        
        return Object.keys(formErrors.value).length === 0;
    };

    // ============== API Methods ==============

    /**
     * createUser: Submits the form to create a new user
     */
    const createUser = async (): Promise<User | null> => {
        if (!validateFormForCreate()) return null;
        
        try {
            const response = await fetch('/api/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(formData.value)
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to create user');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * updateUser: Submits the form to update an existing user
     */
    const updateUser = async (id: number): Promise<User | null> => {
        if (!validateFormForUpdate()) return null;
        
        try {
            const response = await fetch(`/api/users/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    name: formData.value.name,
                    username: formData.value.username,
                    usertype: formData.value.usertype,
                    office: formData.value.office
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to update user');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * deleteUser: Submits the form to delete a user
     */
    const deleteUser = async (id: number): Promise<boolean> => {
        try {
            const response = await fetch(`/api/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to delete user');
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
        editingUser,
        userToDelete,
        formData,
        formErrors,

        // Permissions
        canManageUsers,

        // Modal Methods
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,

        // Form Methods
        validateFormForCreate,
        validateFormForUpdate,
        getCurrentOfficeName,

        // API Methods
        createUser,
        updateUser,
        deleteUser,
    };
}
