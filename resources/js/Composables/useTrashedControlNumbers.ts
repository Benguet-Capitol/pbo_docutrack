import { ref } from 'vue';

/**
 * useTrashedControlNumbers: loads control numbers still held by records in the Trash,
 * so generated control numbers skip them (they remain reserved until permanently deleted).
 * @param module - Trash module key, e.g. 'pass_slips'
 */
export function useTrashedControlNumbers(module: string) {
    const trashedControlNumbers = ref<string[]>([]);

    fetch(`/api/trash/control-numbers/${module}`, { headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : []))
        .then((numbers: string[]) => {
            trashedControlNumbers.value = numbers;
        })
        .catch(() => {
            // Not critical: the server still rejects duplicate control numbers
        });

    /**
     * Highest series number among trashed control numbers with the given prefix and year
     * (format PREFIX-YYYY-MM-NNNN).
     */
    const maxTrashedSeries = (prefix: string, year: number): number =>
        trashedControlNumbers.value
            .filter((controlNo) => controlNo.startsWith(`${prefix}-${year}`))
            .reduce((max, controlNo) => Math.max(max, parseInt(controlNo.split('-').pop() ?? '', 10) || 0), 0);

    return { trashedControlNumbers, maxTrashedSeries };
}
