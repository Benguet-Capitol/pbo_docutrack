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
    { type: 'Wellness Leave', label: 'Wellness Leave (CSC MC No. 3, s. 2025)' },
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

/**
 * "January 5-9, 2026; February 2, 2026" from entries like "2026-01-05 - 2026-01-09" or "2026-02-02".
 * Dates and ranges next to each other in the same month share the month and year: "November 13, 16, 27, 2026".
 * Keep in sync with LeaveFormExporter::inclusiveDatesText().
 */
export const inclusiveDatesText = (entries: string[] | null | undefined): string => {
    const parts: string[] = [];
    // The month being written: its year, month and days ("13", "3-5")
    let group: { y: number; m: number; days: string[] } | null = null;
    const flush = () => {
        if (group) parts.push(`${MONTHS[group.m - 1]} ${group.days.join(', ')}, ${group.y}`);
        group = null;
    };

    for (const entry of (entries || []).filter(Boolean)) {
        const [fromStr, toStr = fromStr] = entry.split(' - ');
        const from = parseDay(fromStr);
        const to = parseDay(toStr);
        if (!from.y || !to.y) {
            flush();
            parts.push(entry);
        } else if (from.y === to.y && from.m === to.m) {
            const days = from.d === to.d ? `${from.d}` : `${from.d}-${to.d}`;
            if (group && group.y === from.y && group.m === from.m) {
                group.days.push(days);
            } else {
                flush();
                group = { y: from.y, m: from.m, days: [days] };
            }
        } else {
            flush();
            parts.push(from.y === to.y
                ? `${MONTHS[from.m - 1]} ${from.d} - ${MONTHS[to.m - 1]} ${to.d}, ${to.y}`
                : `${longDate(fromStr)} - ${longDate(toStr)}`);
        }
    }
    flush();

    return parts.join('; ');
};

/** "3 working days", "1 working day", "0.5 working day (AM)" */
export const daysText = (leave: { is_half_day?: boolean; half_day_period?: string | null; number_of_working_days_applied_for?: number | string | null }): string => {
    const days = leaveDays(leave);
    const text = `${formatDays(days)} ${days <= 1 ? 'working day' : 'working days'}`;
    return leave.is_half_day && leave.half_day_period ? `${text} (${leave.half_day_period})` : text;
};

/** Short names used when several leave types share a line on the form. Mirrors LeaveFormExporter::LEAVE_ABBREVIATIONS */
export const LEAVE_ABBREVIATIONS: Record<string, string> = {
    'Vacation Leave': 'VL',
    'Mandatory/Forced Leave': 'FL',
    'Sick Leave': 'SL',
    'Maternity Leave': 'ML',
    'Paternity Leave': 'PL',
    'Special Privilege Leave': 'SPL',
    'Solo Parent Leave': 'SoPL',
    'Study Leave': 'StL',
    '10-Day VAWC Leave': 'VAWC',
    'Rehabilitation Leave': 'RL',
    'Special Leave Benefits for Women': 'SLBW',
    'Special Emergency (Calamity) Leave': 'SEL',
    'Adoption Leave': 'AL',
    'Wellness Leave': 'WL',
};

/** "VL", "SPL", ...; an "Others" leave keeps its kind, e.g. "Terminal Leave" */
export const leaveTypeShort = (leave: { type_of_leave?: string; other_type?: string | null }): string =>
    leave.type_of_leave === 'Others' ? (leave.other_type || 'Others') : (LEAVE_ABBREVIATIONS[leave.type_of_leave || ''] || leave.type_of_leave || '');

/** "Terminal Leave" for an "Others" leave, else the leave type */
export const leaveTypeName = (leave: { type_of_leave?: string; other_type?: string | null }): string =>
    leave.type_of_leave === 'Others' ? (leave.other_type || 'Others') : (leave.type_of_leave || '');

type PrintEntry = Parameters<typeof daysText>[0] & { type_of_leave?: string; other_type?: string | null; inclusive_dates?: string[] };

/** 6.C total: "4 days" (a lone entry keeps its own text, e.g. "0.5 day (AM)") */
export const applicationDaysText = (entries: PrintEntry[]): string => {
    if (entries.length === 1) return daysText(entries[0]);
    const days = entries.reduce((sum, entry) => sum + leaveDays(entry), 0);
    return `${formatDays(days)} ${days <= 1 ? 'working day' : 'working days'}`;
};

/** 6.C inclusive dates, one line per leave type ("VL: March 3-4, 2026 (2 working days)"); a lone type gets just its dates */
export const applicationDateLines = (entries: PrintEntry[]): string[] =>
    entries.length === 1
        ? [inclusiveDatesText(entries[0].inclusive_dates)]
        : entries.map((entry) => `${leaveTypeShort(entry)}: ${inclusiveDatesText(entry.inclusive_dates)} (${daysText(entry)})`);

/** 6.C inclusive dates; with several types, each type's dates: "VL: March 3-4, 2026 (2 working days); ..." */
export const applicationDatesText = (entries: PrintEntry[]): string =>
    entries.length === 1
        ? inclusiveDatesText(entries[0].inclusive_dates)
        : entries.map((entry) => `${leaveTypeShort(entry)}: ${inclusiveDatesText(entry.inclusive_dates)} (${daysText(entry)})`).join('; ');

const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

/** Weekdays (0 = Sunday) named in the off days, e.g. ["Saturday", "Sunday"] -> {6, 0}; Saturday and Sunday when none are entered */
export const offWeekdays = (offDays: string[] | null | undefined): Set<number> => {
    const text = (offDays || []).join(' ').toLowerCase();
    const days = WEEKDAYS.map((day, index) => (text.includes(day.slice(0, 3).toLowerCase()) ? index : -1)).filter((index) => index >= 0);
    return new Set(days.length || (offDays || []).length ? days : [0, 6]);
};

/** Off days as printed, e.g. "Saturdays and Sundays" (the default). Mirrors LeaveFormExporter::offDaysText() */
export const offDaysText = (offDays: string[] | null | undefined): string => {
    const set = offWeekdays(offDays);
    if (set.size === 0) return (offDays || []).join(', ');
    // Monday first, Sunday last
    const names = [...set].sort((a, b) => ((a + 6) % 7) - ((b + 6) % 7)).map((index) => `${WEEKDAYS[index]}s`);
    const last = names.pop() as string;
    return names.length ? `${names.join(', ')} and ${last}` : last;
};

/** The working days a leave covers (YYYY-MM-DD), without its off days. Mirrors Leave::workingDates() */
export const leaveWorkingDates = (entries: string[] | null | undefined, offDays: string[] | null | undefined): string[] => {
    const off = offWeekdays(offDays);
    const dates = new Set<string>();
    const pad = (n: number) => String(n).padStart(2, '0');
    for (const entry of entries || []) {
        const [fromStr, toStr] = entry.includes(' - ') ? entry.split(' - ') : [entry, entry];
        const from = parseDay(fromStr);
        const to = parseDay(toStr);
        if (!from.y || !to.y) continue;
        const day = new Date(from.y, from.m - 1, from.d);
        const end = new Date(to.y, to.m - 1, to.d);
        for (let guard = 0; day <= end && guard < 400; guard++, day.setDate(day.getDate() + 1)) {
            if (!off.has(day.getDay())) dates.add(`${day.getFullYear()}-${pad(day.getMonth() + 1)}-${pad(day.getDate())}`);
        }
    }
    return [...dates];
};

/** Working days in the inclusive dates (single dates and "from - to" ranges), skipping the off days */
export const countWorkingDays = (entries: string[] | null | undefined, offDays: string[] | null | undefined): number => {
    const off = offWeekdays(offDays);
    const seen = new Set<string>();
    for (const entry of entries || []) {
        const [fromStr, toStr] = entry.includes(' - ') ? entry.split(' - ') : [entry, entry];
        const from = parseDay(fromStr);
        const to = parseDay(toStr);
        if (!from.y || !to.y) continue;
        const day = new Date(from.y, from.m - 1, from.d);
        const end = new Date(to.y, to.m - 1, to.d);
        for (let guard = 0; day <= end && guard < 400; guard++, day.setDate(day.getDate() + 1)) {
            const key = `${day.getFullYear()}-${day.getMonth()}-${day.getDate()}`;
            if (!off.has(day.getDay())) seen.add(key);
        }
    }
    return seen.size;
};
