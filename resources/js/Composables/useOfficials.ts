/**
 * Who the Provincial Budget Officer and the Provincial Governor are, by exact title.
 * Employees come from HRIS (every provincial employee), so text matching like "includes('budget officer')" would also
 * hit e.g. an "Assistant Provincial Budget Officer" or a "Budget Officer IV". Mirrors App\Models\Employee.
 */

export const PBO_OFFICE_ID = 12;
export const TITLE_PBO = 'Provincial Budget Officer';
export const TITLE_GOVERNOR = 'Provincial Governor';
export const PHRMDO_OFFICE_ID = 14;
export const TITLE_PHRMDO = 'Provincial Human Resource Management and Development Officer';
export const TITLE_PA = 'Provincial Administrator';
export const TITLE_APA = 'Assistant Provincial Administrator';

/** A department head (not an assistant department head), e.g. "Provincial Government Department Head". Mirrors Employee::isDepartmentHead() */
export const isDepartmentHead = (employee: { position_title?: string | null } | null | undefined): boolean => {
    const position = String(employee?.position_title ?? '').toLowerCase();
    return position.includes('department head') && !position.includes('assistant');
};

/** Holder of an exact printed title, e.g. the Provincial Administrator */
export const hasTitle = (title: string) => (employee: OfficialLike | null | undefined): boolean =>
    !!employee && !employee.separated && String(employee.designation ?? '').trim() === title;

interface OfficialLike {
    designation?: string | null;
    parenthetical_title?: string | null;
    office_id?: number | string | null;
    /** Another office's employee working at the PBO (set on the employee; the HRIS sync keeps it) */
    detailed_to_pbo?: boolean | number | null;
    /** No longer in HRIS (resigned, retired...): kept on old records, never offered in pickers */
    separated?: boolean | null;
}

/** Provincial Budget Office staff (still with the Province), including employees of other offices detailed to it */
export const isPboStaff = (employee: OfficialLike | null | undefined): boolean =>
    !employee?.separated && (Number(employee?.office_id) === PBO_OFFICE_ID || !!employee?.detailed_to_pbo);

/** The Provincial Budget Officer: that exact title, in the Provincial Budget Office (when the office is known) */
export const isProvincialBudgetOfficer = (employee: OfficialLike | null | undefined): boolean =>
    !!employee
    // The department head whose parenthetical title is "Provincial Budget Officer" (also printed as their title)
    && (String(employee.parenthetical_title ?? '').trim() === TITLE_PBO || String(employee.designation ?? '').trim() === TITLE_PBO)
    && (employee.office_id === undefined || employee.office_id === null || isPboStaff(employee));

/** The Provincial Governor: that exact title */
export const isProvincialGovernor = (employee: OfficialLike | null | undefined): boolean =>
    !!employee && !employee.separated && String(employee.designation ?? '').trim() === TITLE_GOVERNOR;

/** The Provincial Human Resource Management and Development Officer: that exact title, in the PHRMDO (when known) */
export const isProvincialHrmdOfficer = (employee: OfficialLike | null | undefined): boolean =>
    !!employee
    && !employee.separated
    && String(employee.designation ?? '').trim() === TITLE_PHRMDO
    && (employee.office_id === undefined || employee.office_id === null || Number(employee.office_id) === PHRMDO_OFFICE_ID);

/** Who the PBO module dropdowns offer: PBO staff and the Governor (the employee lists also carry people old records name) */
export const inPboScope = (employee: OfficialLike | null | undefined): boolean =>
    isPboStaff(employee) || isProvincialGovernor(employee);

export const findProvincialBudgetOfficer = <T extends OfficialLike>(employees: T[] | null | undefined): T | null =>
    employees?.find(isProvincialBudgetOfficer) ?? null;

export const findProvincialGovernor = <T extends OfficialLike>(employees: T[] | null | undefined): T | null =>
    employees?.find(isProvincialGovernor) ?? null;
