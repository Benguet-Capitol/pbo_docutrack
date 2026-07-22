import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { TardinessRecord, Employee } from './useTardinessData';

export function useTardinessForm(employees: any, tardiness: any) {
    const formData = ref({
        control_no: '',
        date_filed: '',
        type: 'Undertime',
        requested_date: '',
        employee_id: 0,
        requested_time: '',
        reason: '',
        return_time: '',
        returnType: 'time' as 'time' | 'nwd',
        supervisor_employee_id: null as number | null,
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

    const recordToEdit = ref<TardinessRecord | null>(null);
    const recordToDelete = ref<TardinessRecord | null>(null);
    const isRegeneratingControlNo = ref(false);

    const todayDate = computed(() => {
        const today = new Date();
        return today.toISOString().split('T')[0];
    });

    // Watch for returnType changes to auto-set return_time for NWD
    watch(
        () => formData.value.returnType,
        (newType) => {
            if (newType === 'nwd') {
                formData.value.return_time = '17:00:00'; // 5:00 PM
            }
        }
    );

    // Watch for date_filed changes to regenerate control number based on its month (only for new records)
    watch(
        () => formData.value.date_filed,
        (newDate) => {
            if (newDate && isRegeneratingControlNo.value) {
                formData.value.control_no = generateControlNo(newDate);
            }
        }
    );

    // Watch for employee_id changes to handle Provincial Budget Officer approval
    watch(
        () => formData.value.employee_id,
        (newEmployeeId) => {
            if (newEmployeeId) {
                const emp = employees.value.find((e: Employee) => e.id === newEmployeeId);
                if (emp?.designation === 'Provincial Budget Officer') {
                    // If requesting employee is PBO, auto-set approver to Provincial Governor
                    const pgov = getProvincialGovernor();
                    if (pgov) {
                        formData.value.supervisor_employee_id = pgov.id;
                    }
                }
            }
        }
    );

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'TU';

        // Find the maximum series number from all records in the same year
        let maxSeries = 0;
        tardiness.value.forEach((record: TardinessRecord) => {
            if (record.control_no && record.control_no.startsWith(`${prefix}-${year}`)) {
                // Extract the last 4 digits (the series number)
                const parts = record.control_no.split('-');
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

    const computeUndertime = (): string => {
        // Return empty if times are not provided
        if (!formData.value.requested_time || !formData.value.return_time) {
            return '';
        }

        try {
            // Parse requested_time (when employee wants to leave)
            const [startHour, startMin] = formData.value.requested_time.split(':').map(Number);
            // Parse return_time (when employee returns)
            // For NWD, return_time is automatically set to 17:00:00 (5:00 PM)
            const [endHour, endMin] = formData.value.return_time.split(':').map(Number);

            // Convert to total minutes since midnight
            const requestedTotalMin = startHour * 60 + startMin;
            const returnTotalMin = endHour * 60 + endMin;

            // Check if time span crosses midnight (for lunch calculation)
            const isDayWrap = returnTotalMin < requestedTotalMin;

            // Calculate difference: return_time - requested_time
            let diffMin = returnTotalMin - requestedTotalMin;

            // Handle case where it spans to next day (e.g., 11:00 PM to 1:00 AM)
            if (diffMin < 0) {
                diffMin += 24 * 60;
            }

            // Exclude lunch break (12:00 PM to 1:00 PM)
            const LUNCH_START = 12 * 60; // 720 minutes
            const LUNCH_END = 13 * 60; // 780 minutes
            const LUNCH_DURATION = 60; // 1 hour

            let shouldSubtractLunch = false;

            if (isDayWrap) {
                // Spans midnight - lunch period is always included
                shouldSubtractLunch = true;
            } else {
                // Same day - check if lunch period falls within the time span
                if (requestedTotalMin < LUNCH_START && returnTotalMin > LUNCH_END) {
                    shouldSubtractLunch = true;
                }
            }

            if (shouldSubtractLunch) {
                diffMin -= LUNCH_DURATION;
            }

            // Convert total minutes back to hours and minutes
            const hours = Math.floor(diffMin / 60);
            const minutes = diffMin % 60;

            // Format the result
            if (hours === 0) {
                return minutes > 0 ? `${minutes} mins` : '0 mins';
            } else if (minutes === 0) {
                return `${hours} hrs`;
            } else {
                return `${hours} hrs and ${minutes} mins`;
            }
        } catch (e) {
            console.error('Error computing undertime:', e);
            return '';
        }
    };

    const getRequestingEmployee = (): Employee | null => {
        if (!formData.value.employee_id) return null;
        return employees.value.find((emp: Employee) => emp.id === formData.value.employee_id) || null;
    };

    const getSupervisor = (): Employee | null => {
        if (!formData.value.supervisor_employee_id) return null;
        return employees.value.find((emp: Employee) => emp.id === formData.value.supervisor_employee_id) || null;
    };

    const getProvincialBudgetOfficer = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer') || null;
    };

    const getProvincialGovernor = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
    };

    const isRequestingEmployeeProvincialBudgetOfficer = (): boolean => {
        const emp = getRequestingEmployee();
        return emp?.designation === 'Provincial Budget Officer';
    };

    const getEmployeeOffice = (): string => {
        const emp = getRequestingEmployee();
        return emp?.office?.name || 'Provincial Budget Office';
    };

    const formatDateForInput = (dateStr: string | null | undefined): string => {
        if (!dateStr) return '';
        // Use local date methods to avoid timezone conversion issues
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const formatTimeForInput = (timeStr: string | null | undefined): string => {
        if (!timeStr) return '';
        // Extract HH:mm from time string (removes seconds if present)
        return timeStr.substring(0, 5);
    };

    const formatDate = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatTime = (timeStr: string): string => {
        if (!timeStr) return '';
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    };

    const formatTimeDisplay = (timeStr: string): string => {
        if (!timeStr) return '';
        
        // If it doesn't contain a colon, it's likely a special value
        if (!timeStr.includes(':')) {
            return timeStr;
        }
        
        try {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            if (isNaN(hour) || minutes === undefined) return timeStr;
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        } catch (e) {
            return timeStr;
        }
    };

    const validateForm = (): boolean => {
        formErrors.value = {};

        if (!formData.value.date_filed.trim()) {
            formErrors.value['date_filed'] = 'Date filed is required';
        }

        if (!formData.value.type) {
            formErrors.value['type'] = 'Type is required';
        }

        if (!formData.value.requested_date.trim()) {
            formErrors.value['requested_date'] = 'Requested date is required';
        }

        if (!formData.value.employee_id) {
            formErrors.value['employee_id'] = 'Employee is required';
        }

        if (!formData.value.requested_time.trim()) {
            formErrors.value['requested_time'] = 'Requested time is required';
        }

        if (!formData.value.reason.trim()) {
            formErrors.value['reason'] = 'Reason is required';
        }

        return Object.keys(formErrors.value).length === 0;
    };

    // ============== User Permissions ==============
    
        const page = usePage();
        const usertype = computed(() => page.props.auth.user?.usertype || '');
    
        /**
         * Check if current user can create tardiness
         * Only Developer and Administrator can create tardiness
         */
        const canCreateTardiness = computed(() => {
            return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
        });
    
        /**
         * Check if current user can edit tardiness
         * All authenticated users can edit tardiness (they all have tardiness.edit permission)
         */
        const canEditTardiness = computed(() => {
            return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
        });
    
        /**
         * Check if current user can delete tardiness
         * Only Developer and Administrator can delete tardiness
         */
        const canDeleteTardiness = computed(() => {
            return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
        });

    const openCreateModal = () => {
        isRegeneratingControlNo.value = true;
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            control_no: '',
            date_filed: today,
            type: 'Undertime',
            requested_date: '',
            employee_id: 0,
            requested_time: '',
            reason: '',
            return_time: '',
            returnType: 'time',
            supervisor_employee_id: null,
        };
        // Generate control number based on date_filed
        formData.value.control_no = generateControlNo(today);
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    const openEditModal = (record: TardinessRecord) => {
        isRegeneratingControlNo.value = false;
        recordToEdit.value = record;

        formData.value = {
            control_no: record.control_no,
            date_filed: formatDateForInput(record.date_filed),
            type: record.type,
            requested_date: formatDateForInput(record.requested_date),
            employee_id: record.employee_id,
            requested_time: formatTimeForInput(record.requested_time),
            reason: record.reason,
            return_time: record.return_time || '',
            returnType: (record.return_time === 'NWD' || record.return_time === '17:00:00') ? 'nwd' : 'time',
            supervisor_employee_id: record.supervisor_employee_id || null,
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        recordToEdit.value = null;
    };

    const openDeleteModal = (record: TardinessRecord) => {
        recordToDelete.value = record;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        recordToDelete.value = null;
    };

    const openPreviewModal = (record?: TardinessRecord) => {
        isRegeneratingControlNo.value = false;
        if (record) {
            formData.value = {
                control_no: record.control_no,
                date_filed: formatDateForInput(record.date_filed),
                type: record.type,
                requested_date: formatDateForInput(record.requested_date),
                employee_id: record.employee_id,
                requested_time: formatTimeForInput(record.requested_time),
                reason: record.reason,
                return_time: record.return_time || '',
                returnType: (record.return_time === 'NWD' || record.return_time === '17:00:00') ? 'nwd' : 'time',
                supervisor_employee_id: record.supervisor_employee_id || null,
            };
            recordToEdit.value = record;
        }
        showPreviewModal.value = true;
    };

    const closePreviewModal = () => {
        showPreviewModal.value = false;
        isPreviewFromTable.value = false;
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
        recordToEdit,
        recordToDelete,
        todayDate,
        // Permissions
        canCreateTardiness,
        canEditTardiness,
        canDeleteTardiness,
        // Methods
        generateControlNo,
        computeUndertime,
        getRequestingEmployee,
        getSupervisor,
        getProvincialBudgetOfficer,
        getProvincialGovernor,
        isRequestingEmployeeProvincialBudgetOfficer,
        getEmployeeOffice,
        formatDateForInput,
        formatTimeForInput,
        formatDate,
        formatTime,
        formatTimeDisplay,
        validateForm,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        openPreviewModal,
        closePreviewModal,
    };
}
