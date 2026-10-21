/**
 * Who the Provincial Budget Officer and the Provincial Governor are, by exact title.
 * Employees come from HRIS (every provincial employee), so text matching like "includes('budget officer')" would also
 * hit e.g. an "Assistant Provincial Budget Officer" or a "Budget Officer IV". Mirrors App\Models\Employee.
 */

export const PBO_OFFICE_ID = 12;
export const TITLE_PBO = 'Provincial Budget Officer';
export const TITLE_GOVERNOR = 'Provincial Governor';

interface OfficialLike {
    designation?: string | null;
    office_id?: number | string | null;
    /** Another office's employee working at the PBO (set on the employee; the HRIS sync keeps it) */
    detailed_to_pbo?: boolean | number | null;
}

/** Provincial Budget Office staff, including employees of other offices detailed to it */
export const isPboStaff = (employee: OfficialLike | null | undefined): boolean =>
    Number(employee?.office_id) === PBO_OFFICE_ID || !!employee?.detailed_to_pbo;

/** The Provincial Budget Officer: that exact title, in the Provincial Budget Office (when the office is known) */
export const isProvincialBudgetOfficer = (employee: OfficialLike | null | undefined): boolean =>
    !!employee
    && String(employee.designation ?? '').trim() === TITLE_PBO
    && (employee.office_id === undefined || employee.office_id === null || isPboStaff(employee));

/** The Provincial Governor: that exact title */
export const isProvincialGovernor = (employee: OfficialLike | null | undefined): boolean =>
    !!employee && String(employee.designation ?? '').trim() === TITLE_GOVERNOR;

/** Who the PBO module dropdowns offer: PBO staff and the Governor (the employee lists also carry people old records name) */
export const inPboScope = (employee: OfficialLike | null | undefined): boolean =>
    isPboStaff(employee) || isProvincialGovernor(employee);

export const findProvincialBudgetOfficer = <T extends OfficialLike>(employees: T[] | null | undefined): T | null =>
    employees?.find(isProvincialBudgetOfficer) ?? null;

export const findProvincialGovernor = <T extends OfficialLike>(employees: T[] | null | undefined): T | null =>
    employees?.find(isProvincialGovernor) ?? null;
