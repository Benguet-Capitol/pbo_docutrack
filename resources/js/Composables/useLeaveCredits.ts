import { yearOf } from '@/Composables/useYearFilter';
import { leaveWorkingDates } from '@/Pages/Leaves/Composables/useLeaveFormPrint';

/**
 * Yearly limits (by the year of the inclusive dates) for leave types with a fixed number of days per year.
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
    recommendation?: string | null;
    inclusive_dates?: string[] | null;
    off_days?: string[] | null;
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

/**
 * leaveDaysByYear: days a leave counts against each year's limit, by the year of its inclusive dates (not of filing):
 * a leave filed in November for January counts in January's year. { year: days }; dates crossing into the next year
 * split the days by working dates; with no dates, the filing year. Mirrors Leave::daysByYear() on the server.
 */
export const leaveDaysByYear = (
    leave: Pick<LeaveLike, 'is_half_day' | 'number_of_working_days_applied_for' | 'inclusive_dates' | 'off_days'> & { date_of_filing?: string | null },
): Record<number, number> => {
    const total = leaveDays(leave);
    const dates = leaveWorkingDates(leave.inclusive_dates ?? [], leave.off_days ?? null);
    if (!dates.length) return { [yearOf(leave.date_of_filing ?? '') ?? new Date().getFullYear()]: total };
    const perYear: Record<number, number> = {};
    for (const date of dates) perYear[Number(date.slice(0, 4))] = (perYear[Number(date.slice(0, 4))] ?? 0) + 1;
    for (const year of Object.keys(perYear)) perYear[Number(year)] = (total * perYear[Number(year)]) / dates.length;
    return perYear;
};

/** A leave recommended for disapproval didn't proceed: left out of the Dashboard summaries and the Locator Chart, but it still uses the yearly limit (it was filed) */
export const isDisapproved = (leave: { recommendation?: string | null }): boolean => leave?.recommendation === 'disapproval';

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

    // By the year of each leave's inclusive dates, as the server counts them
    const used = leaves
        .filter((leave) => leave.employee_id === employeeId
            && leave.type_of_leave === type
            && (excludeId == null || leave.id !== excludeId))
        .reduce((sum, leave) => sum + (leaveDaysByYear(leave)[year] ?? 0), 0);

    return { type, year, limit, used, remaining: Math.max(0, limit - used) };
};
