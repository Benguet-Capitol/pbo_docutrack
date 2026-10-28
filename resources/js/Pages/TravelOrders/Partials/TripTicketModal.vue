<template>
    <Teleport to="body" v-if="show && order">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-car text-emerald-600 dark:text-emerald-400"></i>
                            Trip Ticket Preview
                        </h3>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1 text-xs bg-white/60 dark:bg-gray-800/60 rounded-lg p-1">
                                <button
                                    type="button"
                                    @click="paperSize = 'short'"
                                    :class="paperSize === 'short' ? 'bg-emerald-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-md font-medium transition-colors"
                                >
                                    8.5 x 11 (Short)
                                </button>
                                <button
                                    type="button"
                                    @click="paperSize = 'long'"
                                    :class="paperSize === 'long' ? 'bg-emerald-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-md font-medium transition-colors"
                                >
                                    8.5 x 13 (Long)
                                </button>
                            </div>
                            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <p v-if="ticketError" class="print:hidden px-6 pt-3 text-xs text-red-600 dark:text-red-400">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        {{ ticketError }}
                    </p>

                    <!-- Modal Body - Document Preview (same layout as the Travel Order); the whole sheet, its margins inside, like the Leave preview; only this page is printed -->
                    <div class="p-4 bg-gray-100 dark:bg-gray-900 overflow-x-auto">
                        <div
                            id="preview-content"
                            class="mx-auto flex flex-col bg-white shadow"
                            data-print-page-only
                            data-page-width-in="8.5"
                            :data-page-height-in="PAGE_HEIGHT_IN[paperSize]"
                            data-page-margin-in="0"
                            :style="{ boxSizing: 'border-box', width: '8.5in', minHeight: `${PAGE_HEIGHT_IN[paperSize]}in`, padding: `${PAGE_MARGIN_IN}in` }"
                        >
                            <!-- Header Section with Logos -->
                            <div class="flex items-center justify-center gap-2 mb-2 pb-1" style="border-bottom: 3px double #050505;">
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                                    <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                    <p class="text-lg font-bold text-gray-900" v-if="isApproverProvincialGovernor">OFFICE OF THE GOVERNOR</p>
                                    <p class="text-lg font-bold text-gray-900" v-else>PROVINCIAL BUDGET OFFICE</p>
                                    <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                                </div>
                                <div style="width: 85px; flex-shrink: 0;">
                                    <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                                </div>
                            </div>

                            <div>
                                <p class="font-bold text-xl text-center text-gray-900 mb-1">DRIVER'S TRIP TICKET</p>
                            </div>

                            <!-- Control No. & Date -->
                            <div class="mb-2 flex justify-end gap-6 pr-8 text-[11px]">
                                <p>
                                    <span>CONTROL NO.: </span>
                                    <span class="font-bold text-gray-900 w-36 border-b border-gray-900 inline-block text-center">
                                        <template v-if="controlNo">{{ controlNo }}</template>
                                        <i v-else-if="loadingTicket" class="fas fa-spinner fa-spin text-gray-400"></i>
                                        <template v-else>&nbsp;</template>
                                    </span>
                                </p>
                                <p>
                                    <span>DATE: </span>
                                    <span class="font-bold text-gray-900 w-40 border-b border-gray-900 inline-block text-center">
                                        {{ ticketDate }}
                                    </span>
                                </p>
                            </div>

                            <!-- A. Authorizing official -->
                            <div class="text-[11px] leading-[14px] text-gray-900 mb-2">
                                <p class="font-bold mb-1">A. TO BE FILLED UP BY THE OFFICIAL/ADMINISTRATIVE OFFICER AUTHORIZING TRAVEL</p>
                                <!-- The label column is as wide as the longest label, so the values get the rest of the line -->
                                <div class="grid grid-cols-[max-content_1fr] items-end gap-x-2 gap-y-px pl-4">
                                    <template v-for="item in sectionA" :key="item.label">
                                        <p>{{ item.label }}</p>
                                        <p class="border-b border-gray-900 font-semibold">{{ item.value || '&nbsp;' }}</p>
                                    </template>
                                    <div class="contents">
                                        <p class="self-start">6. Purpose:</p>
                                        <p class="self-start">
                                            <span class="border-b border-gray-900 font-semibold" style="display: inline; box-decoration-break: clone; -webkit-box-decoration-break: clone; line-height: 1.6;">{{ purposeText }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Approved -->
                            <div class="flex justify-end pr-8 mb-2">
                                <div class="w-72 text-center">
                                    <p class="text-[11px] text-left text-gray-900 mb-4">APPROVED:</p>
                                    <p class="text-sm text-center text-gray-900 border-b border-gray-900 pb-1 font-bold uppercase">{{ approverName }}</p>
                                    <p class="text-[11px] text-center text-gray-700">{{ approverRole }}</p>
                                </div>
                            </div>

                            <!-- B. Driver -->
                            <div class="text-[11px] leading-[14px] text-gray-900 mb-2">
                                <p class="font-bold mb-1">B. TO BE FILLED UP BY THE DRIVER</p>
                                <div class="space-y-px pl-4">
                                    <div v-for="label in TIMES" :key="label" class="flex items-end gap-2">
                                        <p class="w-80 flex-shrink-0">{{ label }}</p>
                                        <p>:</p>
                                        <p class="w-24 border-b border-gray-900">&nbsp;</p><p>am</p>
                                        <p class="w-24 border-b border-gray-900 ml-4">&nbsp;</p><p>pm</p>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <p class="w-80 flex-shrink-0">5. Approximate distance travelled (to and from station)</p>
                                        <p>:</p>
                                        <p class="w-24 border-b border-gray-900">&nbsp;</p><p>km</p>
                                    </div>
                                    <p>6. Gasoline issued, purchased and consumed</p>
                                    <div v-for="item in GASOLINE" :key="item.label" class="flex items-end gap-2 pl-4" :class="{ 'font-bold': item.total }">
                                        <p class="w-76 flex-shrink-0">{{ item.label }}</p>
                                        <p>:</p>
                                        <p class="w-24 border-b border-gray-900">&nbsp;</p><p>liters</p>
                                    </div>
                                    <div v-for="item in OILS" :key="item.label" class="flex items-end gap-2">
                                        <p class="w-80 flex-shrink-0">{{ item.label }}</p>
                                        <p>:</p>
                                        <p class="w-24 border-b border-gray-900">&nbsp;</p><p>{{ item.unit }}</p>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <p class="flex-shrink-0">12. REMARKS:</p>
                                        <p class="flex-1 border-b border-gray-900">&nbsp;</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Certification -->
                            <div class="text-[11px] leading-[14px] text-gray-900 mb-2 pt-2" style="border-top: 3px double #050505;">
                                <p class="text-sm font-bold text-center mb-1">CERTIFICATION</p>
                                <!-- Driver signature beside the statement -->
                                <div class="flex items-end justify-between gap-4 pr-8 mb-1">
                                    <p class="indent-8 self-start">I HEREBY CERTIFY to the correctness of the above statement.</p>
                                    <div class="w-72 flex-shrink-0 text-center pt-5">
                                        <p class="text-sm border-b border-gray-900 pb-0.5 font-bold uppercase">{{ order.driver }}</p>
                                        <p class="text-gray-700">Driver</p>
                                    </div>
                                </div>
                                <p class="indent-8">WE HEREBY CERTIFY that we used this car for official business as stated above.</p>
                                <!-- Every passenger signs on a full-width line (more lines only for many passengers); the generated-on mark sits beside the caption -->
                                <div
                                    v-for="(row, index) in passengerLayout.rows"
                                    :key="index"
                                    class="mt-5 flex border-b border-gray-900 pb-0.5 font-bold uppercase whitespace-nowrap"
                                    :class="row.length <= 3 ? 'justify-center gap-x-12' : 'justify-evenly gap-x-4'"
                                    :style="{ fontSize: `${passengerLayout.fontPx}px` }"
                                >
                                    <span v-for="name in row" :key="name">{{ name }}</span>
                                </div>
                                <div class="relative">
                                    <p class="text-center text-gray-700">Passenger/s</p>
                                    <div class="absolute right-0 top-0 text-right" style="font-size: 9px; line-height: 1.2; color: #555;">
                                        <p class="m-0">Generated on {{ printGeneratedAtDisplay() }}</p>
                                        <p class="m-0 font-bold">PBO|DocuTrack</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-auto">
                                <div style="border-top: 3px double #050505;">
                                    <div class="flex justify-between items-center mt-2" style="line-height: 1;">
                                        <p v-if="!isApproverProvincialGovernor"><span class="font-semibold text-xs">PBO Telephone No.:</span> <span class="text-xs text-gray-900">(074) 422-1378, Local: 134</span></p>
                                        <p v-else class="whitespace-nowrap"><span class="font-semibold text-xs">Benguet Capitol Trunklines:</span> <span class="text-xs text-gray-900">(074) 422-5657; 422-1116; 422-2306; 422-5760; Local: 134</span></p>
                                        <p><span class="font-semibold text-xs">Website:</span> <span class="text-xs font-semibold text-blue-800 underline">www.benguet.gov.ph</span></p>
                                    </div>
                                    <p><span class="font-semibold text-xs">Email Address:</span> <span class="text-xs font-semibold text-blue-800 underline">benguetpbo@benguet.gov.ph</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                        <button @click="printSafely()" :disabled="!controlNo" class="disabled:opacity-50 disabled:pointer-events-none inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { isProvincialGovernor } from '@/Composables/useOfficials';
import { printSafely } from '@/Composables/usePrint';
import type { Employee, TravelOrder } from '../Composables/useTravelOrdersData';
import { longDate, passengerText, tripDatesText } from '../Composables/tripTicket';

const props = defineProps<{
    show: boolean;
    order: TravelOrder | null;
    sortedEmployees: Employee[];
}>();

defineEmits<{
    close: [];
}>();

const TIMES = [
    '1. Time of departure from the office/garage/station',
    '2. Time of arrival at destination',
    '3. Time of departure from destination',
    '4. Time of arrival (back to station)',
];

const GASOLINE = [
    { label: 'a. Balance in tank', total: false },
    { label: 'b. Issued by office from stock', total: false },
    { label: 'c. Add; purchased during the trip', total: false },
    { label: 'TOTAL', total: true },
    { label: 'd. Less; used during the trip', total: false },
    { label: 'e. Balance in tank after the trip', total: false },
];

const OILS = [
    { label: '7. Gear oil used or issued', unit: 'gals/qrts' },
    { label: '8. Lubricating oil used or issued', unit: 'gals/qrts' },
    { label: '9. Grease issued', unit: 'gals/qrts' },
    { label: '10. At the beginning of trip', unit: 'km' },
    { label: '11. At the end of trip', unit: 'km' },
];

const PAGE_HEIGHT_IN = { short: 11, long: 13 } as const;
const PAGE_MARGIN_IN = 0.5;

const paperSize = ref<'short' | 'long'>('short');
const controlNo = ref('');
const loadingTicket = ref(false);
const ticketError = ref('');

// The trip ticket's date is the travel order's date
const ticketDate = computed(() => longDate(props.order?.date));

// The control no. is issued by the system the first time the ticket is opened ("2026-09-0012"), then kept
watch(() => [props.show, props.order?.id], async () => {
    controlNo.value = '';
    ticketError.value = '';
    if (!props.show || !props.order) return;

    const orderId = props.order.id;
    loadingTicket.value = true;
    try {
        const response = await fetch(`/api/travel-orders/${orderId}/trip-ticket`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        const data = await response.json().catch(() => ({}));
        if (props.order?.id !== orderId) return;
        if (!response.ok) throw new Error(data.error || 'Could not get the trip ticket number.');
        controlNo.value = data.control_no;
    } catch (e) {
        ticketError.value = e instanceof Error ? e.message : 'Could not get the trip ticket number.';
    } finally {
        loadingTicket.value = false;
    }
}, { immediate: true });

const passengers = computed(() => (props.order?.employees ?? []).map((employee) => employee.name));
// Passenger names on as few signature lines as possible: the font shrinks (14px down to 9px) until every
// name fits on one line without wrapping; only then, or past six names, is another line added (rows balanced)
const PASSENGER_LINE_PX = 720;
const PASSENGER_CHAR_EM = 0.6; // width of a bold uppercase character, in em
const passengerLayout = computed(() => {
    const names = passengers.value.map((name) => name.toUpperCase());
    for (let lines = 1; ; lines++) {
        const perLine = Math.ceil(names.length / lines);
        const rows = Array.from({ length: lines }, (_, i) => names.slice(i * perLine, (i + 1) * perLine)).filter((row) => row.length);
        const fontPx = Math.min(14, ...rows.map((row) => {
            const gaps = (row.length - 1) * (row.length <= 3 ? 48 : 16);
            const chars = row.reduce((sum, name) => sum + name.length, 0);
            return Math.floor(((PASSENGER_LINE_PX - gaps) / (chars * PASSENGER_CHAR_EM)) * 2) / 2;
        }));
        if ((fontPx >= 9 && perLine <= 6) || perLine <= 1) return { rows, fontPx: Math.max(fontPx, 9) };
    }
});

const purposeText = computed(() => (props.order?.purpose ?? []).join('; '));

const sectionA = computed(() => {
    const order = props.order!;
    return [
        { label: '1. Name of driver/operator of the vehicle:', value: order.driver ?? '' },
        { label: '2. Government car to be used with plate number:', value: order.plate_number ?? '' },
        { label: '3. Name of authorized passenger:', value: passengerText(passengers.value) },
        { label: '4. Inclusive dates of travel:', value: tripDatesText(order) },
        { label: '5. Places to be visited/inspected:', value: order.going_to ?? '' },
    ];
});

// Signed by whoever approved the travel order (the PBO within Benguet, the Governor for Baguio and outside Benguet)
const approver = computed(() => props.sortedEmployees.find((employee) => employee.id === props.order?.approver_employee_id));
const isActing = computed(() => !!props.order?.is_acting_approver);
const approverName = computed(() =>
    isActing.value && props.order?.acting_approver_name?.trim() ? props.order.acting_approver_name.trim() : approver.value?.name ?? '');
const approverRole = computed(() =>
    isActing.value && props.order?.acting_approver_designation?.trim() ? props.order.acting_approver_designation.trim() : approver.value?.designation ?? '');
const isApproverProvincialGovernor = computed(() =>
    isActing.value && props.order?.acting_approver_designation?.trim()
        ? props.order.acting_approver_designation.toLowerCase().includes('governor')
        : isProvincialGovernor(approver.value));

const printGeneratedAtDisplay = (): string => {
    const now = new Date();
    return now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        + ' at '
        + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
};
</script>

<style scoped>
.w-76 {
    width: 19rem;
}
</style>
