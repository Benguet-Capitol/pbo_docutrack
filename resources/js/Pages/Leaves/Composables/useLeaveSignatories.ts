import {
    TITLE_PBO,
    TITLE_GOVERNOR,
    TITLE_PHRMDO,
    TITLE_PA,
    TITLE_APA,
    PHRMDO_OFFICE_ID,
    isProvincialBudgetOfficer,
    isProvincialGovernor,
    isProvincialHrmdOfficer,
    isDepartmentHead,
    hasTitle,
} from '@/Composables/useOfficials';

/**
 * CS Form No. 6 signatories: 7.A certification of leave credits (PHRMDO), 7.B recommendation (the Provincial Budget
 * Officer by default, or the Provincial Governor, e.g. for staff who are OIC or Acting department head; only the
 * Governor for a department head) and the authorized official: the Provincial Governor for a department head, the
 * Provincial Administrator (or the Assistant Provincial Administrator) for other staff. Each signs as the officer, OIC
 * or Acting. Mirrors Leave::signatoryOfficers() / Leave::defaultSignatory().
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
        // The Provincial Budget Officer by default; the Provincial Governor too (e.g. for staff who are OIC or Acting department head)
        officers: [TITLE_PBO, TITLE_GOVERNOR],
        isCandidate: () => true,
    },
    {
        key: 'approved_by',
        label: 'Authorized Official (7.C / 7.D)',
        officers: [TITLE_GOVERNOR, TITLE_PA, TITLE_APA],
        isCandidate: () => true,
    },
];

const configOf = (key: LeaveSignatoryKey) => LEAVE_SIGNATORIES.find((signatory) => signatory.key === key)!;

/** A department head's 7.B recommendation is the Provincial Governor's (e.g. on the Provincial Budget Officer's own leave) */
const governorRecommends = (key: LeaveSignatoryKey, applicant: SignatoryEmployee | null | undefined) =>
    key === 'recommended_by' && isDepartmentHead(applicant);

/** Officers who can sign for this applicant. Mirrors Leave::signatoryOfficers() */
export const signatoryOfficers = (key: LeaveSignatoryKey, applicant?: SignatoryEmployee | null): string[] =>
    governorRecommends(key, applicant) ? [TITLE_GOVERNOR] : configOf(key).officers;

/** Each officer's title, "OIC, <title>" and "Acting <title>" */
export const signatoryTitles = (key: LeaveSignatoryKey, applicant?: SignatoryEmployee | null): string[] =>
    signatoryOfficers(key, applicant).flatMap((title) => [title, `OIC, ${title}`, `Acting ${title}`]);

/** Who can be picked (the Provincial Governor or an OIC / Acting can sign 7.B too, so anyone there) */
export const isSignatoryCandidate = (key: LeaveSignatoryKey, employee: SignatoryEmployee, _applicant?: SignatoryEmployee | null): boolean =>
    configOf(key).isCandidate(employee);

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
        // The Provincial Governor recommends and approves a department head's leave
        const title = signatoryOfficers(key, applicant)[0];
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

/** Stands in for an OIC / Acting department head: signed as a department head's leave (the Provincial Governor's) */
const ACTING_HEAD = { id: 0, name: '', position_title: 'Department Head' };

/** An OIC / Acting department head's leave: not a department head, but 7.B is the Provincial Governor's */
export const isActingHeadLeave = (formData: Record<string, any>, applicant: SignatoryEmployee | null | undefined): boolean =>
    !isDepartmentHead(applicant) && String(formData?.recommended_by_title ?? '').includes(TITLE_GOVERNOR);

/**
 * The "OIC / Acting department head" box: ticked, the Provincial Governor recommends (7.B) and approves, as for a
 * department head; unticked, back to the applicant's own defaults (Provincial Budget Officer, Provincial Administrator).
 */
export const applyActingHeadSignatories = (
    formData: Record<string, any>,
    employees: SignatoryEmployee[] | null | undefined,
    applicant: SignatoryEmployee | null | undefined,
    acting: boolean,
): void => {
    for (const key of ['recommended_by', 'approved_by'] as const) {
        const signer = defaultSignatory(employees, key, acting ? ACTING_HEAD : applicant);
        formData[`${key}_employee_id`] = signer.employee?.id ?? null;
        formData[`${key}_title`] = signer.title;
    }
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
    const previous = (employees || []).find((emp) => emp.id === Number(previousApplicantId));
    // The recommendation follows too: the Provincial Governor's for a department head, else the Provincial Budget Officer's
    for (const key of ['recommended_by', 'approved_by'] as const) {
        const before = defaultSignatory(employees, key, previous);
        const untouched = !formData[`${key}_employee_id`]
            || (formData[`${key}_employee_id`] === before.employee?.id && formData[`${key}_title`] === before.title);
        if (!untouched) continue;
        const after = defaultSignatory(employees, key, applicantOf(employees, formData));
        formData[`${key}_employee_id`] = after.employee?.id ?? null;
        formData[`${key}_title`] = after.title;
    }
};
