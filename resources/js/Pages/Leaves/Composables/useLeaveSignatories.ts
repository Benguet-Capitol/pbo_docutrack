import {
    TITLE_PBO,
    TITLE_GOVERNOR,
    TITLE_PHRMDO,
    TITLE_PA,
    TITLE_APA,
    PHRMDO_OFFICE_ID,
    isPboStaff,
    isProvincialBudgetOfficer,
    isProvincialGovernor,
    isProvincialHrmdOfficer,
    isDepartmentHead,
    hasTitle,
} from '@/Composables/useOfficials';

/**
 * CS Form No. 6 signatories: 7.A certification of leave credits (PHRMDO), 7.B recommendation (Provincial Budget
 * Officer) and the authorized official: the Provincial Governor for a department head, the Provincial
 * Administrator (or the Assistant Provincial Administrator) for other staff. Each signs as the officer, OIC or
 * Acting. Mirrors Leave::signatoryOfficers() / Leave::defaultSignatory().
 */

export type LeaveSignatoryKey = 'certified_by' | 'recommended_by' | 'approved_by';

interface SignatoryEmployee {
    id: number;
    name: string;
    designation?: string | null;
    position_title?: string | null;
    office_id?: number | null;
    detailed_to_pbo?: boolean | null;
}

const OFFICIAL_CHECKS: Record<string, (employee: SignatoryEmployee) => boolean> = {
    [TITLE_PHRMDO]: isProvincialHrmdOfficer,
    [TITLE_PBO]: isProvincialBudgetOfficer,
    [TITLE_GOVERNOR]: isProvincialGovernor,
    [TITLE_PA]: hasTitle(TITLE_PA),
    [TITLE_APA]: hasTitle(TITLE_APA),
};

export const LEAVE_SIGNATORIES: Array<{
    key: LeaveSignatoryKey;
    label: string;
    /** Officers who can sign (each also as OIC or Acting) */
    officers: string[];
    /** Who can be picked (anyone, for the authorized official) */
    isCandidate: (employee: SignatoryEmployee) => boolean;
}> = [
    {
        key: 'certified_by',
        label: '7.A Certification of Leave Credits',
        officers: [TITLE_PHRMDO],
        isCandidate: (employee) => Number(employee.office_id) === PHRMDO_OFFICE_ID,
    },
    {
        key: 'recommended_by',
        label: '7.B Recommendation',
        officers: [TITLE_PBO],
        isCandidate: isPboStaff,
    },
    {
        key: 'approved_by',
        label: 'Authorized Official (7.C / 7.D)',
        officers: [TITLE_GOVERNOR, TITLE_PA, TITLE_APA],
        isCandidate: () => true,
    },
];

const configOf = (key: LeaveSignatoryKey) => LEAVE_SIGNATORIES.find((signatory) => signatory.key === key)!;

/** Each officer's title, "OIC, <title>" and "Acting <title>" */
export const signatoryTitles = (key: LeaveSignatoryKey): string[] =>
    configOf(key).officers.flatMap((title) => [title, `OIC, ${title}`, `Acting ${title}`]);

const findOfficial = <T extends SignatoryEmployee>(employees: T[] | null | undefined, title: string): T | null =>
    (employees || []).find((employee) => OFFICIAL_CHECKS[title]?.(employee)) ?? null;

/**
 * Who signs when nothing is chosen: the officer and the title. The authorized official depends on the applicant:
 * the Provincial Governor for a department head, else the Provincial Administrator (or the Assistant when none).
 */
export const defaultSignatory = <T extends SignatoryEmployee>(
    employees: T[] | null | undefined,
    key: LeaveSignatoryKey,
    applicant: SignatoryEmployee | null | undefined,
): { employee: T | null; title: string } => {
    if (key !== 'approved_by' || isDepartmentHead(applicant)) {
        const title = configOf(key).officers[0];
        return { employee: findOfficial(employees, title), title };
    }
    const administrator = findOfficial(employees, TITLE_PA);
    return administrator
        ? { employee: administrator, title: TITLE_PA }
        : { employee: findOfficial(employees, TITLE_APA), title: TITLE_APA };
};

/** The current officer of a signatory, e.g. the Provincial Governor */
export const findSignatoryOfficial = <T extends SignatoryEmployee>(
    employees: T[] | null | undefined,
    key: LeaveSignatoryKey,
    applicant?: SignatoryEmployee | null,
): T | null => defaultSignatory(employees, key, applicant).employee;

const applicantOf = (employees: SignatoryEmployee[] | null | undefined, leave: Record<string, any> | null | undefined) =>
    (employees || []).find((emp) => emp.id === Number(leave?.employee_id)) || leave?.employee || null;

/** Printed name and title (falls back to the default signatory) */
export const resolveLeaveSignatory = (
    employees: SignatoryEmployee[] | null | undefined,
    leave: Record<string, any> | null | undefined,
    key: LeaveSignatoryKey,
): { name: string; title: string } => {
    const fallback = defaultSignatory(employees, key, applicantOf(employees, leave));
    const id = Number(leave?.[`${key}_employee_id`]) || null;
    const employee = (id && employees?.find((emp) => emp.id === id)) || fallback.employee;
    return { name: employee?.name || '', title: leave?.[`${key}_title`] || fallback.title };
};

/** Fill unset signatories with the default signatories (create, and older leaves on edit) */
export const applyDefaultSignatories = (formData: Record<string, any>, employees: SignatoryEmployee[] | null | undefined): void => {
    const applicant = applicantOf(employees, formData);
    for (const { key } of LEAVE_SIGNATORIES) {
        const fallback = defaultSignatory(employees, key, applicant);
        if (!formData[`${key}_employee_id`]) formData[`${key}_employee_id`] = fallback.employee?.id ?? null;
        if (!formData[`${key}_title`]) formData[`${key}_title`] = fallback.title;
    }
};

/**
 * When the applicant changes, the authorized official follows (Provincial Governor for a department head,
 * Provincial Administrator otherwise) unless someone else was picked by hand.
 */
export const followApplicantSignatory = (
    formData: Record<string, any>,
    employees: SignatoryEmployee[] | null | undefined,
    previousApplicantId: number | null | undefined,
): void => {
    const before = defaultSignatory(employees, 'approved_by', (employees || []).find((emp) => emp.id === Number(previousApplicantId)));
    const untouched = !formData.approved_by_employee_id
        || (formData.approved_by_employee_id === before.employee?.id && formData.approved_by_title === before.title);
    if (!untouched) return;
    const after = defaultSignatory(employees, 'approved_by', applicantOf(employees, formData));
    formData.approved_by_employee_id = after.employee?.id ?? null;
    formData.approved_by_title = after.title;
};
