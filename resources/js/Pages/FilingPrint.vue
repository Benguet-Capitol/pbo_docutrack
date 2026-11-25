<template>
    <!-- One filing's print preview for pbo-registryV2's My Filings (shown in its View/Print frame): the module's own preview, opened as from its table -->
    <Head :title="`Print ${record?.control_no ?? record?.[0]?.control_no ?? ''}`" />

    <PassSlipPreview v-if="module === 'pass-slips'" :show="true" :form-data="passSlip" :employees="employees" :sorted-employees="sortedEmployees" :is-preview-from-table="true" @close="close" />
    <TimeSlipPreview v-else-if="module === 'time-slips'" :show="true" :form-data="timeSlip" :employees="employees" :sorted-employees="sortedEmployees" :is-preview-from-table="true" @close="close" />
    <TardinessPreview
        v-else-if="module === 'tardiness'"
        :show="true"
        :form-data="tardiness"
        :requesting-employee="find(tardiness.employee_id)"
        :supervisor="find(tardiness.supervisor_employee_id)"
        :provincial-budget-officer="employees.find((e) => isProvincialBudgetOfficer(e)) || null"
        :provincial-governor="employees.find((e) => isProvincialGovernor(e)) || null"
        :approved-by="resolveApprovedBy(sortedEmployees, tardiness.approved_by_employee_id, tardiness.approved_by_title)"
        :saving="false"
        :is-preview-from-table="true"
        @close="close"
    />
    <LeavePreview v-else-if="module === 'leaves' && leave" :show="true" :leave="leave" :employee="leaveEmployee" :signatories="leaveSignatories" mode="view" :leave-id="null" @close="close" />
    <TravelOrderPreview v-else-if="module === 'travel-orders'" :show="true" :form-data="travelOrder" :employees="employees" :sorted-employees="sortedEmployees" :is-preview-from-table="true" hide-trip-ticket @close="close" />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PassSlipPreview from './PassSlips/Partials/PreviewModal.vue';
import TimeSlipPreview from './TimeSlips/Partials/PreviewModal.vue';
import TardinessPreview from './Tardiness/Partials/PreviewModal.vue';
import LeavePreview from './Leaves/Partials/PreviewModal.vue';
import TravelOrderPreview from './TravelOrders/Partials/PreviewModal.vue';
import { groupApplications } from './Leaves/Composables/useLeavesData';
import { resolveLeaveSignatory } from './Leaves/Composables/useLeaveSignatories';
import { resolveApprovedBy } from '@/Composables/useSignatory';
import { isProvincialBudgetOfficer, isProvincialGovernor } from '@/Composables/useOfficials';

const props = defineProps<{
    module: 'leaves' | 'pass-slips' | 'time-slips' | 'tardiness' | 'travel-orders';
    /** The slip, or a leave application's rows */
    record: any;
    employees: any[];
}>();

// As the module pages sort them (by last name)
const sortedEmployees = computed(() => props.employees.slice().sort((a, b) => (a.name.split(' ').pop() || '').toLowerCase().localeCompare((b.name.split(' ').pop() || '').toLowerCase())));
const find = (id: number | null | undefined) => (id ? props.employees.find((e) => e.id === id) || null : null);
const hhmm = (value: string | null | undefined) => (value ? String(value).slice(0, 5) : '');
const day = (value: string | null | undefined) => (value ? String(value).slice(0, 10) : '');

// The frame's page closes its View/Print modal
const close = () => window.parent?.postMessage({ type: 'docutrack-filing-print-close' }, '*');

// See-through page in the frame: the preview's own backdrop dims the registry page behind it (a different color-scheme would make the frame opaque)
onMounted(() => {
    for (const el of [document.documentElement, document.body]) {
        el.style.background = 'transparent';
        el.style.colorScheme = 'normal';
    }
    // In the frame, Print hands the prepared page to pbo-registryV2, which prints it in the same tab (usePrint)
    document.documentElement.dataset.printByParent = '1';
});

// The same values the pages' openPreviewModal() builds from a saved record
const RETURN_TYPES: Record<string, string> = { ASAP: 'asap', NWD: 'nwd', 'Time Slip': 'time_slip', NOM: 'nom', Memo: 'memo' };
const passSlip = computed(() => {
    const s = props.record;
    const returnType = RETURN_TYPES[s.expected_return_time] ?? 'time';
    return {
        control_no: s.control_no, date: day(s.date), inclusive_dates: [...(s.inclusive_dates || [])], newInclusiveDate: '', newInclusiveDateRange: '',
        requested_time: hhmm(s.requested_time), purpose: s.purpose, location: s.location,
        expected_return_time: returnType === 'time' ? hhmm(s.expected_return_time) : s.expected_return_time || '',
        remarks: s.remarks || '', employee_ids: (s.employees || []).map((e: any) => e.id), returnType,
        recommending_approval_employee_id: s.recommending_approval_employee_id || null, recommending_approval_employee_ids: [...(s.recommending_approval_employee_ids || [])],
        approved_by_employee_id: s.approved_by_employee_id ?? null,
        approved_by_title: s.approved_by_title ?? null, vehicle: s.vehicle || 'RP Vehicle',
    };
});
const timeSlip = computed(() => {
    const t = props.record;
    return {
        control_no: t.control_no, requesting_employee_id: t.requesting_employee_id, date: day(t.date), time_in_am: t.time_in_am || '', time_out_am: t.time_out_am || '',
        time_in_pm: t.time_in_pm || '', time_out_pm: t.time_out_pm || '', reason: t.reason, certified_by_employee_id: t.certified_by_employee_id,
        approved_by_employee_id: t.approved_by_employee_id ?? null, approved_by_title: t.approved_by_title ?? null,
    };
});
const tardiness = computed(() => {
    const r = props.record;
    return {
        control_no: r.control_no, date_filed: day(r.date_filed), type: r.type, requested_date: day(r.requested_date), employee_id: r.employee_id,
        requested_time: hhmm(r.requested_time), reason: r.reason, return_time: r.return_time || '',
        returnType: (r.return_time === 'NWD' || r.return_time === '17:00:00' ? 'nwd' : 'time') as 'time' | 'nwd',
        supervisor_employee_id: r.supervisor_employee_id || null, approved_by_employee_id: r.approved_by_employee_id ?? null, approved_by_title: r.approved_by_title ?? null,
    };
});

// As the Travel Orders page's openPreviewModal() fills it from a saved order
const travelOrder = computed(() => {
    const o = props.record;
    return {
        control_no: o.control_no, date: day(o.date), going_to: o.going_to, inclusive_dates: [...(o.inclusive_dates || [])], purpose: [].concat(o.purpose ?? []),
        vehicle: o.vehicle, plate_number: o.plate_number || '', employee_ids: (o.employees || []).map((e: any) => e.id), driver: o.driver || '',
        newPurpose: '', newInclusiveDate: '', newInclusiveDateRange: '', supervisor_employee_id: o.supervisor_employee_id || null,
        approver_employee_id: o.approver_employee_id || null, is_acting_approver: o.is_acting_approver ?? false,
        acting_approver_name: o.acting_approver_name || '', acting_approver_designation: o.acting_approver_designation || '',
    };
});

const leave = computed(() => (props.module === 'leaves' ? groupApplications(props.record)[0] ?? null : null));
const leaveEmployee = computed(() => find(Number(leave.value?.employee_id)) || leave.value?.employee || null);
const leaveSignatories = computed(() => ({
    certified_by: resolveLeaveSignatory(props.employees, leave.value, 'certified_by'),
    recommended_by: resolveLeaveSignatory(props.employees, leave.value, 'recommended_by'),
    approved_by: resolveLeaveSignatory(props.employees, leave.value, 'approved_by'),
}));
</script>
