import { ref, watch, computed } from 'vue';
import { usePermissions } from '@/Composables/usePermissions';
import { usePage } from '@inertiajs/vue3';
import { useTrashedControlNumbers } from '@/Composables/useTrashedControlNumbers';
import type { Leave } from './useLeavesData';
import { leaveCredit, leaveDays, formatDays } from '@/Composables/useLeaveCredits';
import { yearOf } from '@/Composables/useYearFilter';
import { leaveWorkingDates, longDate } from './useLeaveFormPrint';

/** Leave types whose 6.B location details (within the Philippines / abroad, purpose) apply */
export const LOCATION_TYPES = ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'];

/** One leave type in an application, with its own dates and details */
export interface LeaveEntry {
    /** Saved row id (edit) */
    id?: number | null;
    type_of_leave: string;
    number_of_working_days_applied_for: number;
    is_half_day: boolean;
    half_day_period: string;
    inclusive_dates: string[];
    off_days: string[];
    within_philippines: boolean;
    purpose: string;
    in_hospital: boolean;
    illness: string;
    completion_type: string;
    other_type: string;
    newInclusiveDate?: string;
    newInclusiveDateRange?: string;
    newOffDay?: string;
}

/** A leave application: shared fields plus one entry per leave type */
export interface LeaveFormData {
    control_no: string;
    employee_id: number;
    date_of_filing: string;
    /** Monthly salary as of filing (5. SALARY), filled from HRIS; "" when unknown */
    salary: string | number;
    entries: LeaveEntry[];
    certified_by_employee_id: number | null;
    certified_by_title: string | null;
    recommended_by_employee_id: number | null;
    recommended_by_title: string | null;
    approved_by_employee_id: number | null;
    approved_by_title: string | null;
    /** 7.B Recommendation: "" (pending), "approval" or "disapproval" */
    recommendation: '' | 'approval' | 'disapproval';
    disapproval_reason: string;
}

export const newLeaveEntry = (type = 'Vacation Leave'): LeaveEntry => ({
    id: null,
    type_of_leave: type,
    number_of_working_days_applied_for: 1,
    is_half_day: false,
    half_day_period: '',
    inclusive_dates: [],
    off_days: [],
    within_philippines: true,
    purpose: '',
    in_hospital: false,
    illness: '',
    completion_type: '',
    other_type: '',
    newInclusiveDate: '',
    newInclusiveDateRange: '',
    newOffDay: '',
});

/** Saved rows send flags as 1/0/"1"/true */
const isTrue = (value: unknown): boolean => value === true || value === 1 || value === '1';

const entryFromRow = (row: Leave): LeaveEntry => ({
    id: row.id,
    type_of_leave: row.type_of_leave,
    number_of_working_days_applied_for: row.number_of_working_days_applied_for,
    is_half_day: !!row.is_half_day,
    half_day_period: row.half_day_period || '',
    inclusive_dates: [...(row.inclusive_dates || [])],
    off_days: [...(row.off_days || [])],
    within_philippines: row.within_philippines === null ? true : isTrue(row.within_philippines),
    purpose: row.purpose || '',
    in_hospital: isTrue(row.in_hospital),
    illness: row.illness || '',
    completion_type: row.completion_type || '',
    other_type: row.other_type || '',
    newInclusiveDate: '',
    newInclusiveDateRange: '',
    newOffDay: '',
});

/** Request body for one entry: only the details that apply to its type */
const entryPayload = (entry: LeaveEntry) => {
    const type = entry.type_of_leave;
    const location = LOCATION_TYPES.includes(type);
    return {
        id: entry.id || null,
        type_of_leave: type,
        number_of_working_days_applied_for: entry.number_of_working_days_applied_for,
        is_half_day: entry.is_half_day,
        half_day_period: entry.is_half_day ? entry.half_day_period : null,
        inclusive_dates: entry.inclusive_dates,
        off_days: entry.off_days.length > 0 ? entry.off_days : null,
        within_philippines: location ? entry.within_philippines : null,
        purpose: location ? entry.purpose : null,
        in_hospital: type === 'Sick Leave' ? entry.in_hospital : null,
        illness: ['Sick Leave', 'Special Leave Benefits for Women'].includes(type) ? entry.illness : null,
        completion_type: type === 'Study Leave' ? entry.completion_type : null,
        other_type: type === 'Others' ? entry.other_type : null,
    };
};

const SIGNATORY_KEYS = ['certified_by', 'recommended_by', 'approved_by'] as const;

export function useLeavesForm(
    leavesRef: any,
    onFetchLeaves: () => Promise<void>,
    showToastFn: (message: string, type: 'success' | 'error' | 'info') => void
) {
    const { can } = usePermissions();

    /** Sees and files leaves for every employee; otherwise only for the employee linked to the account */
    const canViewAllLeaves = computed(() => can('leaves.view_all'));
    const page = usePage();
    const ownEmployeeId = computed<number | null>(() => Number((page.props.auth as any)?.user?.employee_id) || null);

    // Control numbers already used (live and trashed), so the next number is free even when only own leaves are listed
    const usedControlNumbers = ref<string[]>([]);
    const loadUsedControlNumbers = async () => {
        try {
            const response = await fetch('/api/leaves/control-numbers', { headers: { Accept: 'application/json' } });
            if (response.ok) usedControlNumbers.value = await response.json();
        } catch {
            // Not critical: the server still rejects a used control number
        }
    };
    if (can('leaves.create') || can('leaves.edit')) loadUsedControlNumbers();

    // Modal visibility state
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);

    // Loading states
    const creating = ref(false);
    const updating = ref(false);
    const deleting = ref(false);

    // Selection state: the application being edited / deleted
    const leaveToEdit = ref<Leave | null>(null);
    const leaveToDelete = ref<Leave | null>(null);

    const blankForm = (): LeaveFormData => ({
        control_no: '',
        employee_id: 0,
        date_of_filing: '',
        salary: '',
        entries: [newLeaveEntry()],
        certified_by_employee_id: null,
        certified_by_title: null,
        recommended_by_employee_id: null,
        recommended_by_title: null,
        approved_by_employee_id: null,
        approved_by_title: null,
        recommendation: '',
        disapproval_reason: '',
    });

    const formData = ref<LeaveFormData>(blankForm());
    const formErrors = ref<Record<string, string>>({});

    // Control numbers of trashed records stay reserved until permanently deleted
    const { maxTrashedSeries } = useTrashedControlNumbers('leaves');

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'L';

        const maxSeries = Math.max(
            ...[...leavesRef.value.map((leave: Leave) => leave.control_no), ...usedControlNumbers.value]
                .filter((controlNo: string) => controlNo?.startsWith(`${prefix}-${year}`))
                .map((controlNo: string) => {
                    const parts = controlNo.split('-');
                    return parseInt(parts[parts.length - 1], 10) || 0;
                }),
            0
        );

        const series = String(Math.max(maxSeries, maxTrashedSeries(prefix, year)) + 1).padStart(4, '0');
        return `${prefix}-${year}-${month}-${series}`;
    };

    const formatDateForInput = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // ============== User Permissions ==============
    const canCreateLeaves = computed(() => can('leaves.create'));
    const canEditLeaves = computed(() => can('leaves.edit'));
    const canDeleteLeaves = computed(() => can('leaves.delete'));

    const openCreateModal = () => {
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            ...blankForm(),
            control_no: generateControlNo(today),
            date_of_filing: today,
            // Users who see only their own leaves file only for themselves
            employee_id: canViewAllLeaves.value ? 0 : ownEmployeeId.value || 0,
        };
        // Refresh the used numbers, then the proposed number (unless the date was changed meanwhile)
        loadUsedControlNumbers().then(() => {
            if (showCreateModal.value && formData.value.date_of_filing === today) formData.value.control_no = generateControlNo(today);
        });
        leaveToEdit.value = null;
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
        formErrors.value = {};
    };

    /** Edit an application (a grouped leave, see groupApplications) */
    const openEditModal = (leave: Leave) => {
        leaveToEdit.value = leave;
        const rows = leave.entries?.length ? leave.entries : [leave];
        const form: LeaveFormData = {
            ...blankForm(),
            control_no: leave.control_no,
            employee_id: leave.employee_id,
            date_of_filing: formatDateForInput(leave.date_of_filing),
            salary: leave.salary ?? '',
            entries: rows.map(entryFromRow),
            recommendation: (leave.recommendation as LeaveFormData['recommendation']) || '',
            disapproval_reason: leave.disapproval_reason || '',
        };
        for (const key of SIGNATORY_KEYS) {
            (form as any)[`${key}_employee_id`] = (leave as any)[`${key}_employee_id`] ?? null;
            (form as any)[`${key}_title`] = (leave as any)[`${key}_title`] ?? null;
        }
        formData.value = form;
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        leaveToEdit.value = null;
        formErrors.value = {};
    };

    const openDeleteModal = (leave: Leave) => {
        leaveToDelete.value = leave;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        leaveToDelete.value = null;
    };

    // Watch for date changes to regenerate control number (only in create modal)
    watch(() => formData.value.date_of_filing, (newDate) => {
        if (showCreateModal.value && newDate) {
            formData.value.control_no = generateControlNo(newDate);
        }
    });

    /** Rows of the application being edited: not counted twice against the yearly limits */
    const editedRowIds = computed(() => {
        const leave = leaveToEdit.value;
        return new Set((leave?.entries?.length ? leave.entries : leave ? [leave] : []).map((row) => row.id));
    });

    /**
     * Yearly limit status per entry (Mandatory/Forced, Special Privilege, Wellness), or null when the type has no
     * limit or no employee is chosen yet. The server enforces the same rule.
     */
    const leaveCreditInfo = computed(() => {
        const employeeId = Number(formData.value.employee_id);
        const year = yearOf(formData.value.date_of_filing) ?? new Date().getFullYear();
        const others = (leavesRef.value || []).filter((leave: Leave) => !editedRowIds.value.has(leave.id));
        return formData.value.entries.map((entry) => {
            if (!employeeId) return null;
            const credit = leaveCredit(others, employeeId, entry.type_of_leave, year, null);
            if (!credit) return null;
            const requested = leaveDays(entry);
            return { ...credit, requested, exceeds: credit.used + requested > credit.limit };
        });
    });

    /** Message when an entry would go over its yearly limit, or null */
    const leaveLimitError = (): string | null => {
        const info = leaveCreditInfo.value.find((credit) => credit?.exceeds);
        if (!info) return null;
        return info.remaining <= 0
            ? `${info.type} is already exhausted for ${info.year}: all ${info.limit} days have been used.`
            : `${info.type} exceeds the yearly limit for ${info.year}: only ${formatDays(info.remaining)} of ${info.limit} days remaining, but ${formatDays(info.requested)} requested.`;
    };

    /** Required fields still empty: Create/Update stays disabled until this is empty */
    const missingLeaveFields = computed(() => {
        const missing: string[] = [];
        const entries = formData.value.entries;
        const several = entries.length > 1;
        if (!formData.value.employee_id) missing.push('Employee');
        if (!formData.value.date_of_filing) missing.push('Date of Filing');
        if (entries.length === 0) missing.push('Type of Leave');
        entries.forEach((entry) => {
            const label = several ? ` (${entry.type_of_leave || 'leave type'})` : '';
            if (!entry.type_of_leave) missing.push('Type of Leave');
            if (!entry.is_half_day && !(Number(entry.number_of_working_days_applied_for) > 0)) missing.push(`Number of Working Days${label}`);
            if (entry.inclusive_dates.length === 0) missing.push(`Inclusive Dates${label}`);
            if (entry.is_half_day && !entry.half_day_period) missing.push(`Half Day Period (AM/PM)${label}`);
            if (entry.type_of_leave === 'Others' && !entry.other_type) missing.push('Others type (Monetization / Terminal Leave)');
        });
        if (formData.value.recommendation === 'disapproval' && !formData.value.disapproval_reason.trim()) missing.push('Reason for Disapproval');
        const types = entries.map((entry) => entry.type_of_leave);
        if (new Set(types).size !== types.length) missing.push('a different type for each entry');
        return missing;
    });

    /**
     * A day filed twice: by two entries of this application, or by another leave of the employee (disapproved ones
     * aside). Half days on different halves (AM / PM) of the same day are fine. The server checks the same.
     */
    const overlapError = computed((): string | null => {
        const employeeId = Number(formData.value.employee_id);
        if (!employeeId) return null;
        const taken = new Map<string, Array<{ where: string; half: string | null }>>();
        const halfOf = (leave: { is_half_day?: boolean; half_day_period?: string | null }) => (leave.is_half_day ? leave.half_day_period || null : null);
        const clash = (a: string | null, b: string | null) => !(a && b && a !== b);
        for (const leave of (leavesRef.value || []) as Leave[]) {
            if (leave.employee_id !== employeeId || editedRowIds.value.has(leave.id) || leave.recommendation === 'disapproval') continue;
            for (const date of leaveWorkingDates(leave.inclusive_dates, leave.off_days)) {
                taken.set(date, [...(taken.get(date) || []), { where: `${leave.type_of_leave} (${leave.control_no})`, half: halfOf(leave) }]);
            }
        }
        for (const entry of formData.value.entries) {
            const half = halfOf(entry);
            for (const date of leaveWorkingDates(entry.inclusive_dates, entry.off_days)) {
                const other = (taken.get(date) || []).find((item) => clash(half, item.half));
                if (other) return `${entry.type_of_leave}: already on leave on ${longDate(date)}, filed as ${other.where}.`;
                taken.set(date, [...(taken.get(date) || []), { where: `${entry.type_of_leave} in this application`, half }]);
            }
        }
        return null;
    });

    /** Yearly limit or overlapping dates problem with the current form, shown next to the disabled button */
    const leaveLimitMessage = computed(() => overlapError.value || leaveLimitError());

    /** First validation message from a failed API response */
    const responseError = (data: any, fallback: string): string =>
        (data?.errors ? (Object.values(data.errors)[0] as string[] | undefined)?.[0] : null) || data?.error || data?.message || fallback;

    const requestBody = () => {
        const body: Record<string, unknown> = {
            control_no: formData.value.control_no,
            employee_id: formData.value.employee_id,
            date_of_filing: formData.value.date_of_filing,
            salary: formData.value.salary === '' || formData.value.salary === null ? null : Number(formData.value.salary),
            entries: formData.value.entries.map(entryPayload),
            recommendation: formData.value.recommendation || null,
            disapproval_reason: formData.value.recommendation === 'disapproval' ? formData.value.disapproval_reason : null,
        };
        for (const key of SIGNATORY_KEYS) {
            body[`${key}_employee_id`] = (formData.value as any)[`${key}_employee_id`] || null;
            body[`${key}_title`] = (formData.value as any)[`${key}_title`] || null;
        }
        return JSON.stringify(body);
    };

    /** Client-side checks before saving; false (with formErrors set) when something is missing */
    const readyToSave = (): boolean => {
        if (!formData.value.employee_id) {
            formErrors.value['employee_id'] = 'Employee is required';
            return false;
        }
        if (!formData.value.date_of_filing) {
            formErrors.value['date_of_filing'] = 'Date of filing is required';
            return false;
        }
        if (formData.value.entries.some((entry) => entry.inclusive_dates.length === 0)) {
            formErrors.value['inclusive_dates'] = 'Each leave type needs at least one inclusive date';
            return false;
        }
        const limitError = overlapError.value || leaveLimitError();
        if (limitError) {
            formErrors.value['type_of_leave'] = limitError;
            return false;
        }
        return true;
    };

    const createLeave = async () => {
        try {
            creating.value = true;
            formErrors.value = {};
            if (!readyToSave()) return;

            const response = await fetch('/api/leaves', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: requestBody(),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(responseError(errorData, 'Failed to create leave'));
            }

            const newLeave = await response.json();
            await onFetchLeaves();
            closeCreateModal();
            showToastFn(`Leave ${newLeave.data.control_no} has been created successfully!`, 'success');
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            showToastFn(errorMsg, 'error');
        } finally {
            creating.value = false;
        }
    };

    const updateLeave = async () => {
        try {
            updating.value = true;
            formErrors.value = {};
            if (!leaveToEdit.value || !readyToSave()) return;

            const response = await fetch(`/api/leaves/${leaveToEdit.value.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: requestBody(),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(responseError(errorData, 'Failed to update leave'));
            }

            const updatedLeave = await response.json();
            await onFetchLeaves();
            closeEditModal();
            showToastFn(`Leave ${updatedLeave.data.control_no} has been updated successfully!`, 'success');
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            showToastFn(errorMsg, 'error');
        } finally {
            updating.value = false;
        }
    };

    /** Deletes the whole application (every leave type in it) */
    const deleteLeave = async () => {
        try {
            deleting.value = true;

            if (!leaveToDelete.value) return;

            const response = await fetch(`/api/leaves/${leaveToDelete.value.id}`, {
                method: 'DELETE',
            });

            if (!response.ok) {
                throw new Error('Failed to delete leave');
            }

            const controlNo = leaveToDelete.value.control_no;
            await onFetchLeaves();
            closeDeleteModal();
            showToastFn(`Leave ${controlNo} has been deleted successfully!`, 'error');
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            showToastFn(errorMsg, 'error');
        } finally {
            deleting.value = false;
        }
    };

    return {
        // State
        showCreateModal,
        showEditModal,
        showDeleteModal,
        creating,
        updating,
        deleting,
        leaveToEdit,
        leaveToDelete,
        formData,
        formErrors,
        leaveCreditInfo,
        missingLeaveFields,
        leaveLimitMessage,

        // Permissions
        canCreateLeaves,
        canViewAllLeaves,
        ownEmployeeId,
        canEditLeaves,
        canDeleteLeaves,

        // Methods
        generateControlNo,
        formatDateForInput,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        createLeave,
        updateLeave,
        deleteLeave,
    };
}
