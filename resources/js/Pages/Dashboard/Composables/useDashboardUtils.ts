import { Ref } from 'vue';
import { Document } from './useDashboardData';

export function useDashboardUtils(currentTime: Ref<Date>) {
    // ============== Utility Functions ==============

    const formatHours = (hours: number): string => {
        if (!hours || hours === 0) return '0m';
        
        const totalMinutes = Math.round(hours * 60);
        const d = Math.floor(totalMinutes / (24 * 60));
        const h = Math.floor((totalMinutes % (24 * 60)) / 60);
        const m = totalMinutes % 60;

        const parts = [];
        if (d > 0) parts.push(`${d}d`);
        if (h > 0) parts.push(`${h}h`);
        if (m > 0) parts.push(`${m}m`);

        return parts.length > 0 ? parts.join(' ') : '0m';
    };

    const formatDuration = (hours: number): string => {
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
            parts.push(`${days}d`);
        }
        if (durationHours > 0) {
            parts.push(`${durationHours}h`);
        }
        if (minutes > 0) {
            parts.push(`${minutes}m`);
        }

        return parts.length > 0 ? parts.join(' ') : '0m';
    };

    const formatTime = (time: string): string => {
        if (!time) return '-';
        try {
            const date = new Date(time);
            return date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                hour12: true 
            });
        } catch {
            return time;
        }
    };

    const formatDateRange = (fromDate: string, toDate: string): string => {
        if (!fromDate || !toDate) return '';
        const from = new Date(fromDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const to = new Date(toDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        return `${from} - ${to}`;
    };

    const getActionType = (action: string): string => {
        if (action.toLowerCase().includes('created')) return 'created';
        if (action.toLowerCase().includes('forwarded')) return 'forwarded';
        if (action.toLowerCase().includes('received')) return 'received';
        if (action.toLowerCase().includes('ended') || action.toLowerCase().includes('finalized')) return 'finalized';
        return action;
    };

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

    const calculateElapsedTimeExcluding = (document: Document, startDate: Date, endDate: Date): number => {
        if (!document.transactions || document.transactions.length === 0) {
            return calculateBusinessHoursElapsed(startDate, endDate);
        }
        
        const excludePeriods: Array<{start: Date; end: Date}> = [];
        
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

        return `${daysLeft}d`;
    };

    const getTimeLeftStyles = (document: Document): string => {
        const { isLapsed, percentage } = getTimeLeftInfo(document);
        
        if (isLapsed) {
            return 'px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-xs font-medium';
        }
        
        if (percentage >= 80) {
            return 'px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded-full text-xs font-medium';
        }
        
        if (percentage >= 50) {
            return 'px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-medium';
        }
        
        return 'px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium';
    };

    const calculateProcessingTime = (document: Document): string => {
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
        
        return formatHours(businessHoursElapsed);
    };

    return {
        formatHours,
        formatDuration,
        formatTime,
        formatDateRange,
        getActionType,
        calculateBusinessDays,
        calculateBusinessHoursElapsed,
        calculateRemainingBusinessTime,
        calculateElapsedTimeExcluding,
        getProcessingTimeLimit,
        getTimeLeftInfo,
        getTimeLeftText,
        getTimeLeftStyles,
        calculateProcessingTime,
    };
}
