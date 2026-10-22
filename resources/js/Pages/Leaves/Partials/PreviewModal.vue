<template>
    <!-- CS Form No. 6 (Revised 2020) Application for Leave: print preview -->
    <Teleport to="body" v-if="show && leave">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10 print:hidden">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-alt text-emerald-600 dark:text-emerald-400"></i>
                        Application for Leave Preview
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Form (Long / Folio paper, portrait) -->
                <div class="p-4 bg-gray-100 dark:bg-gray-900 overflow-x-auto">
                    <div
                        id="preview-content"
                        class="cs-form mx-auto bg-white text-black shadow"
                        data-print-page-only
                        data-page-width-in="8.5"
                        data-page-height-in="13"
                        data-page-margin-in="0"
                    >
                        <p class="form-no">Civil Service Form No. 6<br>Revised 2020</p>

                        <!-- Agency header -->
                        <div class="agency">
                            <img src="/benguetlogo.png" alt="Province of Benguet seal" class="logo">
                            <div class="text-center">
                                <p>Republic of the Philippines</p>
                                <p class="font-bold">Provincial Government of Benguet</p>
                                <p>Poblacion, La Trinidad, Benguet</p>
                            </div>
                            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas logo" class="logo">
                        </div>

                        <h1 class="title">APPLICATION FOR LEAVE</h1>

                        <!-- 1-5: applicant -->
                        <table class="box">
                            <tbody>
                                <tr>
                                    <td class="w-[38%] align-top">
                                        <div class="grid grid-cols-1">
                                            <span class="label">1.&nbsp;&nbsp;OFFICE/DEPARTMENT</span>
                                            <span class="fill text-center uppercase">{{ officeName }}</span>
                                        </div>
                                    </td>
                                    <td class="align-top">
                                        <div class="grid grid-cols-[auto_1fr_1fr_1fr] gap-x-2">
                                            <span class="label">2.&nbsp;&nbsp;NAME :</span>
                                            <span class="label text-center font-normal">(Last)</span>
                                            <span class="label text-center font-normal">(First)</span>
                                            <span class="label text-center font-normal">(Middle)</span>
                                            <span></span>
                                            <span class="fill text-center uppercase">{{ name.last }}</span>
                                            <span class="fill text-center uppercase">{{ name.first }}</span>
                                            <span class="fill text-center uppercase">{{ name.middle }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-bottom">
                                        <div class="inline-row">
                                            <span class="label">3.&nbsp;&nbsp;DATE OF FILING</span>
                                            <span class="line flex-1 text-center uppercase">{{ longDate(leave.date_of_filing) }}</span>
                                        </div>
                                    </td>
                                    <td class="align-bottom">
                                        <div class="inline-row">
                                            <span class="label">4.&nbsp;&nbsp;POSITION</span>
                                            <span class="line flex-1 text-center uppercase">{{ employee?.designation || employee?.full_position || '' }}</span>
                                            <span class="label ml-2">5.&nbsp;&nbsp;SALARY</span>
                                            <span class="line w-24 text-center uppercase">{{ salaryText }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="bar">6.&nbsp;&nbsp;DETAILS OF APPLICATION</div>

                        <!-- 6.A / 6.B: items spread down the box, so the section fills the page -->
                        <div class="details-ab grow-details">
                            <!-- 6.A Type of leave -->
                            <div class="cell">
                                <p class="label">6.A&nbsp;&nbsp;TYPE OF LEAVE TO BE AVAILED OF</p>
                                <div v-for="item in FORM_LEAVE_TYPES" :key="item.type" class="option">
                                    <span class="check">{{ hasType(item.type) ? '✓' : '' }}</span>
                                    <span>{{ labelParts(item.label).name }} <span v-if="labelParts(item.label).citation" class="citation">{{ labelParts(item.label).citation }}</span></span>
                                </div>
                                <div>
                                    <p class="ml-5">Others:</p>
                                    <p class="ml-5 line w-[85%]">{{ othersText }}</p>
                                </div>
                            </div>

                            <!-- 6.B Details of leave -->
                            <div class="cell">
                                <p class="label">6.B&nbsp;&nbsp;DETAILS OF LEAVE</p>
                                <p class="italic">In case of Vacation/Special Privilege Leave:</p>
                                <div class="option ml-3">
                                    <span class="check">{{ locationDetails.within.checked ? '✓' : '' }}</span>
                                    <span>Within the Philippines <span class="line min-w-[9rem]">{{ locationDetails.within.texts[0] || '' }}</span></span>
                                </div>
                                <p v-for="(text, index) in locationDetails.within.texts.slice(1)" :key="index" class="ml-8 line w-[75%]">{{ text }}</p>
                                <div class="option ml-3">
                                    <span class="check">{{ locationDetails.abroad.checked ? '✓' : '' }}</span>
                                    <span>Abroad (Specify) <span class="line min-w-[10rem]">{{ locationDetails.abroad.texts[0] || '' }}</span></span>
                                </div>
                                <p v-for="(text, index) in locationDetails.abroad.texts.slice(1)" :key="index" class="ml-8 line w-[75%]">{{ text }}</p>
                                <p class="italic">In case of Sick Leave:</p>
                                <div class="option ml-3">
                                    <span class="check">{{ isSick && inHospital === true ? '✓' : '' }}</span>
                                    <span>In Hospital (Specify Illness) <span class="line min-w-[7rem]">{{ isSick && inHospital === true ? sickEntry?.illness : '' }}</span></span>
                                </div>
                                <div class="option ml-3">
                                    <span class="check">{{ isSick && inHospital === false ? '✓' : '' }}</span>
                                    <span>Out Patient (Specify Illness) <span class="line min-w-[7rem]">{{ isSick && inHospital === false ? sickEntry?.illness : '' }}</span></span>
                                </div>
                                <p class="italic">In case of Special Leave Benefits for Women:</p>
                                <p class="ml-3">(Specify Illness) <span class="line min-w-[10rem]">{{ entryOf('Special Leave Benefits for Women')?.illness || '' }}</span></p>
                                <p class="italic">In case of Study Leave:</p>
                                <div class="option ml-3">
                                    <span class="check">{{ entryOf('Study Leave')?.completion_type === `Completion of Master's Degree` ? '✓' : '' }}</span>
                                    <span>Completion of Master's Degree</span>
                                </div>
                                <div class="option ml-3">
                                    <span class="check">{{ entryOf('Study Leave')?.completion_type === 'BAR/Board Examination Review' ? '✓' : '' }}</span>
                                    <span>BAR/Board Examination Review</span>
                                </div>
                                <p class="italic">Other purpose:</p>
                                <div class="option ml-3">
                                    <span class="check">{{ entryOf('Others')?.other_type === 'Monetization of Leave Credits' ? '✓' : '' }}</span>
                                    <span>Monetization of Leave Credits</span>
                                </div>
                                <div class="option ml-3">
                                    <span class="check">{{ entryOf('Others')?.other_type === 'Terminal Leave' ? '✓' : '' }}</span>
                                    <span>Terminal Leave</span>
                                </div>
                            </div>
                        </div>

                        <!-- 6.C / 6.D: compact, top-aligned -->
                        <div class="details-ab grow-details-cd">
                            <!-- 6.C Working days -->
                            <div class="cell">
                                <p class="label">6.C&nbsp;&nbsp;NUMBER OF WORKING DAYS APPLIED FOR</p>
                                <p class="ml-5 mt-1 line w-[85%] text-center">{{ applicationDaysText(entries) }}</p>
                                <p class="ml-5 mt-2 label font-normal">INCLUSIVE DATES</p>
                                <!-- One line per leave type -->
                                <p v-for="(line, index) in applicationDateLines(entries)" :key="index" class="ml-5 line w-[85%] text-center" :class="{ 'mt-1': index > 0 }">{{ line }}</p>
                                <!-- Left-aligned under the dates, like the Excel form -->
                                <p class="ml-5 mt-1">
                                    <span class="label font-normal">OFF DAYS:</span>&nbsp;
                                    <span class="font-bold">{{ offDaysLine }}</span>
                                </p>
                            </div>

                            <!-- 6.D Commutation -->
                            <div class="cell">
                                <p class="label">6.D&nbsp;&nbsp;COMMUTATION</p>
                                <div class="option ml-3 mt-1">
                                    <span class="check">✓</span>
                                    <span>Not Requested</span>
                                </div>
                                <div class="option ml-3">
                                    <span class="check"></span>
                                    <span>Requested</span>
                                </div>
                                <div class="signature">
                                    <span class="sig-line"></span>
                                    <span>(Signature of Applicant)</span>
                                </div>
                            </div>
                        </div>

                        <div class="bar">7.&nbsp;&nbsp;DETAILS OF ACTION ON APPLICATION</div>

                        <!-- 7: filled in by HR / approving officials -->
                        <table class="box">
                            <tbody>
                                <tr>
                                    <td class="w-1/2 align-top no-bottom">
                                        <p class="label">7.A&nbsp;&nbsp;CERTIFICATION OF LEAVE CREDITS</p>
                                        <p class="text-center mt-1">As of <span class="line w-36"></span></p>
                                        <table class="credits">
                                            <thead>
                                                <tr><th></th><th>Vacation Leave</th><th>Sick Leave</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr><td class="italic">Total Earned</td><td></td><td></td></tr>
                                                <tr><td class="italic">Less this application</td><td></td><td></td></tr>
                                                <tr><td class="italic">Balance</td><td></td><td></td></tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="align-top no-bottom">
                                        <p class="label">7.B&nbsp;&nbsp;RECOMMENDATION</p>
                                        <div class="option ml-3 mt-1">
                                            <span class="check">{{ leave.recommendation === 'approval' ? '✓' : '' }}</span>
                                            <span>For approval</span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check">{{ leave.recommendation === 'disapproval' ? '✓' : '' }}</span>
                                            <span>For disapproval due to <span class="line min-w-[8rem]">{{ leave.recommendation === 'disapproval' ? leave.disapproval_reason : '' }}</span></span>
                                        </div>
                                        <p class="ml-8 line w-[80%]"></p>
                                        <p class="ml-8 line w-[80%] mt-2"></p>
                                    </td>
                                </tr>
                                <!-- 7.A / 7.B signatories, side by side -->
                                <tr>
                                    <td class="no-top align-bottom">
                                        <div class="signature">
                                            <span class="sig-line"></span>
                                            <span class="sig-name">{{ signatories?.certified_by?.name || '' }}</span>
                                            <span class="sig-title">{{ signatories?.certified_by?.title || '' }}</span>
                                        </div>
                                    </td>
                                    <td class="no-top align-bottom">
                                        <div class="signature">
                                            <span class="sig-line"></span>
                                            <span class="sig-name">{{ signatories?.recommended_by?.name || '' }}</span>
                                            <span class="sig-title">{{ signatories?.recommended_by?.title || '' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-top">
                                        <p class="label">7.C&nbsp;&nbsp;APPROVED FOR:</p>
                                        <p class="ml-5 mt-1"><span class="line w-12"></span> days with pay</p>
                                        <p class="ml-5"><span class="line w-12"></span> days without pay</p>
                                        <p class="ml-5"><span class="line w-12"></span> others (Specify)</p>
                                    </td>
                                    <td class="align-top">
                                        <p class="label">7.D&nbsp;&nbsp;DISAPPROVED DUE TO:</p>
                                        <p class="ml-5 mt-1 line w-[85%]"></p>
                                        <p class="ml-5 mt-2 line w-[85%]"></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="signature official">
                                            <span class="sig-line"></span>
                                            <span class="sig-name">{{ signatories?.approved_by?.name || '' }}</span>
                                            <span class="sig-title">{{ signatories?.approved_by?.title || '' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <p class="generated">Generated on {{ generatedAt }} · PBO|DocuTrack</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0 print:hidden">
                    <button @click="print" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                    <a
                        v-if="leaveId"
                        :href="`/api/leaves/${leaveId}/form`"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-emerald-700 dark:text-emerald-400 border border-emerald-600 dark:border-emerald-500 hover:text-white hover:bg-emerald-600 rounded-lg transition-all duration-200"
                    >
                        <i class="fas fa-file-excel"></i>
                        Download Excel
                    </a>
                    <template v-if="mode === 'create'">
                        <button
                            @click="$emit('confirm')"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                            Confirm &amp; Save
                        </button>
                        <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200">
                            <i class="fas fa-arrow-left"></i>
                            Back to Form
                        </button>
                    </template>
                    <button v-else @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <p v-if="mode === 'create'" class="w-full text-center text-[11px] text-gray-500 dark:text-gray-400">
                        The Excel file can be downloaded from the leave's preview after it is saved.
                    </p>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { printSafely } from '@/Composables/usePrint';
import {
    FORM_LEAVE_TYPES,
    LOCATION_LEAVE_TYPES,
    splitName,
    longDate,
    applicationDaysText,
    applicationDateLines,
    offDaysText,
    leaveTypeName,
    leaveTypeShort,
} from '../Composables/useLeaveFormPrint';

const props = defineProps<{
    show: boolean;
    /** Leave fields (a saved record or the create form) */
    leave: any;
    /** Applicant, with office */
    employee: any;
    /** 'create': Confirm & Save step; 'view': a saved leave */
    mode: 'create' | 'view';
    /** Saved leave id, enables the Excel download */
    leaveId?: number | null;
    saving?: boolean;
    /** 7.A, 7.B and authorized official: printed name and title */
    signatories?: Record<'certified_by' | 'recommended_by' | 'approved_by', { name: string; title: string }>;
}>();

/** "Vacation Leave (Sec. 51, ...)" -> name "Vacation Leave", citation "(Sec. 51, ...)" */
const labelParts = (label: string) => {
    // The citation is the last parenthetical only: "Special Emergency (Calamity) Leave (CSC MC ...)"
    const match = label.match(/^(.*?)\s*(\([^()]*\))$/);
    return match ? { name: match[1], citation: match[2] } : { name: label, citation: '' };
};

defineEmits<{
    close: [];
    confirm: [];
}>();

const name = computed(() => splitName(props.employee?.name || ''));
// Employees detailed to another office (e.g. the PBO) print the office they work in
const officeName = computed(() => {
    const office = props.employee?.work_office || props.employee?.office;
    return office?.office_name || office?.name || '';
});

/** Every leave type in the application (a create form or a grouped saved application), or the single leave */
const entries = computed<any[]>(() => (props.leave?.entries?.length ? props.leave.entries : props.leave ? [props.leave] : []));
const entryOf = (type: string) => entries.value.find((entry) => entry.type_of_leave === type) || null;
const hasType = (type: string) => !!entryOf(type);
const sickEntry = computed(() => entryOf('Sick Leave'));
const isSick = computed(() => !!sickEntry.value);

/** Saved records send these as 1/0/null, the create form as true/false */
const toFlag = (value: unknown): boolean | null =>
    value === null || value === undefined || value === '' ? null : Boolean(Number(value));

/**
 * 6.B Vacation/Special Privilege Leave: every such leave type in the application counts (e.g. Special Privilege
 * Leave without a purpose plus Vacation Leave with one); purposes are joined, with the type when there are several.
 */
const locationDetails = computed(() => {
    const located = entries.value.filter((entry) => LOCATION_LEAVE_TYPES.includes(entry.type_of_leave));
    const side = (within: boolean) => {
        const matching = located.filter((entry) => toFlag(entry.within_philippines) === within);
        const texts = matching
            .filter((entry) => String(entry.purpose || '').trim())
            .map((entry) => (located.length > 1 ? `${entry.purpose} (${leaveTypeShort(entry)})` : entry.purpose));
        return { checked: matching.length > 0, texts };
    };
    return { within: side(true), abroad: side(false) };
});
const inHospital = computed(() => toFlag(sickEntry.value?.in_hospital));

/** Leave types without a box on the 2020 form go on the "Others" line */
/** 5. SALARY: the monthly salary saved with the leave, e.g. "17,506.00" (no peso sign) */
const salaryText = computed(() => {
    const salary = props.leave?.salary;
    return salary === null || salary === undefined || salary === '' ? '' : Number(salary).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

/** Off days of the application's leave types, e.g. "Saturdays and Sundays" */
const offDaysLine = computed(() => [...new Set(entries.value.map((entry) => offDaysText(entry.off_days)))].join('; '));

const othersText = computed(() =>
    entries.value
        .filter((entry) => entry.type_of_leave && !FORM_LEAVE_TYPES.some((item) => item.type === entry.type_of_leave))
        .map(leaveTypeName)
        .join(', ')
);

const generatedAt = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        + ' at ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
});

const print = () => printSafely();
</script>

<style scoped>
/* Long (folio) paper, 8.5in x 13in, with the margins inside the page */
.cs-form {
    box-sizing: border-box;
    width: 8.5in;
    height: 13in;
    display: flex;
    flex-direction: column;
    padding: 0.25in 0.3in;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10.5px;
    line-height: 1.35;
}
/* Arial throughout the form (the app itself uses Roboto) */
.cs-form, .cs-form * { font-family: Arial, Helvetica, sans-serif !important; }
.form-no { font-size: 9px; font-style: italic; font-weight: bold; }
.agency { display: flex; align-items: center; justify-content: center; gap: 22px; font-size: 13.5px; line-height: 1.3; margin-top: -6px; }
.logo { width: 80px; height: auto; }
.title { text-align: center; font-size: 15px; font-weight: bold; margin: 8px 0 6px; letter-spacing: 0.5px; }
.box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; }
.box > tbody > tr > td { border: 1px solid #000; padding: 5px 7px; }
.bar { border: 1.5px solid #000; border-top: none; border-bottom: none; text-align: center; font-weight: bold; font-size: 11.5px; padding: 3px 0; }
.label { font-weight: bold; font-size: 10px; }
.fill { min-height: 16px; font-weight: bold; font-size: 11px; }
.line { display: inline-block; border-bottom: 1px solid #000; min-height: 14px; padding: 0 3px; vertical-align: bottom; font-weight: bold; }
.option { display: flex; align-items: flex-start; gap: 6px; margin: 2px 0; }
.check { flex: none; width: 12px; height: 12px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; line-height: 1; margin-top: 1px; }
.signature { width: 70%; margin: 44px auto 0; text-align: center; display: flex; flex-direction: column; align-items: center; }
.sig-line { width: 100%; border-top: 1px solid #000; margin-bottom: 1px; }
.sig-name { font-weight: bold; text-transform: uppercase; font-size: 10.5px; }
.sig-title { font-size: 10.5px; line-height: 1.2; }
.signature.official { width: 45%; margin: 48px auto 6px; }
.citation { font-size: 8px; color: #222; }
/* A label and its value on one line, sharing the baseline */
.inline-row { display: flex; align-items: flex-end; gap: 6px; }
.inline-row > .label { white-space: nowrap; }
.inline-row > .line { min-width: 0; line-height: 1.2; }
.box > tbody > tr > td.no-bottom { border-bottom: none; }
.box > tbody > tr > td.no-top { border-top: none; padding-top: 0; }
.credits { width: 92%; margin: 6px auto 0; border-collapse: collapse; }
.credits th, .credits td { border: 1px solid #000; padding: 3px 5px; height: 20px; text-align: center; font-size: 10px; }
.credits td:first-child { text-align: left; }
/* Sections 6 and 7 share the room left on the page, so the form ends at the bottom of the long paper */
.grow-details { flex: 1 1 auto; }
.grow-details-cd { border-top: 1px solid #000 !important; }
.grow-details-cd > .cell { justify-content: flex-start !important; }
.details-ab { display: grid; grid-template-columns: 1fr 1fr; border: 1.5px solid #000; border-top: none; border-bottom: none; }
.details-ab.grow-details { border-top: 1.5px solid #000; }
.details-ab.grow-details-cd { border-bottom: 1.5px solid #000; }
.details-ab > .cell { display: flex; flex-direction: column; justify-content: space-between; padding: 5px 7px; }
.details-ab > .cell + .cell { border-left: 1px solid #000; }
.generated { margin-top: 4px; font-size: 8px; color: #555; }

@media print {
    .cs-form { box-shadow: none; }
}
</style>
