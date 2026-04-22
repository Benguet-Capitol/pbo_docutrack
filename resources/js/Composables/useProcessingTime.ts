import { computed, ref, Ref } from 'vue';

interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string;
    source: string;
    status: string;
    remarks: string;
    user_id: number;
    user?: { id: number; name: string; email: string } | null;
    transactions: any[];
}

interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id: number | null;
    forwarded_to_office_id: number | null;
    forwarded_to_municipality_id: number | null;
    action: string;
    remarks: string;
    created_at: string;
    duration_hours: number;
    user?: { id: number; name: string; email: string } | null;
    forwardedToUser?: { id: number; name: string; email: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
}

/**
 * Composable for document processing time calculations
 */
export const useProcessingTime = (
    currentTime: Ref<Date>,
    offices: Ref<any[]>,
    municipalities: Ref<any[]>,
    formatHours?: (hours: number) => string,
    users?: Ref<any[]>
) => {
    /**
     * Get processing time limit in days based on document type
     */
    const getProcessingTimeLimit = (docType: string): number => {
        const type = docType.toLowerCase().trim();
        if (type.includes('annual')) return 25;
        if (type.includes('supplemental')) return 12;
        if (type.includes('proposal') || type.includes('proposals')) return 12;
        if (type.includes('referral')) {
            if (type.includes('simple')) return 3;
            if (type.includes('complex')) return 7;
            if (type.includes('highly technical')) return 20;
        }
        return 30;
    };

    /**
     * Calculate business days (excluding weekends) between two dates
     */
    const calculateBusinessDays = (startDate: Date, endDate: Date): number => {
        let count = 0;
        const current = new Date(startDate);

        while (current <= endDate) {
            const dayOfWeek = current.getDay();
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
    const calculateBusinessHoursElapsed = (startDate: Date, endDate: Date): number => {
        let businessHours = 0;
        let current = new Date(startDate);
        const end = new Date(endDate);

        while (current < end) {
            const dayOfWeek = current.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                const nextDay = new Date(current);
                nextDay.setDate(nextDay.getDate() + 1);
                nextDay.setHours(0, 0, 0, 0);

                if (nextDay <= end) {
                    const msToMidnight = nextDay.getTime() - current.getTime();
                    businessHours += msToMidnight / (1000 * 60 * 60);
                } else {
                    const msElapsed = end.getTime() - current.getTime();
                    businessHours += msElapsed / (1000 * 60 * 60);
                }
            }
            current.setDate(current.getDate() + 1);
            current.setHours(0, 0, 0, 0);
        }

        return businessHours;
    };

    /**
     * Calculate remaining business days and hours (excluding weekends)
     */
    const calculateRemainingBusinessTime = (remainingHours: number): { days: number; hours: number } => {
        let remainingBusinessHours = remainingHours;
        let businessDays = 0;
        const now = new Date();
        let current = new Date(now);

        while (remainingBusinessHours > 0) {
            const dayOfWeek = current.getDay();
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                if (remainingBusinessHours >= 24) {
                    remainingBusinessHours -= 24;
                    businessDays += 1;
                } else {
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
    const calculateElapsedTimeExcluding = (document: Document, startDate: Date, endDate: Date): number => {
        if (!document.transactions || document.transactions.length === 0) {
            return calculateBusinessHoursElapsed(startDate, endDate);
        }

        const excludePeriods: Array<{ start: Date; end: Date }> = [];

        for (let i = 0; i < document.transactions.length; i++) {
            const transaction = document.transactions[i];

            if ((transaction.forwarded_to_office_id || transaction.forwarded_to_municipality_id) &&
                transaction.action.toLowerCase().includes('forward')) {

                const forwardStart = new Date(transaction.created_at);
                let forwardEnd = endDate;

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

                if (forwardStart < endDate && forwardEnd > startDate) {
                    excludePeriods.push({
                        start: new Date(Math.max(forwardStart.getTime(), startDate.getTime())),
                        end: new Date(Math.min(forwardEnd.getTime(), endDate.getTime()))
                    });
                }
            }
        }

        let totalElapsed = calculateBusinessHoursElapsed(startDate, endDate);

        for (const period of excludePeriods) {
            const excludedHours = calculateBusinessHoursElapsed(period.start, period.end);
            totalElapsed -= excludedHours;
        }

        return Math.max(0, totalElapsed);
    };

    /**
     * Calculate time left for processing
     */
    const getTimeLeftInfo = (document: Document): { daysLeft: number; hoursLeft: number; isLapsed: boolean; percentage: number } => {
        if (!document.transactions || document.transactions.length === 0) {
            return { daysLeft: 0, hoursLeft: 0, isLapsed: false, percentage: 0 };
        }

        const limit = getProcessingTimeLimit(document.document_type);
        const creationTransaction = document.transactions[document.transactions.length - 1];
        const createdDate = new Date(creationTransaction.created_at);

        let now = currentTime.value;
        if (document.status === 'ended' || document.status === 'finalized') {
            const lastTransaction = document.transactions[0];
            now = new Date(lastTransaction.created_at);
        }

        const totalHoursLimit = limit * 24;
        const hoursElapsed = calculateElapsedTimeExcluding(document, createdDate, now);
        const totalHoursLeft = Math.max(0, totalHoursLimit - hoursElapsed);
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

        if (isLapsed) return `Overdue`;
        if (daysLeft === 0 && hoursLeft === 0) return `No time left`;
        if (daysLeft === 0) return `${hoursLeft} hrs`;
        if (hoursLeft === 0) return `${daysLeft} days`;

        return `${daysLeft} days ${hoursLeft} hrs`;
    };

    /**
     * Get the style classes for time left cell based on remaining time
     */
    const getTimeLeftStyles = (document: Document): object => {
        const { isLapsed, percentage } = getTimeLeftInfo(document);

        let bgColor = '';
        let textColor = '';
        let borderColor = '';

        if (isLapsed) {
            bgColor = 'bg-red-100 dark:bg-red-900/30';
            textColor = 'text-red-800 dark:text-red-300';
            borderColor = 'border-red-200 dark:border-red-800';
        } else if (percentage >= 75) {
            bgColor = 'bg-red-100 dark:bg-red-900/30';
            textColor = 'text-red-800 dark:text-red-300';
            borderColor = 'border-red-200 dark:border-red-800';
        } else if (percentage >= 50) {
            bgColor = 'bg-orange-100 dark:bg-orange-900/30';
            textColor = 'text-orange-800 dark:text-orange-300';
            borderColor = 'border-orange-200 dark:border-orange-800';
        } else if (percentage >= 25) {
            bgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
            textColor = 'text-yellow-800 dark:text-yellow-300';
            borderColor = 'border-yellow-200 dark:border-yellow-800';
        } else {
            bgColor = 'bg-green-100 dark:bg-green-900/30';
            textColor = 'text-green-800 dark:text-green-300';
            borderColor = 'border-green-200 dark:border-green-800';
        }

        return {
            'inline-flex': true,
            'items-center': true,
            'gap-1': true,
            'px-3': true,
            'py-1': true,
            [bgColor]: true,
            [textColor]: true,
            'rounded-full': true,
            'border': true,
            [borderColor]: true,
            'whitespace-nowrap': true
        };
    };

    /**
     * Calculate processing time from first 'created' transaction to latest transaction
     */
    const calculateProcessingTime = (document: Document, formatter?: (h: number) => string): string => {
        if (!document.transactions || document.transactions.length === 0) {
            return '-';
        }

        const creationTransaction = document.transactions[document.transactions.length - 1];
        const createdAt = new Date(creationTransaction.created_at);
        const latestTransaction = document.transactions[0];
        const endTime = (document.status !== 'finalized' && document.status !== 'ended')
            ? new Date()
            : new Date(latestTransaction.created_at);

        const businessHoursElapsed = calculateElapsedTimeExcluding(document, createdAt, endTime);

        // Use provided formatter, passed formatter, or default formatting
        const hoursFormatter = formatter || formatHours || ((h: number) => `${h.toFixed(1)}h`);
        return hoursFormatter(businessHoursElapsed);
    };

    /**
     * Get custodian display name: office/municipality/user based on latest transaction
     */
    const getCustodianName = (document: Document): string => {
        if (document.status === 'finalized') {
            return '-';
        }

        if (document.transactions.length === 0) {
            return document.user?.name || 'Unknown';
        }

        const latestTransaction = document.transactions[0];

        if (latestTransaction.forwarded_to_office_id) {
            const office = offices.value.find(o => o.id === latestTransaction.forwarded_to_office_id);
            if (office) {
                return office.office_name;
            }
        }

        if (latestTransaction.forwarded_to_municipality_id) {
            const municipality = municipalities.value.find(m => m.id === latestTransaction.forwarded_to_municipality_id);
            if (municipality) {
                return municipality.name;
            }
        }

        if (latestTransaction.forwarded_to_user_id && users) {
            const hasReceived = document.transactions.some(t =>
                t.forwarded_to_user_id === latestTransaction.forwarded_to_user_id &&
                t.action.toLowerCase().includes('received')
            );

            if (hasReceived) {
                const forwardedUser = users.value.find(u => u.id === latestTransaction.forwarded_to_user_id);
                if (forwardedUser) {
                    return forwardedUser.name;
                }
            } else {
                return latestTransaction.user?.name || document.user?.name || 'Unknown';
            }
        }

        return latestTransaction.user?.name || document.user?.name || 'Unknown';
    };

    return {
        getProcessingTimeLimit,
        calculateBusinessDays,
        calculateBusinessHoursElapsed,
        calculateRemainingBusinessTime,
        calculateElapsedTimeExcluding,
        getTimeLeftInfo,
        getTimeLeftText,
        getTimeLeftStyles,
        calculateProcessingTime,
        getCustodianName
    };
};
