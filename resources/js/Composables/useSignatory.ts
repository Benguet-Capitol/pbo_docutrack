/**
 * "Approved" signatory for Pass Slips, Time Slips, Tardiness and Certificates of Appearance.
 * The signatory can be the Provincial Budget Officer, an OIC or an Acting PBO, picked from PBO employees.
 * A record with no stored signatory falls back to the Provincial Budget Officer.
 */

export const SIGNATORY_TITLES = ['Provincial Budget Officer', 'OIC, Provincial Budget Officer', 'Acting Provincial Budget Officer'] as const;
export type SignatoryTitle = typeof SIGNATORY_TITLES[number];
export const DEFAULT_SIGNATORY_TITLE: SignatoryTitle = 'Provincial Budget Officer';

const PBO_OFFICE_ID = 12;
const PBO_OFFICE_ABBREVIATION = 'PBO';

export interface SignatoryEmployee {
    id: number;
    name: string;
    designation?: string | null;
    office_id?: number | null;
    office?: { office_abbreviation?: string } | any;
}

export interface ApprovedBy {
    name: string;
    title: string;
}

/** isPboEmployee: true for employees of the Provincial Budget Office */
export const isPboEmployee = (employee: SignatoryEmployee): boolean =>
    isPboStaff(employee)
    || employee.office?.office_abbreviation === PBO_OFFICE_ABBREVIATION;

/** The Provincial Budget Officer, by exact title (see useOfficials) */
import { isProvincialBudgetOfficer, findProvincialBudgetOfficer, isPboStaff } from '@/Composables/useOfficials';
export { isProvincialBudgetOfficer, findProvincialBudgetOfficer };

/**
 * resolveApprovedBy: name and title printed under "APPROVED"
 * @param employees - employee list to look the signatory up in
 * @param employeeId - stored approved_by_employee_id (null = Provincial Budget Officer)
 * @param title - stored approved_by_title (null = Provincial Budget Officer)
 * @param defaultTitle - title printed when none is stored, for forms that print the PBO's full designation
 */
export const resolveApprovedBy = (
    employees: SignatoryEmployee[] | null | undefined,
    employeeId: number | null | undefined,
    title: string | null | undefined,
    defaultTitle: string = DEFAULT_SIGNATORY_TITLE,
): ApprovedBy => {
    const selected = employeeId ? employees?.find((emp) => emp.id === employeeId) : null;
    const employee = selected || findProvincialBudgetOfficer(employees);
    return {
        name: employee?.name || '',
        title: title || defaultTitle,
    };
};
