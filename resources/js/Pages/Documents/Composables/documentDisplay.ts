/**
 * Documents page helpers with no state: processing time limits and business-day maths, status and type styles,
 * transaction labels and the checklist's group layout.
 */
import type { Document } from './types';

// ============== Helper Functions ==============

/** Get CSRF token from meta tag for form submissions */
export const getCsrfToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    return token;
};

/**
 * Year shown in the list and tab counts, based on the document date (defaults to the current year).
 * Documents still in progress (forwarded / to be received / pending) stay visible in every year so they aren't missed;
 * "created only" and ended documents follow the year, since some are intentionally left as created.
 */
export const IN_PROGRESS_STATUSES = ['forwarded', 'pending'];

/** Active-tab underline + text color per status, matching the Status column badge colors */
export const tabActiveStyles: Record<string, string> = {
    all: 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400',
    created: 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400',
    forwarded: 'border-cyan-600 text-cyan-600 dark:text-cyan-400 dark:border-cyan-400',
    'to be received': 'border-orange-600 text-orange-600 dark:text-orange-400 dark:border-orange-400',
    pending: 'border-yellow-600 text-yellow-700 dark:text-yellow-400 dark:border-yellow-400',
    finalized: 'border-green-600 text-green-600 dark:text-green-400 dark:border-green-400',
};

/** Active-tab count badge background/text per status, matching the Status column badge colors */
export const tabBadgeStyles: Record<string, string> = {
    all: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
    created: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
    forwarded: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300',
    'to be received': 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
    finalized: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
};

/**
 * Get processing time limit in days based on document type
 */
export const getProcessingTimeLimit = (docType: string): number => {
    const type = docType.toLowerCase().trim();
    if (type.includes('annual')) return 25; // Annual Budget: 25 days
    if (type.includes('supplemental')) return 12; // Supplemental Budget: 12 days
    if (type.includes('proposal') || type.includes('proposals')) return 12; // Budget Proposals: 12 days
    if (type.includes('referral')) {
        if (type.includes('simple')) return 3; // Referral - Simple: 3 days
        if (type.includes('complex')) return 7; // Referral - Complex: 7 days
        if (type.includes('highly technical')) return 20; // Referral - Highly Technical: 20 days
    }
    return 30; // Default: 30 days for other types
};

/**
 * Calculate business days (excluding weekends) between two dates
 */
export const calculateBusinessDays = (startDate: Date, endDate: Date): number => {
    let count = 0;
    const current = new Date(startDate);
    
    while (current <= endDate) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            count++;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return count;
};

/**
 * Calculate business hours elapsed (excluding weekends)
 */
export const calculateBusinessHoursElapsed = (startDate: Date, endDate: Date): number => {
    let businessHours = 0;
    let current = new Date(startDate); // Keep actual start time (including hours/minutes)
    const end = new Date(endDate);
    
    // Iterate through each day
    while (current < end) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            const nextDay = new Date(current);
            nextDay.setDate(nextDay.getDate() + 1);
            nextDay.setHours(0, 0, 0, 0); // Next day at midnight
            
            if (nextDay <= end) {
                // Calculate hours from current time to next midnight
                const msToMidnight = nextDay.getTime() - current.getTime();
                businessHours += msToMidnight / (1000 * 60 * 60);
            } else {
                // Partial day - calculate hours from current time to end time
                const msElapsed = end.getTime() - current.getTime();
                businessHours += msElapsed / (1000 * 60 * 60);
            }
        }
        current.setDate(current.getDate() + 1);
        current.setHours(0, 0, 0, 0); // Set to midnight for next iteration
    }
    
    return businessHours;
};

/**
 * Calculate remaining business days and hours (excluding weekends)
 */
export const calculateRemainingBusinessTime = (remainingHours: number): { days: number; hours: number } => {
    let remainingBusinessHours = remainingHours;
    let businessDays = 0;
    const now = new Date();
    let current = new Date(now);
    
    // Count how many business hours we can fit in the calendar
    while (remainingBusinessHours > 0) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            if (remainingBusinessHours >= 24) {
                remainingBusinessHours -= 24;
                businessDays += 1;
            } else {
                // Remaining hours are less than a full day
                break;
            }
        }
        current.setDate(current.getDate() + 1);
    }
    
    return { days: businessDays, hours: Math.round(remainingBusinessHours) };
};

/**
 * Calculate elapsed time excluding periods when document was with office or municipality
 */
export const calculateElapsedTimeExcluding = (document: Document, startDate: Date, endDate: Date): number => {
    if (!document.transactions || document.transactions.length === 0) {
        return calculateBusinessHoursElapsed(startDate, endDate);
    }
    
    // Find all office/municipality forward transactions and their corresponding receive/return
    const excludePeriods: Array<{start: Date; end: Date}> = [];
    
    for (let i = 0; i < document.transactions.length; i++) {
        const transaction = document.transactions[i];
        
        // Check if this is a forward to office or municipality
        if ((transaction.forwarded_to_office_id || transaction.forwarded_to_municipality_id) &&
            transaction.action.toLowerCase().includes('forward')) {
            
            const forwardStart = new Date(transaction.created_at);
            
            // Find the corresponding received transaction or next user-to-user forward
            let forwardEnd = endDate; // Default to end date
            for (let j = i - 1; j >= 0; j--) {
                const nextTransaction = document.transactions[j];
                const isReceived = nextTransaction.action.toLowerCase().includes('received');
                const isUserForward = nextTransaction.forwarded_to_user_id && 
                                    !nextTransaction.forwarded_to_office_id && 
                                    !nextTransaction.forwarded_to_municipality_id &&
                                    nextTransaction.action.toLowerCase().includes('forward');
                
                if (isReceived || isUserForward) {
                    forwardEnd = new Date(nextTransaction.created_at);
                    break;
                }
            }
            
            // Only exclude if the period is within our time range
            if (forwardStart < endDate && forwardEnd > startDate) {
                excludePeriods.push({
                    start: new Date(Math.max(forwardStart.getTime(), startDate.getTime())),
                    end: new Date(Math.min(forwardEnd.getTime(), endDate.getTime()))
                });
            }
        }
    }
    
    // Calculate total elapsed time
    let totalElapsed = calculateBusinessHoursElapsed(startDate, endDate);
    
    // Subtract excluded periods
    for (const period of excludePeriods) {
        const excludedHours = calculateBusinessHoursElapsed(period.start, period.end);
        totalElapsed -= excludedHours;
    }
    
    return Math.max(0, totalElapsed);
};

/**
 * Helper function to determine action type from action string
 */
export const getActionType = (action: string): 'created' | 'forwarded' | 'finalized' | 'received' => {
    if (action.toLowerCase().includes('created')) return 'created';
    if (action.toLowerCase().includes('forwarded')) return 'forwarded';
    if (action.toLowerCase().includes('received')) return 'received';
    if (action.toLowerCase().includes('ended')) return 'finalized';
    return 'created'; // default
};

/**
 * formatDuration: Formats duration in hours to a human-readable string with days, hours, and minutes
 * @param hours - Duration in hours (can be decimal, e.g., 0.583333 for 35 minutes)
 * @returns Formatted string like "1 day 2 hours 30 minutes" or "45 minutes"
 */
export const formatDuration = (hours: number): string => {
    if (hours === null || hours === undefined) {
        return '0 minutes';
    }

    const totalMinutes = Math.round(hours * 60);
    const days = Math.floor(totalMinutes / (24 * 60));
    const remainingMinutesAfterDays = totalMinutes % (24 * 60);
    const durationHours = Math.floor(remainingMinutesAfterDays / 60);
    const minutes = remainingMinutesAfterDays % 60;

    const parts = [];
    if (days > 0) {
        parts.push(`${days} day${days > 1 ? 's' : ''}`);
    }
    if (durationHours > 0) {
        parts.push(`${durationHours} hour${durationHours > 1 ? 's' : ''}`);
    }
    if (minutes > 0) {
        parts.push(`${minutes} minute${minutes > 1 ? 's' : ''}`);
    }

    return parts.length > 0 ? parts.join(' ') : '0 minutes';
};

/**
 * statusBadge: label, badge classes and card accent for a document's display status (card view)
 */
export const STATUS_BADGES: Record<string, { label: string; classes: string; accent: string }> = {
    'created': { label: 'Created', classes: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300', accent: 'border-t-blue-500' },
    'forwarded': { label: 'Forwarded', classes: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300', accent: 'border-t-cyan-500' },
    'to be received': { label: 'To Be Received', classes: 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300', accent: 'border-t-orange-500' },
    'pending': { label: 'Pending', classes: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300', accent: 'border-t-yellow-500' },
    'finalized': { label: 'Transaction Ended', classes: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', accent: 'border-t-green-500' },
};

// List view (same look as pbo-registryV2 lists): header cells, the Document column's sort buttons, row shading
export const LIST_HEAD = 'px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-900 border-b-2 border-b-gray-300 dark:border-b-gray-700';

export const DOCUMENT_SORTS = [
    { key: 'tracking_no', label: 'No.' },
    { key: 'date', label: 'Date' },
    { key: 'document_type', label: 'Type' },
] as const;

/** Colour and icon per document type, so types can be told apart at a glance */
export const DOCUMENT_TYPE_STYLES: Record<string, { classes: string; icon: string }> = {
    // Soft tints that sit with the page's green and gray: budgets in greens/blues, referrals from slate to amber/rose by complexity
    'Annual Budget': { classes: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-800', icon: 'fas fa-book' },
    'Supplemental Budget': { classes: 'bg-teal-50 text-teal-700 ring-1 ring-inset ring-teal-200 dark:bg-teal-900/20 dark:text-teal-300 dark:ring-teal-800', icon: 'fas fa-book-medical' },
    'Budget Proposals': { classes: 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:ring-sky-800', icon: 'fas fa-file-invoice-dollar' },
    'Referral - Simple': { classes: 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-slate-900/20 dark:text-slate-300 dark:ring-slate-800', icon: 'fas fa-share-square' },
    'Referral - Complex': { classes: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800', icon: 'fas fa-share-square' },
    'Referral - Highly Technical': { classes: 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-800', icon: 'fas fa-share-square' },
};

export const documentTypeStyle = (type: string) =>
    DOCUMENT_TYPE_STYLES[type] ?? { classes: 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700', icon: 'fas fa-file-alt' };

/**
 * Helper: Check if this is the first item of group 'c'
 * Returns true for the parent item of group 'c'
 */
export const isFirstItemOfGroupC = (item: any): boolean => {
    if (item.group_letter !== 'c' || item.is_subitem) {
        return false;
    }
    return true;
};

/**
 * Helper: Check if this is the first item of group 'd'
 * Returns true for the parent item of group 'd'
 */
export const isFirstItemOfGroupD = (item: any): boolean => {
    if (item.group_letter !== 'd' || item.is_subitem) {
        return false;
    }
    return true;
};

/**
 * Helper: Check if this is the first item of group 'e'
 * Returns true for the parent item of group 'e'
 */
export const isFirstItemOfGroupE = (item: any): boolean => {
    if (item.group_letter !== 'e' || item.is_subitem) {
        return false;
    }
    return true;
};

/**
 * Helper: Check if this item is part of group 'c'
 */
export const isPartOfGroupC = (item: any): boolean => {
    return item.group_letter === 'c';
};

/**
 * Helper: Check if this item is part of group 'd'
 */
export const isPartOfGroupD = (item: any): boolean => {
    return item.group_letter === 'd';
};

/**
 * Helper: Check if this item is part of group 'e'
 */
export const isPartOfGroupE = (item: any): boolean => {
    return item.group_letter === 'e';
};

/**
 * Get custodian display name: office/municipality if forwarded to them, otherwise current user
 */
/**
 * Check if document is forwarded to an office or municipality
 * Returns true if the latest transaction shows forwarding to office or municipality
 */
export const isForwardedToOfficeOrMunicipality = (document: Document): boolean => {
    if (!document.transactions || document.transactions.length === 0) {
        return false;
    }

    const latestTransaction = document.transactions[0];
    // Check the ID fields directly (these are always present)
    return !!(latestTransaction.forwarded_to_office_id || latestTransaction.forwarded_to_municipality_id);
};

/**
 * Get the user ID of who forwarded the document
 * Returns the user_id from the latest transaction (who performed the forward action)
 */
export const getForwardedByUserId = (document: Document): number | null => {
    if (!document.transactions || document.transactions.length === 0) {
        return null;
    }

    const latestTransaction = document.transactions[0];
    const userId = latestTransaction.user_id;
    return userId ? Number(userId) : null;
};

/**
 * validateChecklistRequirements: Validates that required items are checked
 * For Annual Budget: letters a, b, c must have document AND signatories checked
 * For Supplemental Budget:
 *   - Letters a, b must have document AND signatories checked
 *   - Letter c: only parent document must be checked
 *   - Letter g: optional (not required)
 * For Budget Proposals:
 *   - 2 specific items must be checked (frontend-only validation)
 */
export const REQUIRED_BUDGET_PROPOSAL_ITEMS = [
    'LBP Form 2 (Program Appropriation & Obligation by Object of Expenditure)',
    'Project Procurement Management Plan',
];
