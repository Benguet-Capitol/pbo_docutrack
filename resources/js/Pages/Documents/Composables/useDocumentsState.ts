import { useResponsiveView } from '@/Composables/useResponsiveView';
import { computed, ref } from 'vue';
import { useYearFilter } from '@/Composables/useYearFilter';
import { IN_PROGRESS_STATUSES } from './documentDisplay';
import type { Document, DocumentTransaction } from './types';
import Toast from '@/Components/Toast.vue';

/** Shared state of the Documents page: the list, filters, every modal's open state and form, the checklist being edited. */
export function useDocumentsState() {
    // Table on desktop (user can switch to cards), always cards on small screens
    const { viewMode: currentViewMode, setViewMode } = useResponsiveView('documentsView');

    const viewMode = computed({ get: () => currentViewMode.value, set: setViewMode });

    /** Reactive current time for real-time updates of Processing Time and Remaining Duration */
    const currentTime = ref(new Date());

    const toastRef = ref<InstanceType<typeof Toast> | null>(null);

    /** Check if current user has a specific permission */
    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    // ============== Reactive State Management ==============

    /** Stores the list of all documents fetched from the API */
    const documents = ref<Document[]>([]);

    /** Tracks the loading state of the page */
    const loading = ref(true);

    /** Stores error messages if any API call fails */
    const error = ref<string | null>(null);

    /** Stores the search query for filtering documents */
    const searchQuery = ref('');

    /** Current page number for pagination */
    const currentPage = ref(1);

    const { selectedYear, availableYears, filterByYear } = useYearFilter(
        documents,
        (document) => document.date,
        (document) => IN_PROGRESS_STATUSES.includes(document.status),
    );

    /** Number of items to display per page */
    const itemsPerPage = ref(10);

    /** Stores the selected document type for filtering */
    const documentTypeFilter = ref('');

    /** The field to sort documents by */
    const sortBy = ref<'id' | 'tracking_no' | 'date' | 'document_type' | 'source' | 'particulars' | 'remarks' | 'user_id' | 'status'>('id');

    /** Sort direction: 'asc' for ascending, 'desc' for descending */
    const sortOrder = ref<'desc' | 'asc'>('desc');

    /** Tracks which document's dropdown menu is currently open (by document ID) */
    const activeDropdown = ref<number | null>(null);

    /** Controls visibility of the Create Document modal */
    const showCreateModal = ref(false);

    /** Controls visibility of the Edit Document modal */
    const showEditModal = ref(false);

    /** Controls visibility of the Delete Document modal */
    const showDeleteModal = ref(false);

    /** Controls visibility of the Forward Document modal */
    const showForwardModal = ref(false);

    /** Stores the document being edited */
    const editingDocument = ref<Document | null>(null);

    /** Stores the document to be deleted */
    const documentToDelete = ref<Document | null>(null);

    /** Stores the document to be forwarded */
    const documentToForward = ref<Document | null>(null);

    /** Forward form data */
    const forwardData = ref({
        forward_to_type: 'user', // 'user', 'office', or 'municipality'
        forward_to_id: null as number | null,
        remarks: '',
    });

    /** Stores forward form errors */
    const forwardErrors = ref<Record<string, string>>({});

    /** Controls visibility of the Finalize Document modal */
    const showFinalizeModal = ref(false);

    /** Stores the document to be finalized */
    const documentToFinalize = ref<Document | null>(null);

    /** Controls visibility of the Receive Document modal */
    const showReceiveModal = ref(false);

    /** Stores the document to be received */
    const documentToReceive = ref<Document | null>(null);

    /** Stores remarks for receive modal */
    const receiveModalRemarks = ref('');

    /** Stores remarks for finalize modal */
    const finalizeModalRemarks = ref('');

    /** Stores the list of offices for internal source */
    const offices = ref<Array<{id: number; office_name: string}>>([]);

    /** Stores the list of municipalities for external source */
    const municipalities = ref<Array<{id: number; name: string; city_class?: string}>>([]);

    /** Stores the list of users for document forwarding */
    const users = ref<Array<{id: number; name: string}>>([]);

    // ============== User & Permissions ==============

    /** Stores the current authenticated user information */
    const currentUser = ref<any>(null);

    /** Stores the current user's permissions */
    const permissions = ref<string[]>([]);

    /** Controls visibility of the Document Transactions modal */
    const showTransactionsModal = ref(false);

    /** Stores the document whose transactions are being viewed */
    const documentViewingTransactions = ref<Document | null>(null);

    /** Controls visibility of the Document Checklist modal */
    const showChecklistModal = ref(false);

    /** Stores the checklist template for the selected document type */
    const checklistTemplate = ref<any>({
        id: null,
        document_type: '',
        items: []
    });

    /** Stores the checklist item data (checked state, remarks, and signatories) */
    const checklistData = ref<Array<{
        checklist_item_id: number;
        item_name: string;
        is_checked: boolean;
        remarks: Record<string, string>;
        signatories: Array<{
            name: string;
            is_checked: boolean;
            acting_status?: string;
        }>;
        annexes?: Array<{
            name: string;
            is_checked: boolean;
        }>;
        group_letter?: string;
        is_subitem?: boolean;
    }>>([]);

    /** Stores checklist validation error message */
    const checklistValidationError = ref<string>('');

    /** Currently active status tab */
    const activeStatusTab = ref<string>('all');

    const documentTransactions = ref<DocumentTransaction[]>([]);

    // ============== Form Data & Validation ==============

    /** Form data for creating a new document */
    const formData = ref({
        tracking_no: '',
        date: '',
        document_type: '',
        particulars: '',
        source: '',
        sourceType: 'internal', // 'internal' or 'external'
        sb_no: '',
        status: 'pending',
        remarks: ''
    });

    /** Stores validation errors for form fields */
    const formErrors = ref<Record<string, string>>({});

    return {
        viewMode,
        currentTime,
        toastRef,
        hasPermission,
        documents,
        loading,
        error,
        searchQuery,
        currentPage,
        selectedYear,
        availableYears,
        filterByYear,
        itemsPerPage,
        documentTypeFilter,
        sortBy,
        sortOrder,
        activeDropdown,
        showCreateModal,
        showEditModal,
        showDeleteModal,
        showForwardModal,
        editingDocument,
        documentToDelete,
        documentToForward,
        forwardData,
        forwardErrors,
        showFinalizeModal,
        documentToFinalize,
        showReceiveModal,
        documentToReceive,
        receiveModalRemarks,
        finalizeModalRemarks,
        offices,
        municipalities,
        users,
        currentUser,
        permissions,
        showTransactionsModal,
        documentViewingTransactions,
        showChecklistModal,
        checklistTemplate,
        checklistData,
        checklistValidationError,
        activeStatusTab,
        documentTransactions,
        formData,
        formErrors,
    };
}

export type DocumentsState = ReturnType<typeof useDocumentsState>;
