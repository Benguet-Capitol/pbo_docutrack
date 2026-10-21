import { leaveDays, formatDays } from '@/Composables/useLeaveCredits';

/**
 * Helpers for the printable CS Form No. 6 (Application for Leave).
 * They mirror App\Services\LeaveFormExporter so the printed form and the Excel file match.
 */

/** Leave types with their own checkbox on the 2020 form (others go on the "Others" line) */
export const FORM_LEAVE_TYPES: Array<{ type: string; label: string }> = [
    { type: 'Vacation Leave', label: 'Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: 'Mandatory/Forced Leave', label: 'Mandatory/Forced Leave (Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: 'Sick Leave', label: 'Sick Leave (Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: 'Maternity Leave', label: 'Maternity Leave (R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)' },
    { type: 'Paternity Leave', label: 'Paternity Leave (R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)' },
    { type: 'Special Privilege Leave', label: 'Special Privilege Leave (Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: 'Solo Parent Leave', label: 'Solo Parent Leave (RA No. 8972 / CSC MC No. 8, s. 2004)' },
    { type: 'Study Leave', label: 'Study Leave (Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: '10-Day VAWC Leave', label: '10-Day VAWC Leave (RA No. 9262 / CSC MC No. 15, s. 2005)' },
    { type: 'Rehabilitation Leave', label: 'Rehabilitation Privilege (Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)' },
    { type: 'Special Leave Benefits for Women', label: 'Special Leave Benefits for Women (RA No. 9710 / CSC MC No. 25, s. 2010)' },
    { type: 'Special Emergency (Calamity) Leave', label: 'Special Emergency (Calamity) Leave (CSC MC No. 2, s. 2012, as amended)' },
    { type: 'Adoption Leave', label: 'Adoption Leave (R.A. No. 8552)' },
];

/** Leave types whose 6.B location details apply */
export const LOCATION_LEAVE_TYPES = ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'];

/** Split "Juan D. Dela Cruz Jr." into last ("Dela Cruz Jr."), first ("Juan") and middle ("D.") */
export const splitName = (fullName: string): { last: string; first: string; middle: string } => {
    const tokens = String(fullName || '').trim().split(/\s+/).filter(Boolean);
    let suffix = '';
    if (tokens.length && /^(jr\.?|sr\.?|ii|iii|iv|v)$/i.test(tokens[tokens.length - 1])) {
        suffix = ' ' + tokens.pop();
    }
    if (tokens.length < 2) return { last: ((tokens[0] || '') + suffix).trim(), first: '', middle: '' };

    let initialAt = -1;
    tokens.forEach((token, i) => {
        if (i > 0 && /^[A-Za-z]\.?$/.test(token)) initialAt = i;
    });
    if (initialAt > 0 && initialAt < tokens.length - 1) {
        return {
            first: tokens.slice(0, initialAt).join(' '),
            middle: tokens[initialAt],
            last: tokens.slice(initialAt + 1).join(' ') + suffix,
        };
    }
    const last = tokens.pop() as string;
    return { first: tokens.join(' '), middle: '', last: last + suffix };
};

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

/** Parse "YYYY-MM-DD" as a local date (no timezone shift) */
const parseDay = (value: string) => {
    const [y, m, d] = String(value).trim().slice(0, 10).split('-').map(Number);
    return { y, m, d };
};

/** "October 21, 2026" */
export const longDate = (value: string | null | undefined): string => {
    if (!value) return '';
    const { y, m, d } = parseDay(value);
    return y && m && d ? `${MONTHS[m - 1]} ${d}, ${y}` : String(value);
};

/** "January 5-9, 2026; February 2, 2026" from entries like "2026-01-05 - 2026-01-09" or "2026-02-02" */
export const inclusiveDatesText = (entries: string[] | null | undefined): string =>
    (entries || []).filter(Boolean).map((entry) => {
        if (!entry.includes(' - ')) return longDate(entry);
        const [fromStr, toStr] = entry.split(' - ');
        const from = parseDay(fromStr);
        const to = parseDay(toStr);
        if (from.y === to.y && from.m === to.m && from.d === to.d) return longDate(fromStr);
        if (from.y === to.y && from.m === to.m) return `${MONTHS[from.m - 1]} ${from.d}-${to.d}, ${to.y}`;
        if (from.y === to.y) return `${MONTHS[from.m - 1]} ${from.d} - ${MONTHS[to.m - 1]} ${to.d}, ${to.y}`;
        return `${longDate(fromStr)} - ${longDate(toStr)}`;
    }).join('; ');

/** "3 days", "1 day", "0.5 day (AM)" */
export const daysText = (leave: { is_half_day?: boolean; half_day_period?: string | null; number_of_working_days_applied_for?: number | string | null }): string => {
    const days = leaveDays(leave);
    const text = `${formatDays(days)} ${days <= 1 ? 'day' : 'days'}`;
    return leave.is_half_day && leave.half_day_period ? `${text} (${leave.half_day_period})` : text;
};
