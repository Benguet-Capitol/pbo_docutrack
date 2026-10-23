/**
 * One look for every list table (same as Documents, and pbo-registryV2's lists): gray header, opaque rows shaded every
 * other row with a blue hover, cells aligned to the top, and the Actions column pinned on the right.
 * Pinned cells stay at z 1-3 so they cover the other cells while scrolling but never the sidebar (z-20).
 */

/** <table class="..."> */
export const LIST_TABLE = 'w-full text-left text-xs border-separate border-spacing-0';

/** Every <th> */
export const LIST_HEAD = 'px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-900 border-b-2 border-b-gray-300 dark:border-b-gray-700';

/** Sort button inside a <th> */
export const LIST_SORT = 'flex items-center gap-1 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors';

/** Every <td> */
export const LIST_CELL = 'px-3 py-2.5 align-top';

/** The <tr>: borders between rows, shading every other row, highlighted when active (e.g. its details are open) */
export const listRowClass = (index: number, active = false): string =>
    [
        'transition-colors [&>td]:border-t [&>td]:border-t-gray-200 dark:[&>td]:border-t-gray-700',
        active
            ? 'bg-blue-100 dark:bg-[#1f305a]'
            : `${index % 2 === 1 ? 'bg-gray-50 dark:bg-[#192231]' : 'bg-white dark:bg-gray-800'} hover:bg-blue-50 dark:hover:bg-gray-700`,
    ].join(' ');

/** First column pinned on the left (header and cells) */
export const PINNED_LEFT_HEAD = 'sticky left-0 z-[2]';
export const PINNED_LEFT_CELL = 'sticky left-0 z-[1] bg-inherit';

/** Actions column pinned on the right (header and cells) */
export const ACTIONS_HEAD = 'sticky right-0 z-[2] text-center shadow-[inset_1px_0_0_theme(colors.gray.300)] dark:shadow-[inset_1px_0_0_theme(colors.gray.700)]';
export const ACTIONS_CELL = 'sticky right-0 z-[1] hover:z-[3] focus-within:z-[3] bg-inherit text-center shadow-[inset_1px_0_0_theme(colors.gray.200)] dark:shadow-[inset_1px_0_0_theme(colors.gray.700)]';

/** Main value of a grouped first column, and the small tag under it */
export const LIST_TITLE = 'text-xs font-semibold text-gray-900 dark:text-gray-100 break-words';
export const LIST_TAG = 'inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold';
export const LIST_META = 'text-xs text-gray-500 dark:text-gray-400';

/** Soft-tinted tags for employees and dates */
export const EMPLOYEE_TAG = 'rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
export const DATE_TAG = 'whitespace-nowrap rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';

/** A header: its label, the field it sorts by (if sortable) and extra classes */
export interface ListColumn {
    label: string;
    sort?: string;
    class?: string;
}

/** "Jul 7, 2026" from "2026-07-07" (no timezone shift) */
export const listDate = (value: string | null | undefined): string => {
    if (!value) return '—';
    const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);
    return y && m && d ? new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : String(value);
};
