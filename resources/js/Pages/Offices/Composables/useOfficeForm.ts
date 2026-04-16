import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Office } from './useOfficeData';

export interface FormData {
    office_name: string;
    office_abbreviation: string;
    sub_office: string;
    fund: string;
    fpp_code: string;
    responsibility_code: string;
    branch: string;
}

/**
 * useOfficeForm: Composable for handling form operations (create, update, delete)
 * Manages form state, validation, and API operations for offices
 */
export function useOfficeForm() {
    // ============== State ==============

    /** Controls visibility of the Create Office modal */
    const showCreateModal = ref(false);

    /** Controls visibility of the Edit Office modal */
    const showEditModal = ref(false);

    /** Controls visibility of the Delete Office modal */
    const showDeleteModal = ref(false);

    /** Stores the office being edited */
    const editingOffice = ref<Office | null>(null);

    /** Stores the office to be deleted */
    const officeToDelete = ref<Office | null>(null);

    /** Form data for creating/updating an office */
    const formData = ref<FormData>({
        office_name: '',
        office_abbreviation: '',
        sub_office: '',
        fund: '',
        fpp_code: '',
        responsibility_code: '',
        branch: ''
    });

    /** Stores validation errors for form fields */
    const formErrors = ref<Record<string, string>>({});

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create offices
     * Only Developer and Administrator can create offices
     */
    const canCreateOffices = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    /**
     * Check if current user can edit offices
     * All authenticated users can edit offices (they all have offices.edit permission)
     */
    const canEditOffices = computed(() => {
        return true;
    });

    /**
     * Check if current user can delete offices
     * Only Developer and Administrator can delete offices
     */
    const canDeleteOffices = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    // ============== Form Methods ==============

    /**
     * openCreateModal: Opens the Create Office modal
     * - Resets formData to empty values
     * - Clears any existing form errors
     * - Sets showCreateModal to true
     */
    const openCreateModal = () => {
        formData.value = {
            office_name: '',
            office_abbreviation: '',
            sub_office: '',
            fund: '',
            fpp_code: '',
            responsibility_code: '',
            branch: ''
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    /**
     * closeCreateModal: Closes the Create Office modal
     * Sets showCreateModal to false
     */
    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    /**
     * openEditModal: Opens the Edit Office modal with the selected office data
     * @param {Office} office - The office object to edit
     * Populates formData with the current office values
     * Sets editingOffice to the office being edited
     * Opens the edit modal
     */
    const openEditModal = (office: Office) => {
        editingOffice.value = office;
        formData.value = {
            office_name: office.office_name,
            office_abbreviation: office.office_abbreviation,
            sub_office: office.sub_office || '',
            fund: office.fund || '',
            fpp_code: office.fpp_code || '',
            responsibility_code: office.responsibility_code || '',
            branch: office.branch || ''
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    /**
     * closeEditModal: Closes the Edit Office modal
     * Clears the editingOffice reference
     */
    const closeEditModal = () => {
        showEditModal.value = false;
        editingOffice.value = null;
    };

    /**
     * openDeleteModal: Opens the Delete Office confirmation modal
     * @param {Office} office - The office object to delete
     * Sets officeToDelete to the office being confirmed for deletion
     * Opens the delete confirmation modal
     */
    const openDeleteModal = (office: Office) => {
        officeToDelete.value = office;
        showDeleteModal.value = true;
    };

    /**
     * closeDeleteModal: Closes the Delete Office modal
     * Clears the officeToDelete reference
     */
    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        officeToDelete.value = null;
    };

    /**
     * validateForm: Validates the form data for creating/updating an office
     * Required fields: office_name, office_abbreviation, fund, branch
     * Returns true if all validations pass, false otherwise
     * Sets formErrors with specific error messages for invalid fields
     */
    const validateForm = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.office_name.trim()) {
            formErrors.value['office_name'] = 'Office Name is required';
        }
        
        if (!formData.value.office_abbreviation.trim()) {
            formErrors.value['office_abbreviation'] = 'Abbreviation is required';
        }
        
        if (!formData.value.fund.trim()) {
            formErrors.value['fund'] = 'Fund is required';
        }
        
        if (!formData.value.branch.trim()) {
            formErrors.value['branch'] = 'Branch is required';
        }
        
        return Object.keys(formErrors.value).length === 0;
    };

    // ============== API Methods ==============

    /**
     * createOffice: Submits the form to create a new office
     * - Validates form data first
     * - Makes POST request to /api/offices with form data and Bearer token
     * - Returns the newly created office or null on error
     * - Sets formErrors['submit'] with error message if request fails
     */
    const createOffice = async (): Promise<Office | null> => {
        if (!validateForm()) return null;
        
        try {
            const response = await fetch('/api/offices', {
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
                throw new Error(errorData.message || errorData.error || 'Failed to create office');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * updateOffice: Submits the form to update an existing office
     * - Validates form data first
     * - Makes PUT request to /api/offices/{id} with updated data and Bearer token
     * - Returns the updated office or null on error
     * - Sets formErrors['submit'] with error message if request fails
     */
    const updateOffice = async (id: number): Promise<Office | null> => {
        if (!validateForm()) return null;
        
        try {
            const response = await fetch(`/api/offices/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(formData.value)
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to update office');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * deleteOffice: Submits the form to delete an office
     * - Makes DELETE request to /api/offices/{id} with Bearer token
     * - Returns true on success, false on error
     */
    const deleteOffice = async (id: number): Promise<boolean> => {
        try {
            const response = await fetch(`/api/offices/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to delete office');
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
        editingOffice,
        officeToDelete,
        formData,
        formErrors,

        // Permissions
        canCreateOffices,
        canEditOffices,
        canDeleteOffices,

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
        createOffice,
        updateOffice,
        deleteOffice,
    };
}
