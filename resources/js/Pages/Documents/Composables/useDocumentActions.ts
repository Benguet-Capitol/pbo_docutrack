import { computed, watch } from 'vue';
import { useFormChanges } from '@/Composables/useFormChanges';
import { getCsrfToken } from './documentDisplay';
import type { Document } from './types';
import type { DocumentsState } from './useDocumentsState';

/** Document actions: create/edit form, delete, forward, end transaction, receive, and the transactions history. */
export function useDocumentActions(state: DocumentsState) {
    const {
        toastRef, documents, error, showCreateModal, showEditModal, showDeleteModal, showForwardModal,
        editingDocument, documentToDelete, documentToForward, forwardData, forwardErrors, showFinalizeModal,
        documentToFinalize, showReceiveModal, documentToReceive, receiveModalRemarks, finalizeModalRemarks,
        offices, municipalities, users, showTransactionsModal, documentViewingTransactions,
        documentTransactions, formData, formErrors,
    } = state;

    /** Watch for changes in sourceType and clear source when changing types (only in create mode) */
    watch(
        () => formData.value.sourceType,
        (newSourceType) => {
            // Only clear source when creating a new document, not when editing
            if (showCreateModal.value) {
                formData.value.source = '';
            }
        }
    );

    /** Get today's date in YYYY-MM-DD format */
    const todayDate = computed(() => {
        const today = new Date();
        return today.toISOString().split('T')[0];
    });

    /**
     * documentTypeHasChecklist: Check if the selected document type has a checklist
     * Returns true if document type is 'Annual Budget', 'Supplemental Budget', or 'Budget Proposals', false otherwise
     */
    const documentTypeHasChecklist = computed(() => {
        const checklistRequiredDocuments = ['Annual Budget', 'Supplemental Budget', 'Budget Proposals'];
        return checklistRequiredDocuments.includes(formData.value.document_type);
    });

    /** Get tracking number prefix (YYYY-MM-) based on selected date */
    /**
     * generateTrackingNo: Fetches auto-generated Tracking No from server based on highest existing number
     * Format: YYYY-MM-NNNN (e.g., 2026-02-0001)
     * Finds the maximum existing sequence number and increments it (handles deleted records)
     */
    const generateTrackingNo = async (): Promise<string> => {
        try {
            const response = await fetch('/api/documents/generate/tracking-no', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error('Failed to generate tracking number');
            }

            const data = await response.json();
            return data.tracking_no;
        } catch (error) {
            console.error('Error generating tracking number:', error);
            // Fallback: Generate locally if API fails, using yearly reset logic
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const yearMonth = `${year}-${month}`;
            
            // Extract numeric parts from ALL documents in current year (not just current month)
            const matchingNumbers = documents.value
                .filter(d => d.tracking_no.startsWith(`${year}-`))
                .map(d => {
                    const parts = d.tracking_no.split('-');
                    return parseInt(parts[parts.length - 1], 10);
                });
            
            const maxNumber = matchingNumbers.length > 0 ? Math.max(...matchingNumbers) : 0;
            const series = String(maxNumber + 1).padStart(4, '0');
            return `${yearMonth}-${series}`;
        }
    };

    /**
     * Determine the source type (office or municipality) of the document being forwarded
     * by checking if the document source exists in the offices or municipalities lists
     */
    const forwardDocumentSourceType = computed(() => {
        if (!documentToForward.value) return null;
        
        // For Annual Budget and Supplemental Budget, allow forwarding to any type (office/municipality/user)
        if (documentToForward.value.document_type === 'Annual Budget' || 
            documentToForward.value.document_type === 'Supplemental Budget') {
            return null;
        }
        
        const source = documentToForward.value.source;
        
        // Check if source is an office
        if (offices.value.some(office => office.office_name === source)) {
            return 'office';
        }
        
        // Check if source is a municipality
        if (municipalities.value.some(municipality => municipality.name === source)) {
            return 'municipality';
        }
        
        return null;
    });

    /**
     * Filter users list to exclude the current document holder
     * Prevents forwarding document to the user who currently has it
     */
    const availableUsersForForward = computed(() => {
        if (!documentToForward.value) return users.value;
        return users.value.filter(user => user.id !== documentToForward.value!.user_id);
    });

    /**
     * handleEditDocument: Opens the Edit Document modal with the selected document data
     * @param {Document} document - The document object to edit
     * Populates formData with the current document values
     * Sets editingDocument to the document being edited
     * Opens the edit modal
     */
    const handleEditDocument = (document: Document) => {
        editingDocument.value = document;
        // Determine sourceType based on whether source is an office, municipality, or custom
        let sourceType = 'others'; // Default to 'others' for custom sources
        if (document.source) {
            if (offices.value.some(o => o.office_name === document.source)) {
                sourceType = 'internal';
            } else if (municipalities.value.some(m => m.name === document.source)) {
                sourceType = 'external';
            }
        }
        
        formData.value = {
            tracking_no: document.tracking_no,
            date: document.date,
            document_type: document.document_type,
            particulars: document.particulars || '',
            source: document.source || '',
            sourceType: sourceType,
            sb_no: document.sb_no || '',
            status: document.status || 'pending',
            remarks: document.remarks || ''
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    /**
     * closeEditModal: Closes the Edit Document modal
     * Clears the editingDocument reference
     */
    const closeEditModal = () => {
        showEditModal.value = false;
        editingDocument.value = null;
    };

    /**
     * handleUpdateDocument: Submits the form to update an existing document
     * - Validates form data first
     * - Makes PUT request to /api/documents/{id} with updated data and Bearer token
     * - On success: updates the document in the documents array, closes the modal, and shows success toast with document details
     * - On error: sets formErrors['submit'] with the error message and shows error toast
     */
    const handleUpdateDocument = async () => {
        if (!validateForm() || !editingDocument.value) return;
        
        try {
            // Prepare data without sourceType and user_id (set server-side)
            const submitData: any = {
                tracking_no: formData.value.tracking_no,
                date: formData.value.date,
                document_type: formData.value.document_type,
                particulars: formData.value.particulars,
                source: formData.value.source,
                status: formData.value.status,
                remarks: formData.value.remarks
            };
            
            // Add sb_no only for Supplemental Budget
            if (formData.value.document_type === 'Supplemental Budget') {
                submitData.sb_no = formData.value.sb_no;
            }
            
            const response = await fetch(`/api/documents/${editingDocument.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify(submitData),
                credentials: 'include'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to update document');
            }
            
            const updatedDocument = await response.json();
            const index = documents.value.findIndex(d => d.id === editingDocument.value!.id);
            if (index !== -1) {
                documents.value[index] = updatedDocument;
            }
            closeEditModal();
            
            toastRef.value?.add(
                'info',
                'Success',
                `Document: <strong>${updatedDocument.tracking_no}</strong> has been updated successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * handleDeleteDocument: Opens the Delete Document confirmation modal
     * @param {Document} document - The document object to delete
     * Sets documentToDelete to the document being confirmed for deletion
     * Opens the delete confirmation modal
     */
    const handleDeleteDocument = (document: Document) => {
        documentToDelete.value = document;
        showDeleteModal.value = true;
    };

    /**
     * closeDeleteModal: Closes the Delete Document modal
     * Clears the documentToDelete reference
     */
    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        documentToDelete.value = null;
    };

    /**
     * confirmDeleteDocument: Confirms and executes the deletion of a document
     * - Makes DELETE request to /api/documents/{id} with CSRF token
     * - On success: removes the document from the documents array, closes the modal, and shows success toast with document details
     * - On error: shows an error toast with the error message
     */
    const confirmDeleteDocument = async () => {
        if (!documentToDelete.value) return;
        
        const deletingDocument = documentToDelete.value;
        
        try {
            const response = await fetch(`/api/documents/${deletingDocument.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                credentials: 'include'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to delete document');
            }
            
            documents.value = documents.value.filter(d => d.id !== deletingDocument.id);
            closeDeleteModal();
            
            toastRef.value?.add(
                'error',
                'Success',
                `Document: <strong>${deletingDocument.tracking_no}</strong> has been deleted successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * handleForwardDocument: Opens the Forward Document modal
     * @param {Document} document - The document to forward
     * Default button selection is User, but source office/municipality can be auto-selected when switching buttons
     */
    const handleForwardDocument = (document: Document) => {
        documentToForward.value = document;
        forwardData.value = {
            forward_to_type: 'user',
            forward_to_id: null,
            remarks: '',
        };
        forwardErrors.value = {};
        showForwardModal.value = true;
    };

    /**
     * selectForwardOffice: Selects office type and auto-selects document source office if available
     */
    const selectForwardOffice = () => {
        forwardData.value.forward_to_type = 'office';
        
        // Auto-select the document source if it's an office
        if (documentToForward.value?.source) {
            const officeMatch = offices.value.find(office => office.office_name === documentToForward.value!.source);
            if (officeMatch) {
                forwardData.value.forward_to_id = officeMatch.id;
            } else {
                forwardData.value.forward_to_id = null;
            }
        } else {
            forwardData.value.forward_to_id = null;
        }
    };

    /**
     * selectForwardMunicipality: Selects municipality type and auto-selects document source municipality if available
     */
    const selectForwardMunicipality = () => {
        forwardData.value.forward_to_type = 'municipality';
        
        // Auto-select the document source if it's a municipality
        if (documentToForward.value?.source) {
            const municipalityMatch = municipalities.value.find(municipality => municipality.name === documentToForward.value!.source);
            if (municipalityMatch) {
                forwardData.value.forward_to_id = municipalityMatch.id;
            } else {
                forwardData.value.forward_to_id = null;
            }
        } else {
            forwardData.value.forward_to_id = null;
        }
    };

    /**
     * closeForwardModal: Closes the Forward Document modal
     */
    const closeForwardModal = () => {
        showForwardModal.value = false;
        documentToForward.value = null;
        forwardData.value = {
            forward_to_type: 'user',
            forward_to_id: null,
            remarks: '',
        };
        forwardErrors.value = {};
    };

    /**
     * handleSubmitForward: Submits the forward form to forward a document to another user, office, or municipality
     */
    const handleSubmitForward = async () => {
        if (!documentToForward.value || !forwardData.value.forward_to_id) {
            forwardErrors.value['forward_to_id'] = 'Please select a recipient';
            return;
        }

        try {
            const response = await fetch(`/api/documents/${documentToForward.value.id}/forward`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({
                    forward_to_type: forwardData.value.forward_to_type,
                    forward_to_id: forwardData.value.forward_to_id,
                    remarks: forwardData.value.remarks || null,
                }),
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to forward document');
            }

            const updatedDocument = await response.json();
            const index = documents.value.findIndex(d => d.id === documentToForward.value!.id);
            if (index !== -1) {
                documents.value[index] = updatedDocument;
            }

            closeForwardModal();

            toastRef.value?.add(
                'success',
                'Success',
                `Document: <strong>${updatedDocument.tracking_no}</strong> has been forwarded successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            forwardErrors.value['submit'] = errorMsg;

            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * handleFinalizeDocument: Opens the Finalize Document modal
     * @param {Document} document - The document to finalize
     */
    const handleFinalizeDocument = (document: Document) => {
        if (document.status !== 'pending') {
            toastRef.value?.add('error', 'Error', 'Only pending documents can be ended', 4000);
            return;
        }

        documentToFinalize.value = document;
        showFinalizeModal.value = true;
    };

    /**
     * closeFinalizeModal: Closes the Finalize Document modal
     */
    const closeFinalizeModal = () => {
        showFinalizeModal.value = false;
        documentToFinalize.value = null;
        finalizeModalRemarks.value = '';
    };

    /**
     * confirmFinalizeDocument: Confirms and executes the finalization of a document
     */
    const confirmFinalizeDocument = async () => {
        if (!documentToFinalize.value) return;

        const finalizingDocument = documentToFinalize.value;

        try {
            const response = await fetch(`/api/documents/${finalizingDocument.id}/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({
                    remarks: finalizeModalRemarks.value || null
                }),
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to end document');
            }

            const updatedDocument = await response.json();
            const index = documents.value.findIndex(d => d.id === finalizingDocument.id);
            if (index !== -1) {
                documents.value[index] = updatedDocument;
            }

            closeFinalizeModal();

            toastRef.value?.add(
                'success',
                'Success',
                `Document: <strong>${updatedDocument.tracking_no}</strong> has been ended successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';

            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * handleReceiveDocument: Opens the Receive Document modal
     * @param {Document} document - The document to receive
     */
    const handleReceiveDocument = (document: Document) => {
        documentToReceive.value = document;
        showReceiveModal.value = true;
    };

    /**
     * closeReceiveModal: Closes the Receive Document modal
     */
    const closeReceiveModal = () => {
        showReceiveModal.value = false;
        documentToReceive.value = null;
        receiveModalRemarks.value = '';
    };

    /**
     * confirmReceiveDocument: Confirms and executes the receipt of a document
     */
    const confirmReceiveDocument = async () => {
        if (!documentToReceive.value) return;

        const receivingDocument = documentToReceive.value;

        try {
            const response = await fetch(`/api/documents/${receivingDocument.id}/receive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({
                    remarks: receiveModalRemarks.value || null
                }),
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to receive document');
            }

            const updatedDocument = await response.json();
            const index = documents.value.findIndex(d => d.id === receivingDocument.id);
            if (index !== -1) {
                documents.value[index] = updatedDocument;
            }

            closeReceiveModal();

            toastRef.value?.add(
                'success',
                'Success',
                `Document: <strong>${updatedDocument.tracking_no}</strong> has been received successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';

            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * openCreateModal: Opens the Create Document modal
     * - Resets formData to empty values (except date which is set to today)
     * - Clears any existing form errors
     * - Sets showCreateModal to true
     */
    const openCreateModal = async () => {
        formData.value = {
            tracking_no: await generateTrackingNo(),
            date: todayDate.value,
            document_type: '',
            particulars: '',
            source: '',
            sourceType: 'internal',
            sb_no: '',
            status: 'pending',
            remarks: ''
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    /**
     * closeCreateModal: Closes the Create Document modal
     * Sets showCreateModal to false
     */
    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    /**
     * validateForm: Validates the form data for creating a document
     * Required fields: tracking_no, date, document_type
     * Returns true if all validations pass, false otherwise
     * Sets formErrors with specific error messages for invalid fields
     */
    const validateForm = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.tracking_no.trim()) {
            formErrors.value['tracking_no'] = 'Tracking No is required';
        }
        
        if (!formData.value.date.trim()) {
            formErrors.value['date'] = 'Date is required';
        }
        
        if (!formData.value.document_type.trim()) {
            formErrors.value['document_type'] = 'Document Type is required';
        }
        
        if (!formData.value.source.trim()) {
            formErrors.value['source'] = 'Source is required';
        }
        
        if (!formData.value.particulars.trim()) {
            formErrors.value['particulars'] = 'Particulars is required';
        }

        return Object.keys(formErrors.value).length === 0;
    };

    /** Required fields still empty (same rules as validateForm): Save/Update stays disabled until filled */
    // Edit: Update stays disabled until something is changed
    const { hasChanges: documentHasChanges } = useFormChanges(() => formData.value, () => showEditModal.value);

    const missingDocumentFields = computed(() => {
        const missing: string[] = [];
        if (!String(formData.value.tracking_no ?? '').trim()) missing.push('Tracking No');
        if (!String(formData.value.date ?? '').trim()) missing.push('Date');
        if (!String(formData.value.document_type ?? '').trim()) missing.push('Document Type');
        if (!String(formData.value.source ?? '').trim()) missing.push('Source');
        if (!String(formData.value.particulars ?? '').trim()) missing.push('Particulars');
        return missing;
    });

    /**
     * viewDocumentTransactions: Opens the transactions view modal for a document
     * - Fetches all transactions for the selected document
     * - Displays them in a modal with user info and timestamp
     */
    const viewDocumentTransactions = async (document: Document) => {
        documentViewingTransactions.value = document;
        showTransactionsModal.value = true;
        
        try {
            const response = await fetch(`/api/documents/${document.id}/transactions`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                documentTransactions.value = await response.json();
            }
        } catch (e) {
            console.error('Failed to fetch transactions:', e);
        }
    };

    /**
     * closeTransactionsModal: Closes the Document Transactions modal
     */
    const closeTransactionsModal = () => {
        showTransactionsModal.value = false;
        documentViewingTransactions.value = null;
        documentTransactions.value = [];
    };

    return {
        todayDate,
        documentTypeHasChecklist,
        generateTrackingNo,
        forwardDocumentSourceType,
        availableUsersForForward,
        handleEditDocument,
        closeEditModal,
        handleUpdateDocument,
        handleDeleteDocument,
        closeDeleteModal,
        confirmDeleteDocument,
        handleForwardDocument,
        selectForwardOffice,
        selectForwardMunicipality,
        closeForwardModal,
        handleSubmitForward,
        handleFinalizeDocument,
        closeFinalizeModal,
        confirmFinalizeDocument,
        handleReceiveDocument,
        closeReceiveModal,
        confirmReceiveDocument,
        openCreateModal,
        closeCreateModal,
        validateForm,
        documentHasChanges,
        missingDocumentFields,
        viewDocumentTransactions,
        closeTransactionsModal,
    };
}

export type DocumentActions = ReturnType<typeof useDocumentActions>;
