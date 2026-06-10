import { computed, ref, Ref } from 'vue';
import { useProcessingTime } from './useProcessingTime';

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

/**
 * Composable for statistics calculations
 */
export const useStatistics = (
    documents: Ref<Document[]>,
    selectedYear: Ref<number | null>,
    selectedSemester: Ref<number | null>,
    selectedUser: Ref<number | null>,
    expandedUserId: Ref<number | null>,
    expandedDocumentType: Ref<string | null>,
    offices: Ref<any[]>,
    municipalities: Ref<any[]>,
    users: Ref<any[]>
) => {
    const currentTime = ref(new Date());

    const formatHours = (hours: number): string => {
        if (hours < 0) return '—';
        const totalMinutes = Math.round(hours * 60);
        const days    = Math.floor(totalMinutes / (60 * 24));
        const hrs     = Math.floor((totalMinutes % (60 * 24)) / 60);
        const minutes = totalMinutes % 60;
        const parts: string[] = [];
        if (days    > 0) parts.push(`${days}d`);
        if (hrs     > 0) parts.push(`${hrs}h`);
        if (minutes > 0 || parts.length === 0) parts.push(`${minutes}m`);
        return parts.join(' ');
    };

    const { calculateElapsedTimeExcluding } = useProcessingTime(
        currentTime,
        offices,
        municipalities,
        formatHours,
        users
    );

    const expandedDocumentTypeLocal = ref<string | null>(null);

    /**
     * Helper function to get semester from date
     */
    const getSemester = (date: string | Date): number => {
        const d = new Date(date);
        const month = d.getMonth() + 1;
        return month <= 6 ? 1 : 2;
    };

    /**
     * Helper function to check if date matches year and semester
     */
    const matchesYearAndSemester = (date: string): boolean => {
        if (!selectedYear.value && !selectedSemester.value) {
            return true;
        }

        const docDate = new Date(date);
        const docYear = docDate.getFullYear();
        const docSemester = getSemester(date);

        if (selectedYear.value && docYear !== selectedYear.value) {
            return false;
        }
        if (selectedSemester.value && docSemester !== selectedSemester.value) {
            return false;
        }

        return true;
    };

    /**
     * Get action type from action string
     */
    const getActionType = (action: string): string => {
        if (action.toLowerCase().includes('created')) return 'created';
        if (action.toLowerCase().includes('forwarded')) return 'forwarded';
        if (action.toLowerCase().includes('received')) return 'received';
        if (action.toLowerCase().includes('ended') || action.toLowerCase().includes('finalized')) return 'finalized';
        return action;
    };

    /**
     * User statistics: average pending time per document
     */
    const userStatistics = computed(() => {
        const stats = new Map<number, { userId: number; name: string; totalHours: number; count: number; averageHours: number }>();

        documents.value.forEach(document => {
            if (!matchesYearAndSemester(document.date)) return;

            if (selectedUser.value) {
                const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
                if (!userHasTransaction) return;
            }

            const sortedTransactions = [...(document.transactions || [])].sort(
                (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
            );

            let currentOwner: { userId: number; userName: string; startTime: Date } | null = null;

            sortedTransactions.forEach((transaction) => {
                const action = getActionType(transaction.action);
                const transactionTime = new Date(transaction.created_at);

                if (action === 'created') {
                    currentOwner = {
                        userId: transaction.user_id,
                        userName: transaction.user?.name || 'Unknown',
                        startTime: transactionTime
                    };
                } else if (action === 'forwarded' && currentOwner) {
                    const existing = stats.get(currentOwner.userId) || {
                        userId: currentOwner.userId,
                        name: currentOwner.userName,
                        totalHours: 0,
                        count: 0,
                        averageHours: 0
                    };

                    const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);

                    existing.totalHours += pendingHours;
                    existing.count += 1;
                    existing.averageHours = existing.totalHours / existing.count;
                    stats.set(currentOwner.userId, existing);

                    if (transaction.forwardedToUser) {
                        currentOwner = {
                            userId: transaction.forwardedToUser.id,
                            userName: transaction.forwardedToUser.name,
                            startTime: transactionTime
                        };
                    } else {
                        currentOwner = null;
                    }
                } else if (action === 'received') {
                    currentOwner = {
                        userId: transaction.user_id,
                        userName: transaction.user?.name || 'Unknown',
                        startTime: transactionTime
                    };
                } else if (action === 'finalized' && currentOwner) {
                    const existing = stats.get(currentOwner.userId) || {
                        userId: currentOwner.userId,
                        name: currentOwner.userName,
                        totalHours: 0,
                        count: 0,
                        averageHours: 0
                    };

                    const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);

                    existing.totalHours += pendingHours;
                    existing.count += 1;
                    existing.averageHours = existing.totalHours / existing.count;
                    stats.set(currentOwner.userId, existing);

                    currentOwner = null;
                }
            });
        });

        return Array.from(stats.values())
            .sort((a, b) => b.averageHours - a.averageHours);
    });

    /**
     * Filter user statistics based on selected user
     */
    const filteredUserStatistics = computed(() => {
        let filtered = userStatistics.value;

        if (selectedUser.value) {
            filtered = filtered.filter(user => user.userId === selectedUser.value);
        }

        return filtered;
    });

    /**
     * Document processing statistics — correctly uses calculateElapsedTimeExcluding
     * so averages reflect real business processing time, not raw wall-clock duration.
     */
    const documentProcessingStatistics = computed(() => {
        const stats = new Map<string, { documentType: string; totalHours: number; count: number; averageHours: number }>();

        documents.value.forEach(document => {
            if (!matchesYearAndSemester(document.date)) return;

            if (!document.transactions || document.transactions.length === 0) return;

            const docType = document.document_type || 'Other';

            // transactions are stored newest-first; oldest (creation) is at the end
            const creationTransaction = document.transactions[document.transactions.length - 1];
            const createdAt = new Date(creationTransaction.created_at);

            const isFinished = document.status === 'finalized' || document.status === 'ended';
            const endTime = isFinished
                ? new Date(document.transactions[0].created_at)
                : new Date();

            // Use the proper business-hours calculator that excludes office/municipality periods
            const processingHours = calculateElapsedTimeExcluding(document, createdAt, endTime);

            const existing = stats.get(docType) || {
                documentType: docType,
                totalHours: 0,
                count: 0,
                averageHours: 0
            };

            existing.totalHours += processingHours;
            existing.count += 1;
            existing.averageHours = existing.totalHours / existing.count;
            stats.set(docType, existing);
        });

        return Array.from(stats.values())
            .sort((a, b) => b.averageHours - a.averageHours);
    });

    /**
     * Get detailed breakdown for selected user
     */
    const selectedUserDetails = computed(() => {
        if (!expandedUserId.value && expandedUserId.value !== 0) return [];

        const currentSelectedUserStat = userStatistics.value.find(u => u.userId === expandedUserId.value);
        if (!currentSelectedUserStat) return [];

        const details: Array<{ documentId: number; trackingNo: string; particulars: string; pendingHours: number; startDate: string; endDate: string }> = [];

        documents.value.forEach(document => {
            if (!matchesYearAndSemester(document.date)) return;

            if (selectedUser.value) {
                const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
                if (!userHasTransaction) return;
            }

            const sortedTransactions = [...(document.transactions || [])].sort(
                (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
            );

            let currentOwner: { userId: number; startTime: Date } | null = null;

            sortedTransactions.forEach((transaction) => {
                const action = getActionType(transaction.action);
                const transactionTime = new Date(transaction.created_at);

                if (action === 'created') {
                    currentOwner = {
                        userId: transaction.user_id,
                        startTime: transactionTime
                    };
                } else if (action === 'forwarded' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                    const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);

                    details.push({
                        documentId: document.id,
                        trackingNo: document.tracking_no,
                        particulars: document.particulars || '-',
                        pendingHours,
                        startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                            month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit', hour12: true
                        }),
                        endDate: new Date(transactionTime).toLocaleString('en-US', {
                            month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit', hour12: true
                        })
                    });

                    if (transaction.forwardedToUser) {
                        currentOwner = {
                            userId: transaction.forwardedToUser.id,
                            startTime: transactionTime
                        };
                    } else {
                        currentOwner = null;
                    }
                } else if (action === 'received') {
                    currentOwner = {
                        userId: transaction.user_id,
                        startTime: transactionTime
                    };
                } else if (action === 'finalized' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                    const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);

                    details.push({
                        documentId: document.id,
                        trackingNo: document.tracking_no,
                        particulars: document.particulars || '-',
                        pendingHours,
                        startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                            month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit', hour12: true
                        }),
                        endDate: new Date(transactionTime).toLocaleString('en-US', {
                            month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit', hour12: true
                        })
                    });

                    currentOwner = null;
                }
            });
        });

        return details;
    });

    return {
        userStatistics,
        filteredUserStatistics,
        documentProcessingStatistics,
        selectedUserDetails
    };
};