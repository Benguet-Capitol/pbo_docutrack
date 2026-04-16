import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Municipality } from './useMunicipalityData';

export interface FormData {
    name: string;
    code: string;
    city_class: string;
    municipal_budget_officer: string;
    representative: string;
}

/**
 * useMunicipalityForm: Composable for handling form operations (create, update, delete)
 * Manages form state, validation, and API operations for municipalities
 */
export function useMunicipalityForm() {
    // ============== State ==============

    /** Controls visibility of the Create Municipality modal */
    const showCreateModal = ref(false);

    /** Controls visibility of the Edit Municipality modal */
    const showEditModal = ref(false);

    /** Controls visibility of the Delete Municipality modal */
    const showDeleteModal = ref(false);

    /** Stores the municipality being edited */
    const editingMunicipality = ref<Municipality | null>(null);

    /** Stores the municipality to be deleted */
    const municipalityToDelete = ref<Municipality | null>(null);

    /** Form data for creating/updating a municipality */
    const formData = ref<FormData>({
        name: '',
        code: '',
        city_class: '',
        municipal_budget_officer: '',
        representative: ''
    });

    /** Stores validation errors for form fields */
    const formErrors = ref<Record<string, string>>({});

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create municipalities
     * Only Developer and Administrator can create municipalities
     */
    const canCreateMunicipalities = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    /**
     * Check if current user can edit municipalities
     * All authenticated users can edit municipalities (they all have municipalities.edit permission)
     */
    const canEditMunicipalities = computed(() => {
        return true;
    });

    /**
     * Check if current user can delete municipalities
     * Only Developer and Administrator can delete municipalities
     */
    const canDeleteMunicipalities = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    // ============== Form Methods ==============

    /**
     * openCreateModal: Opens the Create Municipality modal
     * - Resets formData to empty values
     * - Clears any existing form errors
     * - Sets showCreateModal to true
     */
    const openCreateModal = () => {
        formData.value = {
            name: '',
            code: '',
            city_class: '',
            municipal_budget_officer: '',
            representative: ''
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    /**
     * closeCreateModal: Closes the Create Municipality modal
     * Sets showCreateModal to false
     */
    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    /**
     * openEditModal: Opens the Edit Municipality modal with the selected municipality data
     * @param {Municipality} municipality - The municipality object to edit
     * Populates formData with the current municipality values
     * Sets editingMunicipality to the municipality being edited
     * Opens the edit modal
     */
    const openEditModal = (municipality: Municipality) => {
        editingMunicipality.value = municipality;
        formData.value = {
            name: municipality.name,
            code: municipality.code,
            city_class: municipality.city_class || '',
            municipal_budget_officer: municipality.municipal_budget_officer || '',
            representative: municipality.representative || ''
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    /**
     * closeEditModal: Closes the Edit Municipality modal
     * Clears the editingMunicipality reference
     */
    const closeEditModal = () => {
        showEditModal.value = false;
        editingMunicipality.value = null;
    };

    /**
     * openDeleteModal: Opens the Delete Municipality confirmation modal
     * @param {Municipality} municipality - The municipality object to delete
     * Sets municipalityToDelete to the municipality being confirmed for deletion
     * Opens the delete confirmation modal
     */
    const openDeleteModal = (municipality: Municipality) => {
        municipalityToDelete.value = municipality;
        showDeleteModal.value = true;
    };

    /**
     * closeDeleteModal: Closes the Delete Municipality modal
     * Clears the municipalityToDelete reference
     */
    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        municipalityToDelete.value = null;
    };

    /**
     * validateForm: Validates the form data for creating/updating a municipality
     * Required fields: name, code
     * Returns true if all validations pass, false otherwise
     * Sets formErrors with specific error messages for invalid fields
     */
    const validateForm = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.name.trim()) {
            formErrors.value['name'] = 'Municipality Name is required';
        }
        
        if (!formData.value.code.trim()) {
            formErrors.value['code'] = 'Code is required';
        }
        
        return Object.keys(formErrors.value).length === 0;
    };

    // ============== API Methods ==============

    /**
     * createMunicipality: Submits the form to create a new municipality
     * - Validates form data first
     * - Makes POST request to /api/municipalities with form data and Bearer token
     * - Returns the newly created municipality or null on error
     * - Sets formErrors['submit'] with error message if request fails
     */
    const createMunicipality = async (): Promise<Municipality | null> => {
        if (!validateForm()) return null;
        
        try {
            const response = await fetch('/api/municipalities', {
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
                throw new Error(errorData.message || errorData.error || 'Failed to create municipality');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * updateMunicipality: Submits the form to update an existing municipality
     * - Validates form data first
     * - Makes PUT request to /api/municipalities/{id} with updated data and Bearer token
     * - Returns the updated municipality or null on error
     * - Sets formErrors['submit'] with error message if request fails
     */
    const updateMunicipality = async (id: number): Promise<Municipality | null> => {
        if (!validateForm()) return null;
        
        try {
            const response = await fetch(`/api/municipalities/${id}`, {
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
                throw new Error(errorData.message || errorData.error || 'Failed to update municipality');
            }
            
            return await response.json();
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            throw e;
        }
    };

    /**
     * deleteMunicipality: Submits the form to delete a municipality
     * - Makes DELETE request to /api/municipalities/{id} with Bearer token
     * - Returns true on success, false on error
     */
    const deleteMunicipality = async (id: number): Promise<boolean> => {
        try {
            const response = await fetch(`/api/municipalities/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to delete municipality');
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
        editingMunicipality,
        municipalityToDelete,
        formData,
        formErrors,

        // Permissions
        canCreateMunicipalities,
        canEditMunicipalities,
        canDeleteMunicipalities,

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
        createMunicipality,
        updateMunicipality,
        deleteMunicipality,
    };
}
