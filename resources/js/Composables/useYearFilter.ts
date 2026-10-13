import { ref, computed, type Ref } from 'vue';

/**
 * Year of a date value. Plain dates ("2026-10-13") are read from the string; timestamps
 * ("2025-12-31T16:00:00Z", i.e. Jan 1 in Philippine time) are converted to local time first.
 */
export const yearOf = (value: string | null | undefined): number | null => {
    const text = String(value ?? '');
    if (text.includes('T')) {
        const date = new Date(text);
        return isNaN(date.getTime()) ? null : date.getFullYear();
    }
    const match = /^(\d{4})/.exec(text);
    return match ? Number(match[1]) : null;
};

/**
 * useYearFilter: per-year view of a module's records. Defaults to the current year; null means all years.
 * @param records - the module's full record list
 * @param dateOf - the date field the year is taken from (e.g. leave => leave.date_of_filing)
 * @param alwaysInclude - records shown whatever year is selected (e.g. documents still in progress)
 */
export function useYearFilter<T>(
    records: Ref<T[]>,
    dateOf: (record: T) => string | null | undefined,
    alwaysInclude?: (record: T) => boolean,
) {
    const currentYear = new Date().getFullYear();
    const selectedYear = ref<number | null>(currentYear);

    /** Years that have records, plus the current year, newest first */
    const availableYears = computed(() => {
        const years = new Set<number>([currentYear]);
        records.value.forEach((record) => {
            const year = yearOf(dateOf(record));
            if (year) years.add(year);
        });
        return [...years].sort((a, b) => b - a);
    });

    /** filterByYear: returns a new array with only the selected year's records */
    const filterByYear = (list: T[]): T[] =>
        selectedYear.value === null
            ? [...list]
            : list.filter((record) => yearOf(dateOf(record)) === selectedYear.value || !!alwaysInclude?.(record));

    return { selectedYear, availableYears, filterByYear };
}
