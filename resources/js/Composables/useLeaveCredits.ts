import { yearOf } from '@/Composables/useYearFilter';

/**
 * Yearly limits (by year of filing) for leave types with a fixed number of days per year.
 * Keep in sync with Leave::YEARLY_LIMITS on the server, which enforces them.
 */
export const LEAVE_LIMITS: Record<string, number> = {
    'Mandatory/Forced Leave': 5,
    'Special Privilege Leave': 3,
    'Wellness Leave': 5,
};

export const LIMITED_LEAVE_TYPES = Object.keys(LEAVE_LIMITS);

export interface LeaveLike {
    id?: number;
    employee_id: number;
    type_of_leave: string;
    date_of_filing: string;
    is_half_day?: boolean;
    number_of_working_days_applied_for?: number | string | null;
}

export interface LeaveCredit {
    type: string;
    year: number;
    limit: number;
    used: number;
    remaining: number;
}

/** leaveDays: days a leave counts against its limit (half day = 0.5) */
export const leaveDays = (leave: Pick<LeaveLike, 'is_half_day' | 'number_of_working_days_applied_for'>): number =>
    leave.is_half_day ? 0.5 : Number(leave.number_of_working_days_applied_for) || 0;

/** formatDays: 2 -> "2", 2.5 -> "2.5" */
export const formatDays = (days: number): string => String(Math.round(days * 10) / 10);

/**
 * leaveCredit: an employee's used and remaining days of a limited leave type in a year,
 * or null when the type has no yearly limit.
 * @param excludeId - leave being edited, so it isn't counted twice
 */
export const leaveCredit = (
    leaves: LeaveLike[],
    employeeId: number,
    type: string,
    year: number,
    excludeId?: number | null,
): LeaveCredit | null => {
    const limit = LEAVE_LIMITS[type];
    if (limit === undefined) return null;

    const used = leaves
        .filter((leave) => leave.employee_id === employeeId
            && leave.type_of_leave === type
            && yearOf(leave.date_of_filing) === year
            && (excludeId == null || leave.id !== excludeId))
        .reduce((sum, leave) => sum + leaveDays(leave), 0);

    return { type, year, limit, used, remaining: Math.max(0, limit - used) };
};
