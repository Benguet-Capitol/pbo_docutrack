// Driver's Trip Ticket: generated from a travel order that has a driver and a government (RP) vehicle

type TripTicketSource = {
    vehicle?: string | null;
    driver?: string | null;
};

type TripDatesSource = {
    inclusive_dates?: string[] | null;
    from_date?: string | null;
    to_date?: string | null;
};

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

export const hasTripTicket = (order: TripTicketSource | null | undefined): boolean =>
    !!order && order.vehicle === 'RP Vehicle' && !!order.driver?.trim();

/** "Tomasa S. Atayoc, et. al." when there are other passengers (as on the PBO's trip ticket form) */
export const passengerText = (names: string[]): string => {
    const first = names[0] ?? '';
    return names.length > 1 ? `${first}, et. al.` : first;
};

type Day = { y: number; m: number; d: number };

const parseDay = (value: string): Day | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim());
    return match ? { y: +match[1], m: +match[2] - 1, d: +match[3] } : null;
};

const dayKey = (day: Day) => day.y * 10000 + day.m * 100 + day.d;

/**
 * Inclusive dates the way the trip ticket writes them:
 * "May 27-28, 2026", "September 28 to October 2, 2026", "November 13, 16, 27, 2026".
 * Each entry is "YYYY-MM-DD" or "YYYY-MM-DD - YYYY-MM-DD".
 */
export const tripDatesText = (order: TripDatesSource): string => {
    const entries = order.inclusive_dates?.length
        ? order.inclusive_dates
        : order.from_date ? [order.to_date && order.to_date !== order.from_date ? `${order.from_date} - ${order.to_date}` : order.from_date] : [];

    const ranges = entries
        .map((entry) => {
            const [startText, endText] = entry.split(' - ');
            const start = parseDay(startText);
            const end = endText ? parseDay(endText) : start;
            return start && end ? { start, end } : null;
        })
        .filter((range): range is { start: Day; end: Day } => range !== null)
        .sort((a, b) => dayKey(a.start) - dayKey(b.start));

    // Grouped by year; a range across a new year is written out in full
    const years: { year: number; parts: string[]; month: number | null }[] = [];
    for (const { start, end } of ranges) {
        if (start.y !== end.y) {
            years.push({ year: NaN, parts: [`${MONTHS[start.m]} ${start.d}, ${start.y} to ${MONTHS[end.m]} ${end.d}, ${end.y}`], month: null });
            continue;
        }
        let group = years[years.length - 1];
        if (!group || group.year !== start.y) {
            group = { year: start.y, parts: [], month: null };
            years.push(group);
        }
        const days = start.d === end.d ? `${start.d}` : `${start.d}-${end.d}`;
        if (start.m !== end.m) {
            group.parts.push(`${MONTHS[start.m]} ${start.d} to ${MONTHS[end.m]} ${end.d}`);
            group.month = null;
        } else if (group.month === start.m) {
            group.parts[group.parts.length - 1] += `, ${days}`;
        } else {
            group.parts.push(`${MONTHS[start.m]} ${days}`);
            group.month = start.m;
        }
    }

    return years
        .map((group) => (Number.isNaN(group.year) ? group.parts[0] : `${group.parts.join(', ')}, ${group.year}`))
        .join('; ');
};

export const longDate = (value: string | null | undefined): string => {
    const day = value ? parseDay(value) : null;
    return day ? `${MONTHS[day.m]} ${day.d}, ${day.y}` : '';
};
