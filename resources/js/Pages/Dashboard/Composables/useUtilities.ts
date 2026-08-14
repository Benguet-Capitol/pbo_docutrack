/**
 * Composable for utility and formatting functions
 */
export const useUtilities = () => {
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
     * Format hours into readable string
     */
    const formatHours = (hours: number): string => {
        if (hours < 1) {
            return Math.round(hours * 60) + ' min';
        }
        if (hours < 24) {
            return hours.toFixed(1) + ' hrs';
        }
        const days = Math.floor(hours / 24);
        const remainingHours = hours % 24;
        return `${days} day${days > 1 ? 's' : ''} ${remainingHours.toFixed(1)} hrs`;
    };

    /**
     * Format duration in hours to a human-readable string
     */
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

    /**
     * Format date range from from_date and to_date
     */
    const formatDateRange = (fromDate: any, toDate: any): string => {
        if (!fromDate && !toDate) return '';

        try {
            if (fromDate && toDate) {
                const start = new Date(fromDate);
                const end = new Date(toDate);
                if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
                    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
                }
            } else if (fromDate) {
                const date = new Date(fromDate);
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
        } catch {
            // Fallback
        }

        return fromDate || toDate || '';
    };

    /**
     * Format inclusive dates from array or string format
     */
    const formatInclusiveDates = (dates: any): string => {
        if (!dates) return '';

        let datesArray = dates;
        if (typeof dates === 'string' && dates.startsWith('[')) {
            try {
                datesArray = JSON.parse(dates);
            } catch {
                // Not a JSON array
            }
        }

        if (Array.isArray(datesArray)) {
            const formattedParts = datesArray.map((element: any) => {
                const elementStr = String(element).trim();

                if (elementStr.includes(' - ')) {
                    const rangeParts = elementStr.split(' - ').map(p => p.trim());
                    if (rangeParts.length === 2) {
                        try {
                            const startDate = new Date(rangeParts[0]);
                            const endDate = new Date(rangeParts[1]);
                            if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                                const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                return `${startFormatted} - ${endFormatted}`;
                            }
                        } catch { }
                        return elementStr;
                    }
                }

                try {
                    const date = new Date(elementStr);
                    if (!isNaN(date.getTime())) {
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    }
                } catch { }

                return elementStr;
            });

            return formattedParts.join(', ');
        } else if (typeof dates === 'string') {
            if (dates.includes(' - ')) {
                const parts = dates.split(' - ').map(p => p.trim());
                if (parts.length === 2) {
                    try {
                        const startDate = new Date(parts[0]);
                        const endDate = new Date(parts[1]);
                        if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                            const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            return `${startFormatted} - ${endFormatted}`;
                        }
                    } catch {
                        return dates;
                    }
                } else if (parts.length > 2) {
                    const formattedParts = [];
                    for (let i = 0; i < parts.length; i++) {
                        try {
                            const date = new Date(parts[i]);
                            if (!isNaN(date.getTime())) {
                                formattedParts.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                            } else {
                                formattedParts.push(parts[i]);
                            }
                        } catch {
                            formattedParts.push(parts[i]);
                        }
                    }
                    return formattedParts.join(', ');
                }
            } else {
                try {
                    const date = new Date(dates);
                    if (!isNaN(date.getTime())) {
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    }
                } catch {
                    return dates;
                }
            }
            return dates;
        }

        return '';
    };

    /**
     * Format time from HH:MM:SS to HH:MM AM/PM
     */
    const formatTime = (timeString: string): string => {
        if (!timeString) return '';

        try {
            const parts = timeString.split(':');
            if (parts.length < 2) return timeString;

            let hours = parseInt(parts[0], 10);
            const minutes = parts[1];

            const am_pm = hours >= 12 ? 'PM' : 'AM';
            if (hours > 12) hours -= 12;
            if (hours === 0) hours = 12;

            const hoursStr = String(hours).padStart(2, '0');
            return `${hoursStr}:${minutes} ${am_pm}`;
        } catch {
            return timeString;
        }
    };

    /**
     * Format date and time
     */
    const formatDateTime = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    };

    /**
     * Format date to words (e.g., February 12, 2026)
     */
    const formatDateToWords = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    /**
     * Get other leave types (not in the main 5 columns) for an employee
     */
    const getOtherLeaveTypes = (empSummary: any): string[] => {
        const allowedTypes = [
            'Sick Leave',
            'Vacation Leave',
            'Mandatory/Forced Leave',
            'Special Privilege Leave',
            'Wellness Leave'
        ];

        return Object.keys(empSummary.leaveTypes).filter(type => !allowedTypes.includes(type));
    };

    /**
     * Get other leave types as a map with their counts
     */
    const getOtherLeaveTypesMap = (empSummary: any): Record<string, number> => {
        const otherTypes: Record<string, number> = {};
        getOtherLeaveTypes(empSummary).forEach(type => {
            otherTypes[type] = empSummary.leaveTypes[type];
        });
        return otherTypes;
    };

    /**
     * Capitalize first letter of each word
     */
    const capitalizeWords = (str: string): string => {
        return str.toUpperCase();
    };

    return {
        getActionType,
        formatHours,
        formatDuration,
        formatDateRange,
        formatInclusiveDates,
        formatTime,
        formatDateTime,
        formatDateToWords,
        getOtherLeaveTypes,
        getOtherLeaveTypesMap,
        capitalizeWords
    };
};
