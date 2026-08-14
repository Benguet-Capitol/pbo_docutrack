import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { TimeSlip, Employee } from './useTimeSlipsData';

export function useTimeSlipsForm(employees: any, timeSlips: any) {
    const formData = ref({
        control_no: '',
        requesting_employee_id: null as number | null,
        date: '',
        time_in_am: '',
        time_out_am: '',
        time_in_pm: '',
        time_out_pm: '',
        reason: '',
        certified_by_employee_id: null as number | null,
    });

    const formErrors = ref<Record<string, string>>({});
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const showPreviewModal = ref(false);
    const isPreviewFromTable = ref(false);

    const creating = ref(false);
    const updating = ref(false);
    const deleting = ref(false);

    const timeSlipToEdit = ref<TimeSlip | null>(null);
    const timeSlipToDelete = ref<TimeSlip | null>(null);
    const isRegeneratingControlNo = ref(false);

    const todayDate = computed(() => {
        const today = new Date();
        return today.toISOString().split('T')[0];
    });

    const sortedEmployees = computed(() => {
        return employees.value.slice().sort((a: Employee, b: Employee) => {
            const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
            const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
            return lastNameA.localeCompare(lastNameB);
        });
    });

    // Watch for date changes to regenerate control number (only for new records)
    watch(
        () => formData.value.date,
        (newDate) => {
            if (newDate && isRegeneratingControlNo.value) {
                formData.value.control_no = generateControlNo(newDate);
            }
        }
    );

    // Watch for requesting_employee_id changes (no auto-assignment for certified_by)
    watch(
        () => formData.value.requesting_employee_id,
        () => {
            // Certified By is manually selected by user
        }
    );

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'TS';

        // Find the maximum series number from all records in the same year
        let maxSeries = 0;
        timeSlips.value.forEach((slip: TimeSlip) => {
            if (slip.control_no && slip.control_no.startsWith(`${prefix}-${year}`)) {
                const parts = slip.control_no.split('-');
                if (parts.length === 4) {
                    const series = parseInt(parts[3]);
                    if (!isNaN(series) && series > maxSeries) {
                        maxSeries = series;
                    }
                }
            }
        });

        const series = String(maxSeries + 1).padStart(4, '0');
        return `${prefix}-${year}-${month}-${series}`;
    };

    const resetForm = () => {
        formData.value = {
            control_no: '',
            requesting_employee_id: null,
            date: '',
            time_in_am: '',
            time_out_am: '',
            time_in_pm: '',
            time_out_pm: '',
            reason: '',
            certified_by_employee_id: null,
        };
        formErrors.value = {};
    };

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create time slips
     */
    const canCreateTimeSlips = computed(() => {
        return ['Developer', 'Administrator', 'Administrative', 'Receiving', 'Reviewer', 'Supervisor'].includes(usertype.value);
    });

    /**
     * Check if current user can edit time slips
     * All authenticated users can edit time slips (they all have time_slips.edit permission)
     */
    const canEditTimeSlips = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    /**
     * Check if current user can delete time slips
     * Only Developer and Administrator can delete time slips
     */
    const canDeleteTimeSlips = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    const openCreateModal = () => {
        isRegeneratingControlNo.value = true;
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            control_no: generateControlNo(today),
            requesting_employee_id: null,
            date: today,
            time_in_am: '',
            time_out_am: '',
            time_in_pm: '',
            time_out_pm: '',
            reason: '',
            certified_by_employee_id: null,
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
        resetForm();
    };

    const openEditModal = (timeSlip: TimeSlip) => {
        isRegeneratingControlNo.value = false;
        timeSlipToEdit.value = timeSlip;
        formData.value = {
            control_no: timeSlip.control_no,
            requesting_employee_id: timeSlip.requesting_employee_id,
            date: timeSlip.date,
            time_in_am: timeSlip.time_in_am || '',
            time_out_am: timeSlip.time_out_am || '',
            time_in_pm: timeSlip.time_in_pm || '',
            time_out_pm: timeSlip.time_out_pm || '',
            reason: timeSlip.reason,
            certified_by_employee_id: timeSlip.certified_by_employee_id,
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        timeSlipToEdit.value = null;
        resetForm();
    };

    const openDeleteModal = (timeSlip: TimeSlip) => {
        timeSlipToDelete.value = timeSlip;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        timeSlipToDelete.value = null;
    };

    const openPreviewModal = (timeSlip?: TimeSlip) => {
        isRegeneratingControlNo.value = false;
        if (timeSlip) {
            formData.value = {
                control_no: timeSlip.control_no,
                requesting_employee_id: timeSlip.requesting_employee_id,
                date: timeSlip.date,
                time_in_am: timeSlip.time_in_am || '',
                time_out_am: timeSlip.time_out_am || '',
                time_in_pm: timeSlip.time_in_pm || '',
                time_out_pm: timeSlip.time_out_pm || '',
                reason: timeSlip.reason,
                certified_by_employee_id: timeSlip.certified_by_employee_id,
            };
        }
        showPreviewModal.value = true;
    };

    const closePreviewModal = () => {
        showPreviewModal.value = false;
    };

    const formatTimeForAPI = (timeString: string): string => {
        if (!timeString) return '';

        // If it's already in HH:MM format, return as is
        if (/^\d{2}:\d{2}$/.test(timeString)) {
            return timeString;
        }

        // If it's in H:MM format, pad the hour
        if (/^\d:\d{2}$/.test(timeString)) {
            return '0' + timeString;
        }

        // Handle other cases - try to extract HH:MM
        const match = timeString.match(/(\d{1,2}):(\d{2})/);
        if (match) {
            const hour = match[1].padStart(2, '0');
            const minute = match[2];
            return `${hour}:${minute}`;
        }

        return timeString;
    };

    const formatTimeDisplay = (timeStr: string): string => {
        if (!timeStr) return '';
        
        try {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            if (isNaN(hour) || minutes === undefined) return timeStr;

            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        } catch {
            return timeStr;
        }
    };

    const getProvincialGovernor = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
    };

    const getProvincialBudgetOfficer = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer') || null;
    };

    return {
        formData,
        formErrors,
        showCreateModal,
        showEditModal,
        showDeleteModal,
        showPreviewModal,
        isPreviewFromTable,
        creating,
        updating,
        deleting,
        timeSlipToEdit,
        timeSlipToDelete,
        todayDate,
        sortedEmployees,
        // Permissions
        canCreateTimeSlips,
        canEditTimeSlips,
        canDeleteTimeSlips,
        resetForm,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        openPreviewModal,
        closePreviewModal,
        formatTimeForAPI,
        formatTimeDisplay,
        getProvincialGovernor,
        getProvincialBudgetOfficer,
    };
}
