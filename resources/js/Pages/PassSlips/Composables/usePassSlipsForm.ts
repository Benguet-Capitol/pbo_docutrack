import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PassSlip, Employee } from './usePassSlipsData';

export function usePassSlipsForm(employees: any, passSlips: any) {
    const formData = ref({
        control_no: '',
        date: '',
        inclusive_dates: [] as string[],
        newInclusiveDate: '',
        newInclusiveDateRange: '',
        requested_time: '',
        purpose: '',
        location: '',
        expected_return_time: '',
        remarks: '',
        employee_ids: [] as number[],
        recommending_approval_employee_id: null as number | null,
        vehicle: 'RP Vehicle' as 'RP Vehicle' | 'PUJ',
        returnType: 'time' as 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo',
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

    const passSlipToEdit = ref<PassSlip | null>(null);
    const passSlipToDelete = ref<PassSlip | null>(null);
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

    // Watch for returnType changes
    watch(
        () => formData.value.returnType,
        (newType) => {
            if (newType === 'asap') {
                formData.value.expected_return_time = 'ASAP';
            } else if (newType === 'nwd') {
                formData.value.expected_return_time = 'NWD';
            } else if (newType === 'time_slip') {
                formData.value.expected_return_time = 'Time Slip';
            } else if (newType === 'nom') {
                formData.value.expected_return_time = 'NOM';
            } else if (newType === 'memo') {
                formData.value.expected_return_time = 'Memo';
            }
        }
    );

    // Watch for date changes to regenerate control number (only for new records)
    watch(
        () => formData.value.date,
        (newDate) => {
            if (newDate && isRegeneratingControlNo.value) {
                formData.value.control_no = generateControlNo(newDate);
            }
        }
    );

    // Watch for employee_ids changes to auto-set recommending approval when PBO is selected
    watch(
        () => formData.value.employee_ids,
        (newEmployeeIds) => {
            if (isProvincialBudgetOfficerRequesting()) {
                const pgov = getProvincialGovernor();
                if (pgov) {
                    formData.value.recommending_approval_employee_id = pgov.id;
                }
            }
        },
        { deep: true }
    );

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'PS';

        // Find the maximum series number from all records in the same year
        let maxSeries = 0;
        passSlips.value.forEach((slip: PassSlip) => {
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
        
        // If it doesn't contain a colon, it's likely a special value like ASAP, NWD, etc.
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

    const formatDateForInput = (dateStr: string | null | undefined): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const formatDate = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatTime = (timeStr: string): string => {
        if (!timeStr) return '';
        try {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            if (isNaN(hour)) return '';
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        } catch (e) {
            return '';
        }
    };

    const getEmployeeNames = (): string => {
        const selectedEmployees = sortedEmployees.value.filter((emp: Employee) =>
            formData.value.employee_ids.includes(emp.id)
        );
        return selectedEmployees.map((emp: Employee) => emp.name).join(', ') || '[Employee Name]';
    };

    const getEmployeeName = (empId: number): string => {
        const employee = employees.value.find((emp: Employee) => emp.id === empId);
        return employee ? employee.name : '[Employee Name]';
    };

    const getEmployeeDesignation = (empId: number): string => {
        const employee = employees.value.find((emp: Employee) => emp.id === empId);
        return employee ? employee.designation : '';
    };

    const getRecommendingApprovalEmployee = (): Employee | null => {
        if (!formData.value.recommending_approval_employee_id) {
            return null;
        }
        return employees.value.find((emp: Employee) => emp.id === formData.value.recommending_approval_employee_id) || null;
    };

    const getProvincialGovernor = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
    };

    const getProvincialBudgetOfficer = (): Employee | null => {
        return employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer') || null;
    };

    const isProvincialBudgetOfficerRequesting = (): boolean => {
        return formData.value.employee_ids.some((empId: number) => {
            const emp = employees.value.find((e: Employee) => e.id === empId);
            return emp?.designation === 'Provincial Budget Officer';
        });
    };

    const validateReturnTime = () => {
        if (formData.value.returnType === 'time') {
            if (!formData.value.expected_return_time.trim()) {
                formErrors.value['expected_return_time'] = 'Please enter a return time';
                return;
            }

            // Check if return time is after requested time
            if (formData.value.expected_return_time <= formData.value.requested_time) {
                formErrors.value['expected_return_time'] = 'Return time must be after the requested leave time';
            } else {
                delete formErrors.value['expected_return_time'];
            }
        } else {
            delete formErrors.value['expected_return_time'];
        }
    };

    const validateForm = (): boolean => {
        formErrors.value = {};

        if (!formData.value.date.trim()) {
            formErrors.value['date'] = 'Date is required';
        }

        if (!formData.value.requested_time.trim()) {
            formErrors.value['requested_time'] = 'Requested time is required';
        }

        if (!formData.value.purpose.trim()) {
            formErrors.value['purpose'] = 'Purpose is required';
        }

        if (formData.value.employee_ids.length === 0) {
            formErrors.value['employee_ids'] = 'At least one employee is required';
        }

        return Object.keys(formErrors.value).length === 0;
    };

     // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create pass slips
     * Only Developer and Administrator can create pass slips
     */
    const canCreatePassSlips = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    /**
     * Check if current user can edit pass slips
     * All authenticated users can edit pass slips (they all have pass_slips.edit permission)
     */
    const canEditPassSlips = computed(() => {
        return true;
    });

    /**
     * Check if current user can delete pass slips
     * Only Developer and Administrator can delete pass slips
     */
    const canDeletePassSlips = computed(() => {
        return ['Developer', 'Administrator'].includes(usertype.value);
    });

    const openCreateModal = () => {
        isRegeneratingControlNo.value = true;
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            control_no: generateControlNo(today),
            date: today,
            inclusive_dates: [],
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            requested_time: '08:00',
            purpose: '',
            location: '',
            expected_return_time: '12:00',
            remarks: '',
            employee_ids: [],
            returnType: 'time',
            recommending_approval_employee_id: null,
            vehicle: 'RP Vehicle',
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    const openEditModal = (slip: PassSlip) => {
        isRegeneratingControlNo.value = false;
        passSlipToEdit.value = slip;

        let returnType: 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo' = 'time';
        if (slip.expected_return_time === 'ASAP') returnType = 'asap';
        else if (slip.expected_return_time === 'NWD') returnType = 'nwd';
        else if (slip.expected_return_time === 'Time Slip') returnType = 'time_slip';
        else if (slip.expected_return_time === 'NOM') returnType = 'nom';
        else if (slip.expected_return_time === 'Memo') returnType = 'memo';

        formData.value = {
            control_no: slip.control_no,
            date: formatDateForInput(slip.date),
            inclusive_dates: slip.inclusive_dates ? [...slip.inclusive_dates] : [],
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            requested_time: formatTimeForAPI(slip.requested_time),
            purpose: slip.purpose,
            location: slip.location,
            expected_return_time: returnType === 'time' ? formatTimeForAPI(slip.expected_return_time) : slip.expected_return_time || '',
            remarks: slip.remarks || '',
            employee_ids: slip.employees.map(emp => emp.id),
            returnType: returnType,
            recommending_approval_employee_id: slip.recommending_approval_employee_id || null,
            vehicle: (slip.vehicle || 'RP Vehicle') as 'RP Vehicle' | 'PUJ',
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        passSlipToEdit.value = null;
    };

    const openDeleteModal = (slip: PassSlip) => {
        passSlipToDelete.value = slip;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        passSlipToDelete.value = null;
    };

    const openPreviewModal = (slip?: PassSlip) => {
        isRegeneratingControlNo.value = false;
        if (slip) {
            let returnType: 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo' = 'time';
            if (slip.expected_return_time === 'ASAP') returnType = 'asap';
            else if (slip.expected_return_time === 'NWD') returnType = 'nwd';
            else if (slip.expected_return_time === 'Time Slip') returnType = 'time_slip';
            else if (slip.expected_return_time === 'NOM') returnType = 'nom';
            else if (slip.expected_return_time === 'Memo') returnType = 'memo';

            formData.value = {
                control_no: slip.control_no,
                date: formatDateForInput(slip.date),
                inclusive_dates: slip.inclusive_dates ? [...slip.inclusive_dates] : [],
                newInclusiveDate: '',
                newInclusiveDateRange: '',
                requested_time: formatTimeForAPI(slip.requested_time),
                purpose: slip.purpose,
                location: slip.location,
                expected_return_time: returnType === 'time' ? formatTimeForAPI(slip.expected_return_time) : slip.expected_return_time || '',
                remarks: slip.remarks || '',
                employee_ids: slip.employees.map(emp => emp.id),
                returnType: returnType,
                recommending_approval_employee_id: slip.recommending_approval_employee_id || null,
                vehicle: (slip.vehicle || 'RP Vehicle') as 'RP Vehicle' | 'PUJ',
            };
            passSlipToEdit.value = slip;
        }
        showPreviewModal.value = true;
    };

    const closePreviewModal = () => {
        showPreviewModal.value = false;
        isPreviewFromTable.value = false;
    };

    const resetForm = () => {
        formData.value = {
            control_no: '',
            date: '',
            inclusive_dates: [],
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            requested_time: '',
            purpose: '',
            location: '',
            expected_return_time: '',
            remarks: '',
            employee_ids: [],
            recommending_approval_employee_id: null,
            vehicle: 'RP Vehicle',
            returnType: 'time',
        };
        formErrors.value = {};
        passSlipToEdit.value = null;
        passSlipToDelete.value = null;
    };

    const submitCreatePassSlip = async () => {
        try {
            const payloadData = {
                ...formData.value,
                requested_time: formatTimeForAPI(formData.value.requested_time),
                expected_return_time: formData.value.returnType === 'time' ? formatTimeForAPI(formData.value.expected_return_time) : formData.value.expected_return_time,
            };
            const response = await fetch('/api/pass-slips', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payloadData),
            });
            if (!response.ok) throw new Error('Failed to create pass slip');
            resetForm();
            return await response.json();
        } catch (error) {
            console.error('Error creating pass slip:', error);
            throw error;
        }
    };

    const submitEditPassSlip = async () => {
        try {
            if (!passSlipToEdit.value?.id) throw new Error('No pass slip to edit');
            const payloadData = {
                ...formData.value,
                requested_time: formatTimeForAPI(formData.value.requested_time),
                expected_return_time: formData.value.returnType === 'time' ? formatTimeForAPI(formData.value.expected_return_time) : formData.value.expected_return_time,
            };
            const response = await fetch(`/api/pass-slips/${passSlipToEdit.value.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payloadData),
            });
            if (!response.ok) throw new Error('Failed to update pass slip');
            resetForm();
            return await response.json();
        } catch (error) {
            console.error('Error editing pass slip:', error);
            throw error;
        }
    };

    const submitDeletePassSlip = async () => {
        try {
            if (!passSlipToDelete.value?.id) throw new Error('No pass slip to delete');
            const response = await fetch(`/api/pass-slips/${passSlipToDelete.value.id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
            });
            if (!response.ok) throw new Error('Failed to delete pass slip');
            resetForm();
            return await response.json();
        } catch (error) {
            console.error('Error deleting pass slip:', error);
            throw error;
        }
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
        passSlipToEdit,
        passSlipToDelete,
        todayDate,
        sortedEmployees,
        // Permissions
        canCreatePassSlips,
        canEditPassSlips,
        canDeletePassSlips,
        // Methods
        generateControlNo,
        formatTimeForAPI,
        formatTimeDisplay,
        formatDateForInput,
        formatDate,
        formatTime,
        getEmployeeNames,
        getEmployeeName,
        getEmployeeDesignation,
        getRecommendingApprovalEmployee,
        getProvincialGovernor,
        getProvincialBudgetOfficer,
        isProvincialBudgetOfficerRequesting,
        validateReturnTime,
        validateForm,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        openPreviewModal,
        closePreviewModal,
        resetForm,
        submitCreatePassSlip,
        submitEditPassSlip,
        submitDeletePassSlip,
    };
}
