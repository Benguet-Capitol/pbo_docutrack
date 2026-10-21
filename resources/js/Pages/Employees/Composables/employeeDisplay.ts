import type { Employee } from './useEmployeeData';

/** Employee status badges (same colors as pbo-registryV2) */
export const STATUS_CLASS: Record<string, string> = {
    Permanent: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200',
    Casual: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
    COS: 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200',
};

const officeOf = (employee: Employee) => (typeof employee.office === 'object' && employee.office ? employee.office : null);

export const officeAbbreviation = (employee: Employee): string =>
    officeOf(employee)?.office_abbreviation || officeOf(employee)?.office_name || '';

export const officeName = (employee: Employee): string => officeOf(employee)?.office_name || '';
