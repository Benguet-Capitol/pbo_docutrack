<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import RowIconButton from '@/Components/RowIconButton.vue';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import { LIST_HEAD, listRowClass } from '@/utils/listTable';
import { MODAL_ADD, MODAL_ADD_OUTLINE, MODAL_CANCEL, MODAL_SUBMIT } from './profileStyles';
import LeaveSignatoryField from '@/Pages/Leaves/Partials/LeaveSignatoryField.vue';
import LeaveActingHeadCheckbox from '@/Pages/Leaves/Partials/LeaveActingHeadCheckbox.vue';
import { LEAVE_SIGNATORIES, applyDefaultSignatories } from '@/Pages/Leaves/Composables/useLeaveSignatories';

// PBO staff file and edit their own leaves, pass slips, time slips and tardiness (as on pbo-registryV2's profile, which saves
// through the Filing API). The server sets the control number and runs the module's own checks; a leave is For approval unless
// the form says For disapproval.
const props = withDefaults(defineProps<{
    records: any;
    travelOrders?: any[]; // from the leave summary: shown, not filed here
    // Who can sign a leave (7.A, 7.B, authorized official) and the applicant, who decides the default authorized official
    leaveSignatories?: { employees: any[]; applicant: any } | null;
}>(), { travelOrders: () => [], leaveSignatories: null });

// A leave's signatories: its own, else the current officers, as on the Leaves form (each can be changed to an OIC or Acting)
function leaveSignatoryValues(row: any = null): Record<string, any> {
    const list = props.leaveSignatories;
    if (!list) return {};
    const data: Record<string, any> = { employee: list.applicant };
    for (const { key } of LEAVE_SIGNATORIES) {
        data[`${key}_employee_id`] = row?.[`${key}_employee_id`] ?? null;
        data[`${key}_title`] = row?.[`${key}_title`] ?? null;
    }
    applyDefaultSignatories(data, list.employees);
    delete data.employee;
    return data;
}

type Module = 'leaves' | 'pass-slips' | 'time-slips' | 'tardiness';
type TabKey = Module | 'travel-orders';
type Tab = { key: TabKey; label: string; icon: string; list?: string; readonly?: boolean };

const signatories = computed(() => props.records?.signatories ?? { supervisors: [], approvers: [], titles: [], budget_officer_id: null, governor_approves: false });

const TABS: Tab[] = [
    { key: 'leaves', label: 'Leaves', icon: 'fa-umbrella-beach' },
    { key: 'pass-slips', label: 'Pass Slips', icon: 'fa-id-card', list: 'pass_slips' },
    { key: 'time-slips', label: 'Time Slips', icon: 'fa-clock', list: 'time_slips' },
    { key: 'tardiness', label: 'Tardiness/Undertime', icon: 'fa-hourglass-half' },
    // Travel orders are made on the Travel Orders page: listed only
    { key: 'travel-orders', label: 'Travel Orders', icon: 'fa-route', readonly: true },
];
const SINGULAR: Record<Module, string> = { leaves: 'Leave', 'pass-slips': 'Pass Slip', 'time-slips': 'Time Slip', tardiness: 'Tardiness/Undertime' };
// Each tab's table columns (Actions is added to all but Travel Orders)
const HEADERS: Record<TabKey, string[]> = {
    leaves: ['Control No.', 'Date Filed', 'Type of Leave', 'Inclusive Dates', 'Days'],
    'pass-slips': ['Control No.', 'Date', 'Inclusive Dates', 'Time Out – Return', 'Purpose / Location'],
    'time-slips': ['Control No.', 'Date', 'AM In – Out', 'PM In – Out', 'Reason'],
    tardiness: ['Control No.', 'Date Filed', 'Type', 'Requested Date & Time', 'Reason'],
    'travel-orders': ['Control No.', 'Date', 'Inclusive Dates', 'Destination', 'Purpose'],
};
// Cell highlights, the same colors as the module lists (utils/listTable.ts)
const TAG = 'inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold';
const TAG_TYPE = 'whitespace-nowrap rounded bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200';
const TAG_HALF = 'rounded bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200';
const TAG_DATE = 'whitespace-nowrap rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
const TAG_TIME = 'whitespace-nowrap rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
const TAG_TARDY = 'rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
const TAG_UNDERTIME = 'rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
const TAG_PURPOSE = 'rounded bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200';
// Each slip's supervisor field and its label on the module's form
const SUPERVISOR: Record<Exclude<Module, 'leaves'>, [string, string]> = {
    'pass-slips': ['recommending_approval_employee_id', 'Recommending approval (supervisor)'],
    'time-slips': ['certified_by_employee_id', 'Certified by (supervisor)'],
    tardiness: ['supervisor_employee_id', 'Supervisor'],
};

// Opens on ?tab= (set after filing, so the user lands on the record's tab), else Leaves
const urlTab = new URLSearchParams(window.location.search).get('tab');
const tab = ref<TabKey>(TABS.some((t) => t.key === urlTab) ? (urlTab as TabKey) : 'leaves');
const module = computed(() => tab.value as Module);
const supervisorField = computed(() => (SUPERVISOR as any)[tab.value]?.[0] as string);
const supervisorLabel = computed(() => (SUPERVISOR as any)[tab.value]?.[1] as string);
// Latest control number first (PREFIX-YYYY-MM-NNNN), so a record just filed is on top
const byControlNo = (list: any[]) => [...list].sort((a, b) => String(b.control_no ?? '').localeCompare(String(a.control_no ?? ''), undefined, { numeric: true }));
const rows = (t: Tab): any[] => byControlNo(t.key === 'travel-orders' ? props.travelOrders : props.records?.[t.list ?? t.key] ?? []);
const current = computed(() => TABS.find((t) => t.key === tab.value)!);

const LOCATION_TYPES = ['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'];
const RETURN_WORDS = ['ASAP', 'NWD', 'NOM', 'Memo', 'Time Slip'];
const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0]; // Monday first, as printed

// "2026-11-05" -> "Nov 5, 2026"; "14:30" -> "2:30 PM"
const formatDate = (value: any) => (value ? new Date(`${String(value).slice(0, 10)}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '');
function formatTime(value: any): string {
    if (!value || !/^\d{1,2}:\d{2}/.test(value)) return value ?? '';
    const [h, m] = String(value).split(':').map(Number);
    return `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
}
const formatDates = (dates: any[]) => (dates ?? []).map((d) => String(d).split(' - ').map(formatDate).join(' – ')).join(', ');
const formatDays = (days: number) => String(Math.round(days * 10) / 10);

// --- Off days and working days, as the Leaves form counts them: weekday names, none entered = Saturdays and Sundays
function offWeekdays(offDays: string[] | null): number[] {
    const text = (offDays ?? []).join(' ').toLowerCase();
    const days = WEEKDAYS.map((day, i) => (text.includes(day.slice(0, 3).toLowerCase()) ? i : -1)).filter((i) => i >= 0);
    return days.length || (offDays ?? []).length ? days : [0, 6];
}
function workingDates(entry: any): string[] {
    const off = new Set<number>(entry.off);
    const dates = new Set<string>();
    for (const item of entry.inclusive_dates) {
        const [from, to] = String(item).split(' - ');
        const day = new Date(`${from}T00:00:00`);
        const end = new Date(`${to ?? from}T00:00:00`);
        for (let guard = 0; day <= end && guard < 400; guard++, day.setDate(day.getDate() + 1)) {
            if (!off.has(day.getDay())) dates.add(day.toLocaleDateString('en-CA'));
        }
    }
    return [...dates];
}
const workingDays = (entry: any) => workingDates(entry).length;
// A leave's days, as counted against a yearly limit (a half day is 0.5)
const leaveDays = (e: any) => (e.is_half_day ? 0.5 : workingDays(e));

// --- The form: one per module, empty for a new record or filled from the row being edited
const show = ref(false);
const editing = ref<any>(null);
const form = useForm<{ values: any }>({ values: {} });
const today = () => new Date().toLocaleDateString('en-CA');

const blankEntry = () => ({ id: null, type_of_leave: '', inclusive_dates: [] as string[], from: today(), to: '', off: [0, 6], is_half_day: false, half_day_period: '', within_philippines: true, purpose: '', in_hospital: false, illness: '', completion_type: '' });
const signatoryBlank = (m: Exclude<Module, 'leaves'>) => ({ [SUPERVISOR[m][0]]: null, approved_by_employee_id: signatories.value.budget_officer_id, approved_by_title: signatories.value.titles[0] ?? null });

function blank(m: Module): any {
    if (m === 'leaves') return { recommendation: 'approval', disapproval_reason: '', entries: [blankEntry()], ...leaveSignatoryValues() };
    if (m === 'pass-slips') return { date: today(), inclusive_dates: [today()], newDate: '', requested_time: '08:00', purpose: '', location: '', return: 'time', expected_return_time: '12:00', remarks: '', vehicle: 'RP Vehicle', ...signatoryBlank(m) };
    if (m === 'time-slips') return { date: today(), time_in_am: '', time_out_am: '', time_in_pm: '', time_out_pm: '', reason: '', ...signatoryBlank(m) };
    return { type: 'Undertime', requested_date: today(), requested_time: '', return_time: '', reason: '', ...signatoryBlank(m) };
}

function open(row: any = null) {
    editing.value = row;
    const values = blank(module.value);
    if (row && module.value === 'leaves') {
        values.entries = row.entries.map((e: any) => ({ ...blankEntry(), ...Object.fromEntries(Object.entries(e).filter(([, x]) => x !== null)), inclusive_dates: [...(e.inclusive_dates ?? [])], from: '', to: '', off: offWeekdays(e.off_days) }));
        values.recommendation = row.recommendation === 'disapproval' ? 'disapproval' : 'approval';
        values.disapproval_reason = row.disapproval_reason ?? '';
        Object.assign(values, leaveSignatoryValues(row));
    } else if (row) {
        Object.keys(values).forEach((k) => { if (row[k] !== undefined && row[k] !== null) values[k] = Array.isArray(row[k]) ? [...row[k]] : row[k]; });
        values[supervisorField.value] = row[supervisorField.value] ?? null;
        values.approved_by_employee_id = row.approved_by_employee_id ?? signatories.value.budget_officer_id;
        values.approved_by_title = row.approved_by_title ?? signatories.value.titles[0] ?? null;
        if (module.value === 'pass-slips') {
            const word = RETURN_WORDS.includes(row.expected_return_time) ? row.expected_return_time : null;
            Object.assign(values, { return: word ?? 'time', expected_return_time: word ? '12:00' : String(row.expected_return_time ?? '').slice(0, 5) });
            if (!values.inclusive_dates.length) values.inclusive_dates = [values.date];
        }
        ['date', 'requested_date'].forEach((k) => { if (values[k]) values[k] = String(values[k]).slice(0, 10); });
        ['requested_time', 'time_in_am', 'time_out_am', 'time_in_pm', 'time_out_pm', 'return_time'].forEach((k) => { if (values[k]) values[k] = String(values[k]).slice(0, 5); });
    }
    form.values = values;
    form.clearErrors();
    show.value = true;
}

function addLeaveDates(entry: any) {
    if (!entry.from) return;
    const item = entry.to && entry.to !== entry.from ? `${entry.from} - ${entry.to}` : entry.from;
    if (!entry.inclusive_dates.includes(item)) entry.inclusive_dates = [...entry.inclusive_dates, item].sort();
    entry.from = '';
    entry.to = '';
}
function toggleOff(entry: any, day: number) {
    entry.off = entry.off.includes(day) ? entry.off.filter((d: number) => d !== day) : [...entry.off, day];
}
function addSlipDate() {
    const v = form.values;
    if (v.newDate && !v.inclusive_dates.includes(v.newDate)) v.inclusive_dates = [...v.inclusive_dates, v.newDate].sort();
    v.newDate = '';
}

// --- The module forms' checks: required fields, then any other reason the form can't be saved
const filled = (value: any) => String(value ?? '').trim() !== '';
const missingFields = computed(() => {
    const x = form.values;
    const missing: string[] = [];
    if (tab.value === 'leaves') {
        const entries: any[] = x.entries ?? [];
        const several = entries.length > 1;
        if (!entries.length) missing.push('Type of Leave');
        entries.forEach((e) => {
            const label = several ? ` (${e.type_of_leave || 'leave type'})` : '';
            if (!e.type_of_leave) missing.push('Type of Leave');
            if (!e.is_half_day && !(workingDays(e) > 0)) missing.push(`Number of Working Days${label}`);
            if (!e.inclusive_dates.length) missing.push(`Inclusive Dates${label}`);
            if (e.is_half_day && !e.half_day_period) missing.push(`Half Day Period (AM/PM)${label}`);
        });
        const types = entries.map((e) => e.type_of_leave);
        if (new Set(types).size !== types.length) missing.push('a different type for each entry');
        if (x.recommendation === 'disapproval' && !filled(x.disapproval_reason)) missing.push('Reason for Disapproval');
    } else if (tab.value === 'pass-slips') {
        if (!filled(x.date)) missing.push('Date');
        if (!filled(x.requested_time)) missing.push('Requested Time');
        if (!filled(x.purpose)) missing.push('Purpose');
        if (x.return === 'time' && !filled(x.expected_return_time)) missing.push('Expected Time of Return');
    } else if (tab.value === 'time-slips') {
        if (!filled(x.date)) missing.push('Date');
        if (!filled(x.reason)) missing.push('Reason');
        if (!(x.time_in_am || x.time_out_am || x.time_in_pm || x.time_out_pm)) missing.push('At least one time');
    } else {
        if (!filled(x.type)) missing.push('Type');
        if (!filled(x.requested_date)) missing.push('Requested Date');
        if (!filled(x.requested_time)) missing.push('Requested Time');
        if (!filled(x.reason)) missing.push('Reason');
    }
    return [...new Set(missing)];
});

const longDate = (date: string) => new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
// A day filed twice (by this form or another leave), then a yearly limit gone over: the same messages as the Leaves page
const submitMessage = computed<string | null>(() => {
    const x = form.values;
    if (tab.value === 'pass-slips') {
        return x.return === 'time' && x.expected_return_time && x.requested_time && x.expected_return_time <= x.requested_time ? 'Return time must be after the requested leave time' : null;
    }
    if (tab.value !== 'leaves' || !props.records?.leave_check) return null;
    const check = props.records.leave_check;
    const own = editing.value?.control_no ?? null;
    const half = (e: any) => (e.is_half_day ? e.half_day_period || null : null);
    const clash = (a: any, b: any) => !(a && b && a !== b);
    const taken = new Map<string, { where: string; half: any }[]>();
    for (const [date, items] of Object.entries<any[]>(check.days ?? {})) {
        taken.set(date, items.filter(([controlNo]) => controlNo !== own).map(([controlNo, type, h]) => ({ where: `${type} (${controlNo})`, half: h })));
    }
    for (const e of x.entries ?? []) {
        if (!e.type_of_leave) continue;
        for (const date of workingDates(e)) {
            const other = (taken.get(date) ?? []).find((item) => clash(half(e), item.half));
            if (other) return `${e.type_of_leave}: already on leave on ${longDate(date)}, filed as ${other.where}.`;
            taken.set(date, [...(taken.get(date) ?? []), { where: `${e.type_of_leave} in this application`, half: half(e) }]);
        }
    }
    const year = Number(String(editing.value?.date_of_filing ?? today()).slice(0, 4));
    for (const e of x.entries ?? []) {
        const limit = check.limits?.[e.type_of_leave];
        if (!limit) continue;
        const used = (check.limited ?? []).filter((r: any) => r.type === e.type_of_leave && r.year === year && r.control_no !== own).reduce((sum: number, r: any) => sum + Number(r.days), 0);
        const requested = leaveDays(e);
        if (used + requested <= limit) continue;
        const remaining = Math.max(0, limit - used);
        return remaining <= 0
            ? `${e.type_of_leave} is already exhausted for ${year}: all ${limit} days have been used.`
            : `${e.type_of_leave} exceeds the yearly limit for ${year}: only ${formatDays(remaining)} of ${limit} days remaining, but ${formatDays(requested)} requested.`;
    }
    return null;
});
const canSubmit = computed(() => missingFields.value.length === 0 && !submitMessage.value);

// --- View/Print: the module's own print preview of the record, in a frame (its Close button closes this too)
const printing = ref<{ title: string; url: string | null; error: string | null; loading: boolean } | null>(null);
async function openPrint(row: any) {
    const id = tab.value === 'leaves' ? row.control_no : row.id;
    printing.value = { title: `${SINGULAR[module.value]} ${row.control_no}`, url: null, error: null, loading: true };
    try {
        const { data } = await axios.get(route('profile.filings.print', { module: tab.value, id }));
        printing.value = { ...printing.value, url: data.url, loading: false };
    } catch (e: any) {
        printing.value = { ...printing.value!, error: e.response?.data?.message ?? 'The print preview is unavailable right now.', loading: false };
    }
}
function onFrameMessage(event: MessageEvent) {
    // Only the preview page in the open frame may close it or send a page to print
    if (!printing.value?.url || event.origin !== new URL(printing.value.url, window.location.href).origin) return;
    if (event.data?.type === 'docutrack-filing-print-close') printing.value = null;
    if (event.data?.type === 'docutrack-filing-print' && typeof event.data.html === 'string') printPage(event.data.html);
}
// Prints the frame's prepared page from this page: the browser won't open the print dialog inside the frame
function printPage(html: string) {
    const frame = document.createElement('iframe');
    // No scripts in it; allow-modals lets it open the print dialog
    frame.setAttribute('sandbox', 'allow-same-origin allow-modals');
    frame.setAttribute('aria-hidden', 'true');
    frame.style.cssText = 'position:fixed;left:-10000px;top:-10000px;width:1400px;height:2000px;border:0;visibility:hidden;';
    frame.addEventListener('load', async () => {
        const win = frame.contentWindow!;
        await (frame.contentDocument?.fonts?.ready ?? Promise.resolve());
        win.addEventListener('afterprint', () => setTimeout(() => frame.remove(), 500), { once: true });
        win.focus();
        win.print();
    }, { once: true });
    frame.srcdoc = html;
    document.body.appendChild(frame);
}
onMounted(() => window.addEventListener('message', onFrameMessage));
onBeforeUnmount(() => window.removeEventListener('message', onFrameMessage));

function payload() {
    const x = { ...form.values };
    if (tab.value === 'leaves') {
        return {
            recommendation: x.recommendation,
            disapproval_reason: x.recommendation === 'disapproval' ? x.disapproval_reason : null,
            // Sent only when the form has them (else the server keeps the leave's own)
            ...(props.leaveSignatories ? Object.fromEntries(LEAVE_SIGNATORIES.flatMap(({ key }) => [[`${key}_employee_id`, x[`${key}_employee_id`] ?? null], [`${key}_title`, x[`${key}_title`] ?? null]])) : {}),
            entries: x.entries.map((e: any) => {
                const offDays = WEEK_ORDER.filter((d) => e.off.includes(d)).map((d) => WEEKDAYS[d]);
                return {
                    ...(e.id ? { id: e.id } : {}),
                    type_of_leave: e.type_of_leave,
                    inclusive_dates: e.inclusive_dates,
                    // Saturdays and Sundays are the default; "None" when every day counts
                    off_days: offDays.join() === 'Saturday,Sunday' ? null : offDays.length ? offDays : ['None'],
                    number_of_working_days_applied_for: workingDays(e),
                    is_half_day: e.is_half_day,
                    half_day_period: e.is_half_day ? e.half_day_period : null,
                    within_philippines: e.within_philippines,
                    purpose: e.purpose,
                    in_hospital: e.in_hospital,
                    illness: e.illness,
                    completion_type: e.completion_type,
                };
            }),
        };
    }
    if (tab.value === 'pass-slips') {
        x.expected_return_time = x.return === 'time' ? x.expected_return_time : x.return;
        delete x.return;
        delete x.newDate;
    }
    return x;
}

function save() {
    const id = editing.value ? (tab.value === 'leaves' ? editing.value.control_no : editing.value.id) : undefined;
    form.transform(() => ({ values: payload() })).post(route('profile.filings.save', { module: tab.value, id }), {
        preserveScroll: true,
        onSuccess: () => { show.value = false; },
    });
}

const serverErrors = computed(() => Object.values(form.errors));
const input = 'w-full rounded border-gray-300 text-xs dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 focus:border-emerald-500 focus:ring-emerald-500';
const label = 'block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1';
const chip = 'inline-flex items-center gap-1 rounded bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-[11px] text-gray-700 dark:text-gray-200';
const check = 'rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500';
</script>

<template>
    <section>
        <h4 class="flex items-center gap-2 mb-2.5 text-[11px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300"><i class="fas fa-folder-open text-emerald-600 dark:text-emerald-400"></i>My records in {{ records.year }}</h4>
        <!-- Hidden, not removed, on the read-only Travel Orders tab so the tabs don't jump -->
        <div class="mb-3" :class="{ invisible: current.readonly }">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 transition-colors" :disabled="current.readonly" @click="open()">
                <i class="fas fa-plus"></i>File {{ SINGULAR[module] ?? 'Leave' }}
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 dark:border-gray-700 mb-3">
            <button
                v-for="t in TABS" :key="t.key" type="button"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border-b-2 -mb-px transition-colors"
                :class="tab === t.key ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                @click="tab = t.key"
            >
                <i class="fas" :class="t.icon"></i>{{ t.label }}
                <span
                    class="min-w-[1.25rem] rounded-full px-1.5 py-px text-center text-[10px] font-bold tabular-nums"
                    :class="tab === t.key ? 'bg-emerald-600 text-white dark:bg-emerald-400 dark:text-gray-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
                >{{ rows(t).length }}</span>
            </button>
        </div>

        <p v-if="!rows(current).length" class="rounded border border-dashed border-gray-300 dark:border-gray-600 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-inbox block mb-2 text-lg text-gray-300 dark:text-gray-600"></i>No {{ current.label.toLowerCase() }} in {{ records.year }}.
        </p>
        <div v-else class="overflow-x-auto rounded border border-gray-200 dark:border-gray-700">
            <table class="w-full min-w-[760px] text-left text-xs border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th v-for="h in HEADERS[tab]" :key="h" :class="[LIST_HEAD, h === 'Days' ? 'text-right' : '']">{{ h }}</th>
                        <th v-if="!current.readonly" class="text-center" :class="LIST_HEAD">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in rows(current)" :key="row.id ?? row.control_no"
                        class="transition-colors [&>td]:border-t [&>td]:border-t-gray-200 dark:[&>td]:border-t-gray-700 [&>td]:px-3 [&>td]:py-2 [&>td]:align-top"
                        :class="listRowClass(index)"
                    >
                        <td class="font-semibold text-gray-900 dark:text-gray-100 tabular-nums whitespace-nowrap">{{ row.control_no }}</td>
                        <!-- Highlights as in the module lists: leave types purple, half days orange, dates blue -->
                        <template v-if="tab === 'leaves'">
                            <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(row.date_of_filing) }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="e in row.entries" :key="e.id" :class="[TAG, TAG_TYPE]">
                                        {{ e.type_of_leave }}<span v-if="row.entries.length > 1" class="font-normal opacity-80">· {{ e.is_half_day ? 0.5 : e.number_of_working_days_applied_for }}d</span>
                                        <span v-if="e.is_half_day" :class="[TAG, TAG_HALF, '!py-0 !px-1.5']">{{ e.half_day_period }}</span>
                                    </span>
                                </div>
                            </td>
                            <td><div class="flex flex-wrap gap-1"><span v-for="(d, i) in row.entries.flatMap((e: any) => e.inclusive_dates ?? [])" :key="i" :class="[TAG, TAG_DATE]">{{ formatDates([d]) }}</span></div></td>
                            <td class="text-right tabular-nums font-semibold text-gray-900 dark:text-gray-100">{{ formatDays(row.entries.reduce((sum: number, e: any) => sum + (e.is_half_day ? 0.5 : Number(e.number_of_working_days_applied_for) || 0), 0)) }}</td>
                        </template>
                        <template v-else-if="tab === 'pass-slips'">
                            <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(row.date) }}</td>
                            <td><div class="flex flex-wrap gap-1"><span v-for="d in (row.inclusive_dates?.length ? row.inclusive_dates : [row.date])" :key="d" :class="[TAG, TAG_DATE]">{{ formatDates([d]) }}</span></div></td>
                            <td class="whitespace-nowrap font-medium text-gray-800 dark:text-gray-200">{{ formatTime(row.requested_time) }} – {{ formatTime(row.expected_return_time) }}</td>
                            <td class="max-w-xs"><div class="text-gray-800 dark:text-gray-100 break-words">{{ row.purpose }}</div><div v-if="row.location" class="text-[11px] text-gray-500 dark:text-gray-400 break-words"><i class="fas fa-location-dot mr-1"></i>{{ row.location }}</div></td>
                        </template>
                        <template v-else-if="tab === 'time-slips'">
                            <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(row.date) }}</td>
                            <td v-for="half in [['time_in_am', 'time_out_am'], ['time_in_pm', 'time_out_pm']]" :key="half[0]" class="whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <template v-for="(field, i) in half" :key="field">
                                        <span v-if="i" class="text-gray-400">–</span>
                                        <span v-if="row[field]" :class="[TAG, TAG_TIME]">{{ formatTime(row[field]) }}</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </template>
                                </div>
                            </td>
                            <td class="max-w-xs break-words text-gray-800 dark:text-gray-200">{{ row.reason }}</td>
                        </template>
                        <template v-else-if="tab === 'tardiness'">
                            <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(row.date_filed) }}</td>
                            <td class="whitespace-nowrap"><span :class="[TAG, row.type === 'Tardiness' ? TAG_TARDY : TAG_UNDERTIME]">{{ row.type }}</span></td>
                            <td class="whitespace-nowrap">
                                <span :class="[TAG, TAG_DATE]">{{ formatDate(row.requested_date) }}</span>
                                <span class="ml-1 font-medium text-gray-800 dark:text-gray-200">{{ formatTime(row.requested_time) }}<template v-if="row.return_time"> – {{ formatTime(row.return_time) }}</template></span>
                            </td>
                            <td class="max-w-xs break-words text-gray-800 dark:text-gray-200">{{ row.reason }}</td>
                        </template>
                        <template v-else>
                            <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ formatDate(row.date) }}</td>
                            <td><div class="flex flex-wrap gap-1"><span v-for="d in row.inclusive_dates ?? []" :key="d" :class="[TAG, TAG_DATE]">{{ formatDates([d]) }}</span></div></td>
                            <td class="max-w-xs break-words text-gray-800 dark:text-gray-200">{{ row.going_to || '—' }}</td>
                            <td class="max-w-xs"><div class="flex flex-wrap gap-1"><span v-for="p in [].concat(row.purpose ?? [])" :key="p" :class="[TAG, TAG_PURPOSE, 'break-words']">{{ p }}</span></div></td>
                        </template>
                        <td v-if="!current.readonly">
                            <div class="flex items-center justify-center gap-2">
                                <RowIconButton color="purple" icon="fas fa-print" tooltip="View/Print" @click="openPrint(row)" />
                                <RowIconButton v-if="row.editable" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click="open(row)" />
                                <span v-else class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 cursor-help" title="A pass slip naming other employees too: change it on the Pass Slips page."><i class="fas fa-lock"></i></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- File / Edit: closes only with Cancel, × or a double click outside, so typed entries are not lost -->
        <Teleport to="body">
            <div v-if="show" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center overflow-y-auto bg-black/50 p-4" @dblclick.self="show = false">
                <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-gray-800 shadow-lg animate-scaleInUp">
                    <!-- Header and footer as the module modals have them -->
                    <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-file-signature text-emerald-600 dark:text-emerald-400"></i>
                            {{ editing ? 'Edit' : 'File' }} {{ SINGULAR[module] }}<template v-if="editing"> {{ editing.control_no }}</template>
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" aria-label="Close" @click="show = false"><i class="fas fa-times text-xl"></i></button>
                    </div>

                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div v-if="serverErrors.length" class="text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded px-3 py-2 mb-4">
                            <p v-for="message in serverErrors" :key="message">{{ message }}</p>
                        </div>

                        <!-- Leave: one or more leave types, each with its dates and off days -->
                        <template v-if="tab === 'leaves'">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-3"><i class="fas fa-circle-info mr-1"></i>The control number is assigned when saved.</p>
                            <div v-for="(entry, i) in form.values.entries" :key="i" class="mb-3 rounded border border-gray-200 dark:border-gray-700 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-100">Leave {{ i + 1 }}</span>
                                    <button v-if="form.values.entries.length > 1" type="button" class="p-2 rounded-lg transition-colors text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50" title="Remove this leave type" aria-label="Remove this leave type" @click="form.values.entries.splice(i, 1)"><i class="fas fa-trash-alt"></i></button>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2"><label :class="label">Type of Leave <span class="text-red-500">*</span></label>
                                        <select v-model="entry.type_of_leave" :class="input"><option value="">—</option><option v-for="t in records.leave_types" :key="t" :value="t">{{ t }}</option></select></div>

                                    <div class="col-span-2"><label :class="label">Inclusive Dates <span class="text-red-500">*</span> <span class="font-normal">(a day, or a range with a last day)</span></label>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input v-model="entry.from" type="date" :class="input" class="!w-40" aria-label="First day" />
                                            <span class="text-xs text-gray-500">to</span>
                                            <input v-model="entry.to" type="date" :min="entry.from" :class="input" class="!w-40" aria-label="Last day (optional)" />
                                            <button type="button" :class="MODAL_ADD" :disabled="!entry.from" @click="addLeaveDates(entry)"><i class="fas fa-plus"></i>Add</button>
                                        </div>
                                        <div v-if="entry.inclusive_dates.length" class="mt-1.5 flex flex-wrap gap-1">
                                            <span v-for="d in entry.inclusive_dates" :key="d" :class="chip">{{ formatDates([d]) }}
                                                <button type="button" :aria-label="`Remove ${d}`" @click="entry.inclusive_dates = entry.inclusive_dates.filter((x: string) => x !== d)"><i class="fas fa-xmark"></i></button></span>
                                        </div>
                                    </div>

                                    <div class="col-span-2"><label :class="label">Off days <span class="font-normal">(not counted; Saturdays and Sundays unless changed)</span></label>
                                        <div class="flex flex-wrap gap-x-3 gap-y-1">
                                            <label v-for="d in WEEK_ORDER" :key="d" class="text-xs flex items-center gap-1 text-gray-700 dark:text-gray-200"><input type="checkbox" :class="check" :checked="entry.off.includes(d)" @change="toggleOff(entry, d)" />{{ WEEKDAYS[d].slice(0, 3) }}</label>
                                        </div>
                                    </div>

                                    <div><label :class="label">Number of Working Days <span class="text-red-500">*</span></label>
                                        <input :value="entry.is_half_day ? 0.5 : workingDays(entry)" type="number" readonly tabindex="-1" :class="input" class="bg-gray-100 dark:!bg-gray-800 cursor-not-allowed" /></div>
                                    <div class="flex items-end gap-3 pb-1">
                                        <label class="text-xs flex items-center gap-1.5 text-gray-700 dark:text-gray-200"><input v-model="entry.is_half_day" type="checkbox" :class="check" />Half day</label>
                                        <select v-if="entry.is_half_day" v-model="entry.half_day_period" :class="input" class="!w-20"><option value="">—</option><option>AM</option><option>PM</option></select>
                                    </div>

                                    <template v-if="LOCATION_TYPES.includes(entry.type_of_leave)">
                                        <div><label :class="label">Where</label><select v-model="entry.within_philippines" :class="input"><option :value="true">Within the Philippines</option><option :value="false">Abroad</option></select></div>
                                        <div><label :class="label">Specify</label><input v-model="entry.purpose" type="text" maxlength="500" :class="input" /></div>
                                    </template>
                                    <template v-if="entry.type_of_leave === 'Sick Leave'">
                                        <div><label :class="label">Where</label><select v-model="entry.in_hospital" :class="input"><option :value="true">In hospital</option><option :value="false">Out patient</option></select></div>
                                        <div><label :class="label">Illness</label><input v-model="entry.illness" type="text" maxlength="500" :class="input" /></div>
                                    </template>
                                    <div v-if="entry.type_of_leave === 'Special Leave Benefits for Women'" class="col-span-2"><label :class="label">Illness</label><input v-model="entry.illness" type="text" maxlength="500" :class="input" /></div>
                                    <div v-if="entry.type_of_leave === 'Study Leave'" class="col-span-2"><label :class="label">Completion type</label>
                                        <select v-model="entry.completion_type" :class="input"><option value="">—</option><option>Completion of Master's Degree</option><option>BAR/Board Examination Review</option></select></div>
                                </div>
                            </div>
                            <button type="button" :class="MODAL_ADD_OUTLINE" @click="form.values.entries.push(blankEntry())"><i class="fas fa-plus"></i> Add another leave type</button>

                            <!-- 7.B Recommendation of CS Form No. 6: For approval unless changed -->
                            <div class="mt-4 rounded border border-gray-200 dark:border-gray-700 p-3">
                                <label :class="label">Recommendation <span class="text-red-500">*</span></label>
                                <div class="flex flex-wrap gap-x-5 gap-y-1">
                                    <label class="text-xs flex items-center gap-1.5 text-gray-700 dark:text-gray-200"><input v-model="form.values.recommendation" type="radio" value="approval" class="text-emerald-600 focus:ring-emerald-500" />For approval</label>
                                    <label class="text-xs flex items-center gap-1.5 text-gray-700 dark:text-gray-200"><input v-model="form.values.recommendation" type="radio" value="disapproval" class="text-emerald-600 focus:ring-emerald-500" />For disapproval</label>
                                </div>
                                <div v-if="form.values.recommendation === 'disapproval'" class="mt-3">
                                    <label :class="label">Reason for Disapproval <span class="text-red-500">*</span></label>
                                    <textarea v-model="form.values.disapproval_reason" rows="2" maxlength="500" :class="input"></textarea>
                                </div>
                            </div>

                            <!-- Signatories printed on the form (7.A, 7.B, authorized official): the current officers unless an OIC or Acting signs -->
                            <div v-if="leaveSignatories" class="mt-4 rounded border border-gray-200 dark:border-gray-700 p-3 space-y-3">
                                <div>
                                    <span :class="label">Signatories</span>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400"><i class="fas fa-circle-info mr-1"></i>Change the title and name when an OIC or Acting officer signs.</p>
                                </div>
                                <LeaveActingHeadCheckbox :form-data="form.values" :employees="leaveSignatories.employees" :applicant="leaveSignatories.applicant" />
                                <LeaveSignatoryField
                                    v-for="signatory in LEAVE_SIGNATORIES"
                                    :key="signatory.key"
                                    :signatory="signatory.key"
                                    :employees="leaveSignatories.employees"
                                    :applicant="leaveSignatories.applicant"
                                    :employee-id="form.values[`${signatory.key}_employee_id`]"
                                    :title="form.values[`${signatory.key}_title`]"
                                    @change="(value) => { form.values[`${signatory.key}_employee_id`] = value.employeeId; form.values[`${signatory.key}_title`] = value.title; }"
                                />
                            </div>
                        </template>

                        <template v-else>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-3"><i class="fas fa-circle-info mr-1"></i>The control number is assigned when saved.</p>

                            <div v-if="tab === 'pass-slips'" class="grid grid-cols-2 gap-3">
                                <div><label :class="label">Date <span class="text-red-500">*</span></label><input v-model="form.values.date" type="date" :class="input" /></div>
                                <div><label :class="label">Requested Time <span class="text-red-500">*</span></label><input v-model="form.values.requested_time" type="time" :class="input" /></div>
                                <div class="col-span-2"><label :class="label">Inclusive Dates</label>
                                    <div class="flex gap-2"><input v-model="form.values.newDate" type="date" :class="input" class="!w-40" aria-label="Inclusive date" /><button type="button" :class="MODAL_ADD" :disabled="!form.values.newDate" @click="addSlipDate"><i class="fas fa-plus"></i>Add</button></div>
                                    <div v-if="form.values.inclusive_dates.length" class="mt-1.5 flex flex-wrap gap-1">
                                        <span v-for="d in form.values.inclusive_dates" :key="d" :class="chip">{{ formatDate(d) }}
                                            <button type="button" :aria-label="`Remove ${d}`" @click="form.values.inclusive_dates = form.values.inclusive_dates.filter((x: string) => x !== d)"><i class="fas fa-xmark"></i></button></span>
                                    </div>
                                </div>
                                <div class="col-span-2"><label :class="label">Purpose <span class="text-red-500">*</span></label><input v-model="form.values.purpose" type="text" maxlength="255" :class="input" /></div>
                                <div class="col-span-2"><label :class="label">Location</label><input v-model="form.values.location" type="text" maxlength="255" :class="input" /></div>
                                <div><label :class="label">Expected return</label>
                                    <select v-model="form.values.return" :class="input"><option value="time">At a time</option><option v-for="w in RETURN_WORDS" :key="w" :value="w">{{ w }}</option></select></div>
                                <div v-if="form.values.return === 'time'"><label :class="label">Expected Time of Return <span class="text-red-500">*</span></label><input v-model="form.values.expected_return_time" type="time" :class="input" /></div>
                                <div><label :class="label">Vehicle</label><select v-model="form.values.vehicle" :class="input"><option>RP Vehicle</option><option>PUJ</option></select></div>
                                <div class="col-span-2"><label :class="label">Remarks</label><input v-model="form.values.remarks" type="text" :class="input" /></div>
                            </div>

                            <div v-else-if="tab === 'time-slips'" class="grid grid-cols-2 gap-3">
                                <div class="col-span-2"><label :class="label">Date <span class="text-red-500">*</span></label><input v-model="form.values.date" type="date" :class="input" /></div>
                                <p class="col-span-2 -mb-1 text-[11px] text-gray-500 dark:text-gray-400">At least one time <span class="text-red-500">*</span></p>
                                <div><label :class="label">Time in (AM)</label><input v-model="form.values.time_in_am" type="time" :class="input" /></div>
                                <div><label :class="label">Time out (AM)</label><input v-model="form.values.time_out_am" type="time" :class="input" /></div>
                                <div><label :class="label">Time in (PM)</label><input v-model="form.values.time_in_pm" type="time" :class="input" /></div>
                                <div><label :class="label">Time out (PM)</label><input v-model="form.values.time_out_pm" type="time" :class="input" /></div>
                                <div class="col-span-2"><label :class="label">Reason <span class="text-red-500">*</span></label><textarea v-model="form.values.reason" rows="2" maxlength="500" :class="input"></textarea></div>
                            </div>

                            <div v-else class="grid grid-cols-2 gap-3">
                                <div><label :class="label">Type <span class="text-red-500">*</span></label><select v-model="form.values.type" :class="input"><option>Tardiness</option><option>Undertime</option></select></div>
                                <div><label :class="label">Requested Date <span class="text-red-500">*</span></label><input v-model="form.values.requested_date" type="date" :class="input" /></div>
                                <div><label :class="label">Requested Time <span class="font-normal">({{ form.values.type === 'Tardiness' ? 'time in' : 'time out' }})</span> <span class="text-red-500">*</span></label><input v-model="form.values.requested_time" type="time" :class="input" /></div>
                                <div v-if="form.values.type === 'Undertime'"><label :class="label">Return time <span class="font-normal">(if any)</span></label><input v-model="form.values.return_time" type="time" :class="input" /></div>
                                <div class="col-span-2"><label :class="label">Reason <span class="text-red-500">*</span></label><textarea v-model="form.values.reason" rows="2" maxlength="500" :class="input"></textarea></div>
                            </div>

                            <!-- Signatories: a supervisor (or none, straight to the approver) and the approving Provincial Budget Officer -->
                            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-3">
                                <p v-if="signatories.governor_approves && tab !== 'time-slips'" class="col-span-2 text-xs italic text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i>The Provincial Governor recommends and approves the Provincial Budget Officer's own {{ tab === 'pass-slips' ? 'pass slips' : 'tardiness/undertime' }}.
                                </p>
                                <template v-else>
                                    <div class="col-span-2"><label :class="label">{{ supervisorLabel }}</label>
                                        <select v-model="form.values[supervisorField]" :class="input">
                                            <option :value="null">None — straight to the approving officer</option>
                                            <option v-for="s in signatories.supervisors" :key="s.id" :value="s.id">{{ s.name }}{{ s.designation ? ` — ${s.designation}` : '' }}</option>
                                        </select></div>
                                    <div><label :class="label">Approved by (title)</label>
                                        <select v-model="form.values.approved_by_title" :class="input"><option v-for="t in signatories.titles" :key="t" :value="t">{{ t }}</option></select></div>
                                    <div><label :class="label">Approved by (name)</label>
                                        <select v-model="form.values.approved_by_employee_id" :class="input"><option v-for="a in signatories.approvers" :key="a.id" :value="a.id">{{ a.name }}</option></select></div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Centered: File / Save first, then Cancel, and why the button is disabled, as on the module forms -->
                    <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button type="button" :class="MODAL_SUBMIT" :disabled="!canSubmit || form.processing" @click="save">
                            <i class="fas" :class="form.processing ? 'fa-spinner fa-spin' : editing ? 'fa-save' : 'fa-paper-plane'"></i>
                            {{ form.processing ? (editing ? 'Updating...' : 'Filing...') : (editing ? 'Update' : 'File') }}
                        </button>
                        <button type="button" :class="MODAL_CANCEL" @click="show = false">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <MissingFieldsHint :fields="missingFields" :message="submitMessage" />
                    </div>
                </div>
            </div>

            <!-- View/Print: the module's own preview of the filing, full screen in a see-through frame, so its backdrop is the only one -->
            <div v-if="printing && !printing.url" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" @dblclick.self="printing = null">
                <div class="w-full max-w-md rounded-lg bg-white dark:bg-gray-800 shadow-lg p-6 text-center text-xs text-gray-700 dark:text-gray-200">
                    <template v-if="printing.loading"><i class="fas fa-spinner fa-spin mr-1"></i>Opening the print preview of {{ printing.title }}…</template>
                    <template v-else>
                        <p class="text-red-700 dark:text-red-300 mb-3"><i class="fas fa-triangle-exclamation mr-1"></i>{{ printing.error }}</p>
                        <button type="button" :class="MODAL_CANCEL" @click="printing = null"><i class="fas fa-times"></i>Close</button>
                    </template>
                </div>
            </div>
            <!-- color-scheme: normal on both sides keeps the frame see-through in dark mode too -->
            <iframe v-else-if="printing" :src="printing.url ?? undefined" :title="`Print preview of ${printing.title}`" class="fixed inset-0 z-[60] w-full h-full border-0 bg-transparent" style="color-scheme: normal"></iframe>
        </Teleport>
    </section>
</template>
