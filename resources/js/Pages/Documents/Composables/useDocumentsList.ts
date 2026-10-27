import { computed, onMounted, onUnmounted, watch } from 'vue';
import { getProcessingTimeLimit, calculateRemainingBusinessTime, calculateElapsedTimeExcluding, STATUS_BADGES } from './documentDisplay';
import type { Document } from './types';
import type { DocumentsState } from './useDocumentsState';

/** The list: loading, year/type/status/search filters, status tabs, sorting, paging, and each document's status and remaining duration. */
export function useDocumentsList(state: DocumentsState) {
    const {
        currentTime, hasPermission, documents, loading, error, searchQuery, currentPage, selectedYear,
        filterByYear, itemsPerPage, documentTypeFilter, sortBy, sortOrder, activeDropdown, offices,
        municipalities, users, currentUser, permissions, showTransactionsModal, documentViewingTransactions,
        activeStatusTab,
    } = state;

    /** Stores the interval ID for cleanup on component unmount */
    let timeUpdateInterval: ReturnType<typeof setInterval> | null = null;

    /** Documents filtered by year + search + type only (for tab counts, independent of status) */
    const documentsForTabCounts = computed(() => {
        let filtered = filterByYear(documents.value);
        if (documentTypeFilter.value) {
            filtered = filtered.filter(document => document.document_type === documentTypeFilter.value);
        }
        return filtered.filter(document =>
            document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    });

    /** Status tab list with live counts, using the same per-user status as the table's Status column */
    const statusTabs = computed(() => {
        const counts: Record<string, number> = { created: 0, forwarded: 0, 'to be received': 0, pending: 0, finalized: 0 };
        documentsForTabCounts.value.forEach(document => {
            const status = getDisplayStatus(document);
            if (counts[status] !== undefined) counts[status]++;
        });
        return [
            { key: 'all', label: 'All', count: documentsForTabCounts.value.length },
            { key: 'created', label: 'Created', count: counts['created'] },
            { key: 'forwarded', label: 'Forwarded', count: counts['forwarded'] },
            { key: 'to be received', label: 'To Be Received', count: counts['to be received'] },
            { key: 'pending', label: 'Pending', count: counts['pending'] },
            { key: 'finalized', label: 'Transaction Ended', count: counts['finalized'] },
        ];
    });

    watch(activeStatusTab, () => {
        currentPage.value = 1;
    });

    /**
     * Calculate time left for processing and determine color
     */
    const getTimeLeftInfo = (document: Document): { daysLeft: number; hoursLeft: number; isLapsed: boolean; percentage: number } => {
        if (!document.transactions || document.transactions.length === 0) {
            return { daysLeft: 0, hoursLeft: 0, isLapsed: false, percentage: 0 };
        }
        
        // Get limit based on document type (in business days)
        const limit = getProcessingTimeLimit(document.document_type);
        
        // Get the creation timestamp from the first transaction (has the actual time)
        // Find the creation transaction (typically the oldest/last in the array)
        const creationTransaction = document.transactions[document.transactions.length - 1];
        const createdDate = new Date(creationTransaction.created_at);
        
        // If document is ended (finalized), freeze the time at the moment of ending
        let now = currentTime.value; // Use currentTime for real-time updates
        if (document.status === 'ended' || document.status === 'finalized') {
            // Use the last transaction date as the reference point (when it was ended)
            const lastTransaction = document.transactions[0];
            now = new Date(lastTransaction.created_at);
        }
        
        // Calculate remaining business days in hours
        const totalHoursLimit = limit * 24; // Convert business days to hours
        
        // Calculate business hours elapsed (only counting weekdays), excluding office/municipality forwarding time
        const hoursElapsed = calculateElapsedTimeExcluding(document, createdDate, now);
        
        // Calculate total hours left
        const totalHoursLeft = Math.max(0, totalHoursLimit - hoursElapsed);
        
        // Calculate remaining business days and hours (excluding weekends)
        const { days: daysLeft, hours: hoursLeft } = calculateRemainingBusinessTime(totalHoursLeft);
        
        const isLapsed = hoursElapsed > totalHoursLimit;
        const percentage = (hoursElapsed / totalHoursLimit) * 100;
        
        return { daysLeft, hoursLeft, isLapsed, percentage };
    };

    /**
     * Get the text to display for time left
     */
    const getTimeLeftText = (document: Document): string => {
        const { daysLeft, hoursLeft, isLapsed } = getTimeLeftInfo(document);
        
        if (isLapsed) {
            return `Overdue`;
        }
        
        if (daysLeft === 0 && hoursLeft === 0) {
            return `No time left`;
        }
        
        if (daysLeft === 0) {
            return `${hoursLeft} hrs`;
        }
        
        if (hoursLeft === 0) {
            return `${daysLeft} days`;
        }
        
        return `${daysLeft} days ${hoursLeft} hrs`;
    };

    /**
     * Get the style classes for time left cell based on remaining time
     */
    const getTimeLeftStyles = (document: Document): object => {
        const { daysLeft, isLapsed, percentage } = getTimeLeftInfo(document);
        
        let bgColor = '';
        let textColor = '';
        let borderColor = '';
        
        if (isLapsed) {
            // Red: lapsed
            bgColor = 'bg-red-100 dark:bg-red-900/30';
            textColor = 'text-red-800 dark:text-red-300';
            borderColor = 'border-red-200 dark:border-red-800';
        } else if (percentage >= 75) {
            // Red: very urgent (75%+ of time used)
            bgColor = 'bg-red-100 dark:bg-red-900/30';
            textColor = 'text-red-800 dark:text-red-300';
            borderColor = 'border-red-200 dark:border-red-800';
        } else if (percentage >= 50) {
            // Orange: urgent (50-75% of time used)
            bgColor = 'bg-orange-100 dark:bg-orange-900/30';
            textColor = 'text-orange-800 dark:text-orange-300';
            borderColor = 'border-orange-200 dark:border-orange-800';
        } else if (percentage >= 25) {
            // Yellow: moderate (25-50% of time used)
            bgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
            textColor = 'text-yellow-800 dark:text-yellow-300';
            borderColor = 'border-yellow-200 dark:border-yellow-800';
        } else {
            // Green: plenty of time (< 25% of time used)
            bgColor = 'bg-green-100 dark:bg-green-900/30';
            textColor = 'text-green-800 dark:text-green-300';
            borderColor = 'border-green-200 dark:border-green-800';
        }
        
        return {
            'inline-flex': true,
            'items-center': true,
            'gap-1': true,
            'px-2': true,
            'py-1': true,
            'rounded-full': true,
            'text-xs': true,
            'font-medium': true,
            'border': true,
            [bgColor]: true,
            [textColor]: true,
            [borderColor]: true,
        };
    };

    /**
     * getDisplayStatus: Get the display status based on current user's perspective
     * For forwarded documents:
     * - If forwarded to current user: "to be received"
     * - If forwarded by current user: "forwarded"
     * Otherwise: return the actual status
     */
    const getDisplayStatus = (document: Document): string => {
        if (document.status !== 'forwarded') {
            return document.status;
        }

        // Check if document is forwarded to current user
        const latestTransaction = document.transactions && document.transactions.length > 0
            ? document.transactions[0]
            : null;
        
        if (latestTransaction && latestTransaction.forwarded_to_user_id === currentUser.value?.id) {
            return 'to be received';
        }

        // It's forwarded by current user
        return 'forwarded';
    };

    /** Opaque rows (the pinned columns sit over the others while scrolling): shaded every other row, highlighted while its transactions are open */
    const documentRowClass = (document: Document, index: number): string => {
        if (showTransactionsModal.value && documentViewingTransactions.value?.id === document.id) return 'bg-blue-100 dark:bg-[#1f305a]';
        return (index % 2 === 1 ? 'bg-gray-50 dark:bg-[#192231]' : 'bg-white dark:bg-gray-800') + ' hover:bg-blue-50 dark:hover:bg-gray-700';
    };

    const statusBadge = (document: Document) => {
        const status = getDisplayStatus(document);
        return STATUS_BADGES[status] ?? { label: status, classes: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300', accent: 'border-t-gray-400' };
    };

    // ============== Computed Properties ==============

    /**
     * filteredDocuments: Filters and sorts documents based on search query and sort settings
     * - Filters documents by multiple fields (tracking_no, date, document_type, source, particulars, etc.)
     * - Applies sorting based on sortBy and sortOrder
     * - Filters based on user viewing permissions
     * Returns the filtered and sorted array of documents
     */
    const filteredDocuments = computed(() => {
        // All roles can view all document records — no per-role visibility gating.
        let filtered = filterByYear(documents.value);

        // Apply document type filter if selected
        if (documentTypeFilter.value) {
            filtered = filtered.filter(document => document.document_type === documentTypeFilter.value);
        }
        // Apply status tab filter
        if (activeStatusTab.value !== 'all') {
            filtered = filtered.filter(document => getDisplayStatus(document) === activeStatusTab.value);
        }

        // Apply search filter
        filtered = filtered.filter(document => 
            document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
        
        // Sort
        filtered.sort((a, b) => {
            let aVal: any;
            let bVal: any;
            
            if (sortBy.value === 'user_id') {
                aVal = a.user_id || 0;
                bVal = b.user_id || 0;
                aVal = Number(aVal);
                bVal = Number(bVal);
            } else if (sortBy.value === 'id') {
                aVal = Number(a[sortBy.value]) || 0;
                bVal = Number(b[sortBy.value]) || 0;
            } else {
                aVal = a[sortBy.value] || '';
                bVal = b[sortBy.value] || '';
                aVal = aVal.toString();
                bVal = bVal.toString();
            }
            
            let comparison = 0;
            if (aVal < bVal) comparison = -1;
            if (aVal > bVal) comparison = 1;
            return sortOrder.value === 'asc' ? comparison : -comparison;
        });
        
        return filtered;
    });

    /**
     * totalPages: Calculates the total number of pages needed for pagination
     * Based on the filtered documents count and items per page
     */
    const totalPages = computed(() => {
        return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
    });

    watch(documentTypeFilter, () => {
        currentPage.value = 1; // Reset to first page when document type filter changes
    });

    watch(searchQuery, () => {
        currentPage.value = 1; // Reset to first page when search query changes
    });

    watch(selectedYear, () => {
        currentPage.value = 1; // Reset to first page when the year changes
    });

    /**
     * Smart pagination: Shows limited page buttons with ellipsis
     * Format: [1, 2, ..., currentPage-1, currentPage, currentPage+1, ..., last-1, last]
     */
    const paginationPages = computed(() => {
        const pages: (number | string)[] = [];
        const total = totalPages.value;
        const current = currentPage.value;
        const maxButtons = 5; // Maximum page buttons to show without ellipsis
        const sidePages = 1; // Pages to show on each side of current page
        const edgePages = 1; // Pages to show at start and end
        
        if (total <= maxButtons + 2) {
            // If total pages fit, show all
            for (let i = 1; i <= total; i++) {
                pages.push(i);
            }
        } else {
            // Always show first page(s)
            for (let i = 1; i <= Math.min(edgePages + 1, total); i++) {
                pages.push(i);
            }
            
            // Calculate range around current page
            const rangeStart = Math.max(edgePages + 2, current - sidePages);
            const rangeEnd = Math.min(total - edgePages - 1, current + sidePages);
            
            // Add ellipsis if needed
            if (rangeStart > edgePages + 2) {
                if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }
            
            // Add pages around current
            for (let i = rangeStart; i <= rangeEnd; i++) {
                if (i > edgePages + 1 && i < total - edgePages) {
                    pages.push(i);
                }
            }
            
            // Add ellipsis if needed before last pages
            if (rangeEnd < total - edgePages - 1) {
                if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }
            
            // Always show last page(s)
            for (let i = Math.max(edgePages + 2, total - edgePages); i <= total; i++) {
                if (!pages.includes(i)) {
                    pages.push(i);
                }
            }
        }
        
        return pages;
    });

    /**
     * paginatedDocuments: Slices filtered documents to show only the current page
     * Calculates start and end indices based on currentPage and itemsPerPage
     * Returns the documents for the current page only
     */
    const paginatedDocuments = computed(() => {
        const start = (currentPage.value - 1) * itemsPerPage.value;
        const end = start + itemsPerPage.value;
        return filteredDocuments.value.slice(start, end);
    });

    /**
     * Check if current user can receive a specific document
     * Mirrors the backend canReceiveSpecificDocument logic exactly
     */
    const canReceiveDocument = (document: Document): boolean => {
        if (document.status !== 'forwarded') return false;
        if (!hasPermission('documents.receive')) return false;
        if (!document.transactions || document.transactions.length === 0) return false;

        const latestTransaction = document.transactions[0];

        // Forwarded directly to this user
        if (latestTransaction.forwarded_to_user_id === currentUser.value?.id) {
            return true;
        }

        // Forwarded to office/municipality — allowed roles or original forwarder can still receive
        if (latestTransaction.forwarded_to_office_id || latestTransaction.forwarded_to_municipality_id) {
            const isAllowedRole = hasPermission('documents.receive_office');
            const isForwarder = latestTransaction.user_id === currentUser.value?.id;
            return isAllowedRole || isForwarder;
        }

        return false;
    };

    const getCustodianDisplay = (document: Document): string => {
        // For finalized/ended documents, display '-'
        if (document.status === 'finalized') {
            return '-';
        }
        
        // If no transactions, show document creator
        if (!document.transactions || document.transactions.length === 0) {
            return document.user?.name || 'Unknown';
        }

        const latestTransaction = document.transactions[0];
        
        // Check for office forwarding (by ID field)
        if (latestTransaction.forwarded_to_office_id) {
            // Fall back to the embedded relation for offices that are in the Trash
            const office = offices.value.find(o => o.id === latestTransaction.forwarded_to_office_id)
                || latestTransaction.forwarded_to_office;
            if (office) {
                return office.office_name;
            }
        }

        // Check for municipality forwarding (by ID field)
        if (latestTransaction.forwarded_to_municipality_id) {
            const municipality = municipalities.value.find(m => m.id === latestTransaction.forwarded_to_municipality_id)
                || latestTransaction.forwarded_to_municipality;
            if (municipality) {
                return municipality.name;
            }
        }
        
        // Check for user-to-user forwarding
        if (latestTransaction.forwarded_to_user_id) {
            // Check if the forwarded-to user has received the document
            const hasReceived = document.transactions.some(t => 
                t.forwarded_to_user_id === latestTransaction.forwarded_to_user_id &&
                t.action.toLowerCase().includes('received')
            );
            
            if (hasReceived) {
                // User has received it, show that user as custodian
                const forwardedUser = users.value.find(u => u.id === latestTransaction.forwarded_to_user_id)
                    || latestTransaction.forwarded_to_user;
                if (forwardedUser) {
                    return forwardedUser.name;
                }
            } else {
                // User hasn't received it yet, show the forwarder as custodian
                return latestTransaction.user?.name || document.user?.name || 'Unknown';
            }
        }
        
        // Fallback to the user who performed the transaction
        return latestTransaction.user?.name || document.user?.name || 'Unknown';
    };

    // ============== Lifecycle Hooks ==============

    /**
     * onMounted: Fetches documents from the API when component is mounted
     * - Sets loading state to true initially
     * - Makes API request to /api/documents
     * -    
            // Fetch offices and municipalities
            const officesResponse = await fetch('/api/offices');
            if (officesResponse.ok) {
                offices.value = await officesResponse.json();
            }
            
            const municipalitiesResponse = await fetch('/api/municipalities');
            if (municipalitiesResponse.ok) {
                municipalities.value = await municipalitiesResponse.json();
            }
         Populates documents.value with response data
     * - Handles errors and sets loading state to false on completion
     */
    onMounted(async () => {
        // Set up real-time updates for Processing Time and Remaining Duration
        timeUpdateInterval = setInterval(() => {
            currentTime.value = new Date();
        }, 1000); // Update every second
        
        try {
            // Fetch current user with permissions
            const currentUserResponse = await fetch('/api/user/current');
            if (currentUserResponse.ok) {
                const userData = await currentUserResponse.json();
                currentUser.value = userData;
                permissions.value = userData.permissions || [];
            }

            const response = await fetch('/api/documents');
            if (!response.ok) {
                throw new Error('Failed to fetch documents');
            }
            documents.value = await response.json();
            
            // Fetch offices and municipalities
            const officesResponse = await fetch('/api/offices');
            if (officesResponse.ok) {
                offices.value = await officesResponse.json();
            }
            
            const municipalitiesResponse = await fetch('/api/municipalities');
            if (municipalitiesResponse.ok) {
                municipalities.value = await municipalitiesResponse.json();
            }

            // Fetch users for forwarding
            const usersResponse = await fetch('/api/users');
            if (usersResponse.ok) {
                users.value = await usersResponse.json();
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'An error occurred';
        } finally {
            loading.value = false;
        }
    });

    /**
     * onUnmounted: Clean up the time update interval when component is destroyed
     */
    onUnmounted(() => {
        if (timeUpdateInterval) {
            clearInterval(timeUpdateInterval);
            timeUpdateInterval = null;
        }
    });

    // ============== Utility Functions ==============

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
    const toggleSort = (field: 'id' | 'tracking_no' | 'date' | 'document_type' | 'source' | 'particulars' | 'remarks' | 'user_id' | 'status') => {
        if (sortBy.value === field) {
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy.value = field;
            sortOrder.value = 'asc';
        }
    };

    /**
     * toggleDropdown: Toggle the action dropdown menu for a document
     * @param {number} documentId - The ID of the document
     * If dropdown is already open for this document, close it
     * Otherwise, open the dropdown for this document (closes any other open dropdowns)
     */
    const toggleDropdown = (documentId: number) => {
        activeDropdown.value = activeDropdown.value === documentId ? null : documentId;
    };

    return {
        documentsForTabCounts,
        statusTabs,
        getTimeLeftInfo,
        getTimeLeftText,
        getTimeLeftStyles,
        getDisplayStatus,
        documentRowClass,
        statusBadge,
        filteredDocuments,
        totalPages,
        paginationPages,
        paginatedDocuments,
        canReceiveDocument,
        getCustodianDisplay,
        changePage,
        toggleSort,
        toggleDropdown,
    };
}
