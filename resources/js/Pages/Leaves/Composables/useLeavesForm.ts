import { ref, watch, computed } from 'vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useTrashedControlNumbers } from '@/Composables/useTrashedControlNumbers';
import { usePage } from '@inertiajs/vue3';
import type { Leave } from './useLeavesData';
import { leaveCredit, leaveDays, formatDays } from '@/Composables/useLeaveCredits';
import { yearOf } from '@/Composables/useYearFilter';

export interface LeaveFormData {
    control_no: string;
    employee_id: number;
    date_of_filing: string;
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

export function useLeavesForm(
    leavesRef: any,
    onFetchLeaves: () => Promise<void>,
    showToastFn: (message: string, type: 'success' | 'error' | 'info') => void
) {
    const { can } = usePermissions();

    // Modal visibility state
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);

    // Loading states
    const creating = ref(false);
    const updating = ref(false);
    const deleting = ref(false);

    // Selection state
    const leaveToEdit = ref<Leave | null>(null);
    const leaveToDelete = ref<Leave | null>(null);

    // Form state
    const formData = ref<LeaveFormData>({
        control_no: '',
        employee_id: 0,
        date_of_filing: '',
        type_of_leave: 'Vacation Leave',
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

    const formErrors = ref<Record<string, string>>({});

    const leaveTypes = [
        'Vacation Leave',
        'Mandatory/Forced Leave',
        'Sick Leave',
        'Maternity Leave',
        'Paternity Leave',
        'Special Privilege Leave',
        'Study Leave',
        '10-Day VAWC Leave',
        'Rehabilitation Leave',
        'Special Leave Benefits for Women',
        'Special Emergency (Calamity) Leave',
        'Adoption Leave',
        'Wellness Leave',
        'Others'
    ];

    // Control numbers of trashed records stay reserved until permanently deleted
    const { maxTrashedSeries } = useTrashedControlNumbers('leaves');

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'L';

        const maxSeries = Math.max(
            ...leavesRef.value
                .filter((leave: Leave) => leave.control_no.startsWith(`${prefix}-${year}`))
                .map((leave: Leave) => {
                    const parts = leave.control_no.split('-');
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

    const formatDateForDisplay = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatInclusiveDate = (entry: string): string => {
        if (!entry) return '';
        if (entry.includes(' - ')) {
            const [startDate, endDate] = entry.split(' - ');
            return `${formatDateForDisplay(startDate.trim())} - ${formatDateForDisplay(endDate.trim())}`;
        } else {
            return formatDateForDisplay(entry);
        }
    };

    // ============== User Permissions ==============
    
    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create offices
     * Only Developer and Administrator can create offices
     */
    const canCreateLeaves = computed(() => can('leaves.create'));

    /**
     * Check if current user can edit offices
     * All authenticated users can edit offices (they all have offices.edit permission)
     */
    const canEditLeaves = computed(() => can('leaves.edit'));

    /**
     * Check if current user can delete offices
     * Only Developer and Administrator can delete offices
     */
    const canDeleteLeaves = computed(() => can('leaves.delete'));

    const openCreateModal = () => {
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            control_no: generateControlNo(today),
            employee_id: 0,
            date_of_filing: today,
            type_of_leave: 'Vacation Leave',
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
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
        formErrors.value = {};
    };

    const openEditModal = (leave: Leave) => {
        leaveToEdit.value = leave;
        formData.value = {
            control_no: leave.control_no,
            employee_id: leave.employee_id,
            date_of_filing: formatDateForInput(leave.date_of_filing),
            type_of_leave: leave.type_of_leave,
            number_of_working_days_applied_for: leave.number_of_working_days_applied_for,
            is_half_day: leave.is_half_day || false,
            half_day_period: leave.half_day_period || '',
            inclusive_dates: [...leave.inclusive_dates],
            off_days: [...(leave.off_days || [])],
            within_philippines: leave.within_philippines === true || (typeof leave.within_philippines === 'number' && leave.within_philippines === 1) || (typeof leave.within_philippines === 'string' && leave.within_philippines === "1"),
            purpose: leave.purpose || '',
            in_hospital: leave.in_hospital === true || (typeof leave.in_hospital === 'number' && leave.in_hospital === 1) || (typeof leave.in_hospital === 'string' && leave.in_hospital === "1"),
            illness: leave.illness || '',
            completion_type: leave.completion_type || '',
            other_type: leave.other_type || '',
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            newOffDay: '',
        };
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

    const addInclusiveDate = () => {
        if (formData.value.newInclusiveDate) {
            if (formData.value.newInclusiveDateRange) {
                const rangeString = `${formData.value.newInclusiveDate} - ${formData.value.newInclusiveDateRange}`;
                if (!formData.value.inclusive_dates.includes(rangeString)) {
                    formData.value.inclusive_dates.push(rangeString);
                }
            } else {
                if (!formData.value.inclusive_dates.includes(formData.value.newInclusiveDate)) {
                    formData.value.inclusive_dates.push(formData.value.newInclusiveDate);
                }
            }
            formData.value.newInclusiveDate = '';
            formData.value.newInclusiveDateRange = '';
        }
    };

    const removeInclusiveDate = (index: number) => {
        formData.value.inclusive_dates.splice(index, 1);
    };

    const editInclusiveDate = (index: number) => {
        const entry = formData.value.inclusive_dates[index];

        if (entry.includes(' - ')) {
            const [startDate, endDate] = entry.split(' - ');
            formData.value.newInclusiveDate = startDate.trim();
            formData.value.newInclusiveDateRange = endDate.trim();
        } else {
            formData.value.newInclusiveDate = entry;
            formData.value.newInclusiveDateRange = '';
        }

        formData.value.inclusive_dates.splice(index, 1);
    };

    const addOffDay = () => {
        if (formData.value.newOffDay && !formData.value.off_days.includes(formData.value.newOffDay)) {
            formData.value.off_days.push(formData.value.newOffDay);
            formData.value.newOffDay = '';
        }
    };

    const removeOffDay = (index: number) => {
        formData.value.off_days.splice(index, 1);
    };

    /**
     * Yearly limit status for the leave in the form (Mandatory/Forced, Special Privilege, Wellness),
     * or null when the type has no limit or no employee is chosen yet. The server enforces the same rule.
     */
    const leaveCreditInfo = computed(() => {
        const employeeId = Number(formData.value.employee_id);
        const year = yearOf(formData.value.date_of_filing) ?? new Date().getFullYear();
        if (!employeeId) return null;
        const credit = leaveCredit(leavesRef.value || [], employeeId, formData.value.type_of_leave, year, leaveToEdit.value?.id ?? null);
        if (!credit) return null;
        const requested = leaveDays(formData.value);
        return { ...credit, requested, exceeds: credit.used + requested > credit.limit };
    });

    /** Message when the leave would go over the yearly limit, or null */
    const leaveLimitError = (): string | null => {
        const info = leaveCreditInfo.value;
        if (!info || !info.exceeds) return null;
        return info.remaining <= 0
            ? `${info.type} is already exhausted for ${info.year}: all ${info.limit} days have been used.`
            : `${info.type} exceeds the yearly limit for ${info.year}: only ${formatDays(info.remaining)} of ${info.limit} days remaining, but ${formatDays(info.requested)} requested.`;
    };

    /** Required fields still empty: Create/Update stays disabled until this is empty */
    const missingLeaveFields = computed(() => {
        const missing: string[] = [];
        if (!formData.value.employee_id) missing.push('Employee');
        if (!formData.value.date_of_filing) missing.push('Date of Filing');
        if (!formData.value.type_of_leave) missing.push('Type of Leave');
        if (!formData.value.is_half_day && !(Number(formData.value.number_of_working_days_applied_for) > 0)) missing.push('Number of Working Days');
        if (formData.value.inclusive_dates.length === 0) missing.push('Inclusive Dates');
        return missing;
    });

    /** Yearly limit problem with the current form, shown next to the disabled button */
    const leaveLimitMessage = computed(() => leaveLimitError());

    /** First validation message from a failed API response */
    const responseError = (data: any, fallback: string): string =>
        (data?.errors ? (Object.values(data.errors)[0] as string[] | undefined)?.[0] : null) || data?.error || data?.message || fallback;

    const createLeave = async () => {
        try {
            creating.value = true;
            formErrors.value = {};

            if (!formData.value.employee_id) {
                formErrors.value['employee_id'] = 'Employee is required';
                return;
            }

            if (!formData.value.date_of_filing) {
                formErrors.value['date_of_filing'] = 'Date of filing is required';
                return;
            }

            if (formData.value.inclusive_dates.length === 0) {
                formErrors.value['inclusive_dates'] = 'At least one inclusive date is required';
                return;
            }

            const limitError = leaveLimitError();
            if (limitError) {
                formErrors.value['type_of_leave'] = limitError;
                return;
            }

            const response = await fetch('/api/leaves', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    control_no: formData.value.control_no,
                    employee_id: formData.value.employee_id,
                    date_of_filing: formData.value.date_of_filing,
                    type_of_leave: formData.value.type_of_leave,
                    number_of_working_days_applied_for: formData.value.number_of_working_days_applied_for,
                    is_half_day: formData.value.is_half_day,
                    half_day_period: formData.value.is_half_day ? formData.value.half_day_period : null,
                    inclusive_dates: formData.value.inclusive_dates,
                    off_days: formData.value.off_days.length > 0 ? formData.value.off_days : null,
                    within_philippines: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.within_philippines : null,
                    purpose: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.purpose : null,
                    in_hospital: formData.value.type_of_leave === 'Sick Leave' ? formData.value.in_hospital : null,
                    illness: ['Sick Leave', 'Special Leave Benefits for Women'].includes(formData.value.type_of_leave) ? formData.value.illness : null,
                    completion_type: formData.value.type_of_leave === 'Study Leave' ? formData.value.completion_type : null,
                    other_type: formData.value.type_of_leave === 'Others' ? formData.value.other_type : null,
                }),
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

            if (!leaveToEdit.value) return;

            if (!formData.value.employee_id) {
                formErrors.value['employee_id'] = 'Employee is required';
                return;
            }

            if (formData.value.inclusive_dates.length === 0) {
                formErrors.value['inclusive_dates'] = 'At least one inclusive date is required';
                return;
            }

            const limitError = leaveLimitError();
            if (limitError) {
                formErrors.value['type_of_leave'] = limitError;
                return;
            }

            const response = await fetch(`/api/leaves/${leaveToEdit.value.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    control_no: formData.value.control_no,
                    employee_id: formData.value.employee_id,
                    date_of_filing: formData.value.date_of_filing,
                    type_of_leave: formData.value.type_of_leave,
                    number_of_working_days_applied_for: formData.value.number_of_working_days_applied_for,
                    is_half_day: formData.value.is_half_day,
                    half_day_period: formData.value.is_half_day ? formData.value.half_day_period : null,
                    inclusive_dates: formData.value.inclusive_dates,
                    off_days: formData.value.off_days.length > 0 ? formData.value.off_days : null,
                    within_philippines: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.within_philippines : null,
                    purpose: ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.value.type_of_leave) ? formData.value.purpose : null,
                    in_hospital: formData.value.type_of_leave === 'Sick Leave' ? formData.value.in_hospital : null,
                    illness: ['Sick Leave', 'Special Leave Benefits for Women'].includes(formData.value.type_of_leave) ? formData.value.illness : null,
                    completion_type: formData.value.type_of_leave === 'Study Leave' ? formData.value.completion_type : null,
                    other_type: formData.value.type_of_leave === 'Others' ? formData.value.other_type : null,
                }),
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
        leaveTypes,
        leaveCreditInfo,
        missingLeaveFields,
        leaveLimitMessage,

        // Permissions
        canCreateLeaves,
        canEditLeaves,
        canDeleteLeaves,

        // Methods
        generateControlNo,
        formatDateForInput,
        formatDateForDisplay,
        formatInclusiveDate,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        addInclusiveDate,
        removeInclusiveDate,
        editInclusiveDate,
        addOffDay,
        removeOffDay,
        createLeave,
        updateLeave,
        deleteLeave,
    };
}
