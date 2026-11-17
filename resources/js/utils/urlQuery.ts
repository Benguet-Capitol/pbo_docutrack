/**
 * A list page's starting filters from its link, e.g. /leaves?search=L-2026-10-0005&year=2026 from the navbar search.
 * Only read when the page opens; the filters work as usual afterwards.
 */
export const urlParam = (name: string): string | null => {
    if (typeof window === 'undefined') return null;
    const value = new URLSearchParams(window.location.search).get(name);
    return value === null || value.trim() === '' ? null : value;
};

/** ?year=2026 as a number; "all" for every year (null); undefined when the link names no year */
export const urlYear = (): number | null | undefined => {
    const value = urlParam('year');
    if (value === null) return undefined;
    if (value === 'all') return null;
    const year = Number(value);
    return Number.isInteger(year) && year > 1900 ? year : undefined;
};
