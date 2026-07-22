import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { TravelOrder, Employee } from './useTravelOrdersData';

export function useTravelOrdersForm(employees: any, travelOrders: any) {
    const formData = ref({
        control_no: '',
        date: '',
        going_to: '',
        inclusive_dates: [] as string[],
        purpose: [] as string[],
        vehicle: 'RP Vehicle' as 'PUJ' | 'RP Vehicle',
        plate_number: '',
        employee_ids: [] as number[],
        driver: '',
        newPurpose: '',
        newInclusiveDate: '',
        newInclusiveDateRange: '',
        supervisor_employee_id: null as number | null,
        approver_employee_id: null as number | null,
        is_acting_approver: false,
        acting_approver_name: '',
        acting_approver_designation: '',
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

    const travelOrderToEdit = ref<TravelOrder | null>(null);
    const travelOrderToDelete = ref<TravelOrder | null>(null);

    const editingPurposeIndex = ref<number | null>(null);
    const editingPurposeValue = ref('');

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

    const pboEmployee = computed(() => {
        return employees.value.find((emp: Employee) => emp.designation?.includes('Provincial Budget Officer'));
    });

    const provincialGovernorEmployee = computed(() => {
        return employees.value.find((emp: Employee) => emp.designation?.includes('Provincial Governor'));
    });

    const pboOfficeEmployees = computed(() => {
        return employees.value
            .filter((emp: Employee) => emp.office_id === 12)
            .sort((a: Employee, b: Employee) => {
                const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
                const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
                return lastNameA.localeCompare(lastNameB);
            });
    });

    const requestingEmployees = computed(() => sortedEmployees.value);

    const supervisorOptions = computed(() => pboOfficeEmployees.value);

    const approverOptions = computed(() => {
        const options = [];
        if (provincialGovernorEmployee.value) options.push(provincialGovernorEmployee.value);
        if (pboEmployee.value) options.push(pboEmployee.value);
        return options;
    });

    const driverOptions = computed(() => {
        return pboOfficeEmployees.value
            .map((emp: Employee) => emp.name)
            .sort();
    });

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'TO';

        const maxSeries = Math.max(
            ...travelOrders.value
                .filter((order: TravelOrder) => order.control_no.startsWith(`${prefix}-${year}`))
                .map((order: TravelOrder) => {
                    const parts = order.control_no.split('-');
                    return parseInt(parts[parts.length - 1], 10) || 0;
                }),
            0
        );

        const series = String(maxSeries + 1).padStart(4, '0');
        return `${prefix}-${year}-${month}-${series}`;
    };

    const formatDateForInput = (dateStr: string | null | undefined): string => {
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

    const formatDateForDisplayFull = (dateStr: string): string => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    const validateForm = (): boolean => {
        formErrors.value = {};

        if (!formData.value.date.trim()) {
            formErrors.value['date'] = 'Date is required';
        }

        if (!formData.value.going_to.trim()) {
            formErrors.value['going_to'] = 'Going to is required';
        }

        if (formData.value.inclusive_dates.length === 0) {
            formErrors.value['inclusive_dates'] = 'At least one inclusive date is required';
        }

        if (formData.value.purpose.length === 0) {
            formErrors.value['purpose'] = 'At least one purpose is required';
        }

        if (!formData.value.vehicle) {
            formErrors.value['vehicle'] = 'Vehicle is required';
        }

        if (formData.value.employee_ids.length === 0) {
            formErrors.value['employee_ids'] = 'At least one employee is required';
        }

        if (!formData.value.is_acting_approver && !formData.value.approver_employee_id) {
            formErrors.value['approver_employee_id'] = 'Approver is required';
        }

        if (formData.value.is_acting_approver) {
            if (!formData.value.acting_approver_name?.trim()) {
                formErrors.value['acting_approver_name'] = 'Acting approver name is required';
            }
            if (!formData.value.acting_approver_designation?.trim()) {
                formErrors.value['acting_approver_designation'] = 'Acting approver designation is required';
            }
        }

        return Object.keys(formErrors.value).length === 0;
    };

    // Watch for date changes to regenerate control_no
    watch(
        () => formData.value.date,
        (newDate) => {
            if (showCreateModal.value && newDate) {
                formData.value.control_no = generateControlNo(newDate);
            }
        }
    );



    // Watch for approver changes - if Provincial Governor is selected, auto-set supervisor to PBO
    watch(
        () => formData.value.approver_employee_id,
        (newApproverId) => {
            if (!newApproverId) return;
            const approver = employees.value.find((emp: Employee) => emp.id === newApproverId);
            if (approver?.designation?.includes('Provincial Governor')) {
                // If PBO is approver, set PBO as supervisor
                formData.value.supervisor_employee_id = pboEmployee.value?.id || null;
            }
        }
    );

    // Watch for employee_ids changes - if PBO is in requesting employees, auto-set approver to PG and clear supervisor
    watch(
        () => formData.value.employee_ids,
        (newEmployeeIds) => {
            if (!newEmployeeIds || newEmployeeIds.length === 0) return;
            const isPBORequesting = newEmployeeIds.some((id: number) => {
                const emp = employees.value.find((e: Employee) => e.id === id);
                return emp?.designation?.includes('Provincial Budget Officer');
            });
            if (isPBORequesting) {
                // If PBO is requesting, approver must be PG and no supervisor
                formData.value.approver_employee_id = provincialGovernorEmployee.value?.id || null;
                formData.value.supervisor_employee_id = null;
            }
        }
    );

    // ============== User Permissions ==============

    const page = usePage();
    const usertype = computed(() => page.props.auth.user?.usertype || '');

    /**
     * Check if current user can create travel orders
     * Only Developer and Administrator can create travel orders
     */
    const canCreateTravelOrders = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    /**
     * Check if current user can edit travel orders
     * All authenticated users can edit travel orders (they all have travel_orders.edit permission)
     */
    const canEditTravelOrders = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    /**
     * Check if current user can delete travel orders
     * Only Developer and Administrator can delete travel orders
     */
    const canDeleteTravelOrders = computed(() => {
        return ['Developer', 'Administrator', 'Administrative'].includes(usertype.value);
    });

    const openCreateModal = () => {
        const today = todayDate.value;
        formData.value = {
            control_no: generateControlNo(today),
            date: today,
            going_to: '',
            inclusive_dates: [],
            purpose: [],
            vehicle: 'RP Vehicle',
            plate_number: '',
            employee_ids: [],
            driver: '',
            newPurpose: '',
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            supervisor_employee_id: null,
            approver_employee_id: null,
            is_acting_approver: false,
            acting_approver_name: '',
            acting_approver_designation: '',
        };
        formErrors.value = {};
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    const openEditModal = (order: TravelOrder) => {
        travelOrderToEdit.value = order;
        formData.value = {
            control_no: order.control_no,
            date: formatDateForInput(order.date),
            going_to: order.going_to,
            inclusive_dates: order.inclusive_dates ? [...order.inclusive_dates] : [],
            purpose: [...order.purpose],
            vehicle: order.vehicle as 'PUJ' | 'RP Vehicle',
            plate_number: order.plate_number || '',
            employee_ids: order.employees.map(emp => emp.id),
            driver: order.driver || '',
            newPurpose: '',
            newInclusiveDate: '',
            newInclusiveDateRange: '',
            supervisor_employee_id: order.supervisor_employee_id || null,
            approver_employee_id: order.approver_employee_id || null,
            is_acting_approver: order.is_acting_approver ?? false,
            acting_approver_name: order.acting_approver_name || '',
            acting_approver_designation: order.acting_approver_designation || '',
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        travelOrderToEdit.value = null;
    };

    const openDeleteModal = (order: TravelOrder) => {
        travelOrderToDelete.value = order;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        travelOrderToDelete.value = null;
    };

    const openPreviewModal = () => {
        // If coming from table, populate formData from the selected travel order
        if (isPreviewFromTable.value && travelOrderToEdit.value) {
            const order = travelOrderToEdit.value;
            formData.value = {
                control_no: order.control_no,
                date: formatDateForInput(order.date),
                going_to: order.going_to,
                inclusive_dates: order.inclusive_dates ? [...order.inclusive_dates] : [],
                purpose: [...order.purpose],
                vehicle: order.vehicle as 'PUJ' | 'RP Vehicle',
                plate_number: order.plate_number || '',
                employee_ids: order.employees.map(emp => emp.id),
                driver: order.driver || '',
                newPurpose: '',
                newInclusiveDate: '',
                newInclusiveDateRange: '',
                supervisor_employee_id: order.supervisor_employee_id || null,
                approver_employee_id: order.approver_employee_id || null,
                is_acting_approver: order.is_acting_approver ?? false,
                acting_approver_name: order.acting_approver_name || '',
                acting_approver_designation: order.acting_approver_designation || '',
            };
        }
        // Otherwise formData should already be populated from the edit/create modal
        showPreviewModal.value = true;
    };

    const closePreviewModal = () => {
        showPreviewModal.value = false;
        isPreviewFromTable.value = false;
    };

    const addPurpose = () => {
        if (formData.value.newPurpose.trim()) {
            formData.value.purpose.push(formData.value.newPurpose.trim());
            formData.value.newPurpose = '';
        }
    };

    const removePurpose = (index: number) => {
        formData.value.purpose.splice(index, 1);
    };

    const startEditPurpose = (index: number) => {
        editingPurposeIndex.value = index;
        editingPurposeValue.value = formData.value.purpose[index];
    };

    const saveEditPurpose = (index: number) => {
        if (editingPurposeValue.value.trim()) {
            formData.value.purpose[index] = editingPurposeValue.value.trim();
        }
        editingPurposeIndex.value = null;
        editingPurposeValue.value = '';
    };

    const cancelEditPurpose = () => {
        editingPurposeIndex.value = null;
        editingPurposeValue.value = '';
    };

    const addInclusiveDate = () => {
        if (formData.value.newInclusiveDate.trim()) {
            formData.value.inclusive_dates = [...formData.value.inclusive_dates, formData.value.newInclusiveDate.trim()];
            formData.value.newInclusiveDate = '';
        }
    };

    const addInclusiveDateRange = () => {
        if (formData.value.newInclusiveDateRange.trim()) {
            formData.value.inclusive_dates = [...formData.value.inclusive_dates, formData.value.newInclusiveDateRange.trim()];
            formData.value.newInclusiveDateRange = '';
        }
    };

    const removeInclusiveDate = (index: number) => {
        formData.value.inclusive_dates = formData.value.inclusive_dates.filter((_, i) => i !== index);
    };

    const formatInclusiveDate = (dateEntry: string): string => {
        if (!dateEntry) return '';
        
        if (dateEntry.includes(' - ')) {
            const [startStr, endStr] = dateEntry.split(' - ');
            const startDate = new Date(startStr.trim());
            const endDate = new Date(endStr.trim());
            const start = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const end = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            return `${start} - ${end}`;
        } else {
            const date = new Date(dateEntry.trim());
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    };

    const getSupervisor = (): Employee | null => {
        if (!formData.value.supervisor_employee_id) {
            return null;
        }
        return employees.value.find((emp: Employee) => emp.id === formData.value.supervisor_employee_id) || null;
    };

    const getApproverName = (): string => {
        if (formData.value.is_acting_approver && formData.value.acting_approver_name?.trim()) {
            return formData.value.acting_approver_name.trim();
        }
        if (!formData.value.approver_employee_id) return '';
        const approver = employees.value.find((emp: Employee) => emp.id === formData.value.approver_employee_id);
        return approver?.name || '';
    };

    const getApproverRole = (): string => {
        if (formData.value.is_acting_approver && formData.value.acting_approver_designation?.trim()) {
            return formData.value.acting_approver_designation.trim();
        }
        if (!formData.value.approver_employee_id) return '';
        const approver = employees.value.find((emp: Employee) => emp.id === formData.value.approver_employee_id);
        return approver?.designation || '';
    };

    const isApproverProvincialGovernor = (): boolean => {
        if (formData.value.is_acting_approver && formData.value.acting_approver_designation?.trim()) {
            return formData.value.acting_approver_designation.toLowerCase().includes('governor');
        }
        if (!formData.value.approver_employee_id) return false;
        const approver = employees.value.find((emp: Employee) => emp.id === formData.value.approver_employee_id);
        return approver?.designation?.toLowerCase().includes('governor') || false;
    };

    const isProvincialBudgetOfficerRequestingEmployee = (): boolean => {
        if (!formData.value.employee_ids || formData.value.employee_ids.length === 0) return false;
        return formData.value.employee_ids.some((id: number) => {
            const emp = employees.value.find((e: Employee) => e.id === id);
            return emp?.designation?.toLowerCase().includes('budget officer');
        });
    };

    const getRecommendingApprovalSignatory = (): Employee | null => {
        if (!formData.value.supervisor_employee_id) return null;
        return employees.value.find((emp: Employee) => emp.id === formData.value.supervisor_employee_id) || null;
    };

    const getRecommendingApprovalDesignation = (): string => {
        const supervisor = getRecommendingApprovalSignatory();
        if (!supervisor) return '';
        return supervisor.designation || '';
    };

    const getRequestingEmployeeNames = (): string => {
        if (!formData.value.employee_ids || formData.value.employee_ids.length === 0) return '';
        const names = formData.value.employee_ids
            .map((id: number) => employees.value.find((e: Employee) => e.id === id)?.name || '')
            .filter((name: string) => name);
        return names.join(', ');
    };

    return {
        // State
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
        travelOrderToEdit,
        travelOrderToDelete,
        editingPurposeIndex,
        editingPurposeValue,

        // Computed
        todayDate,
        sortedEmployees,
        requestingEmployees,
        supervisorOptions,
        approverOptions,
        driverOptions,

        // Permissions
        canCreateTravelOrders,
        canEditTravelOrders,
        canDeleteTravelOrders,

        // Methods
        generateControlNo,
        formatDateForInput,
        formatDateForDisplay,
        formatDateForDisplayFull,
        validateForm,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        openPreviewModal,
        closePreviewModal,
        addPurpose,
        removePurpose,
        startEditPurpose,
        saveEditPurpose,
        cancelEditPurpose,
        addInclusiveDate,
        addInclusiveDateRange,
        removeInclusiveDate,
        formatInclusiveDate,
        getSupervisor,
        getApproverName,
        getApproverRole,
        isApproverProvincialGovernor,
        isProvincialBudgetOfficerRequestingEmployee,
        getRecommendingApprovalSignatory,
        getRecommendingApprovalDesignation,
        getRequestingEmployeeNames,
    };
}