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
                <div class="p-4 bg-gray-100 dark:bg-gray-900">
                    <div
                        id="preview-content"
                        class="cs-form mx-auto bg-white text-black"
                        data-page-width-in="8.5"
                        data-page-height-in="13"
                        data-page-margin-in="0.3"
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
                                        <span class="label">1.&nbsp;&nbsp;OFFICE/DEPARTMENT</span>
                                        <div class="fill mt-1">{{ officeName }}</div>
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
                                    <td>
                                        <span class="label">3.&nbsp;&nbsp;DATE OF FILING</span>
                                        <span class="line w-40">{{ longDate(leave.date_of_filing) }}</span>
                                    </td>
                                    <td>
                                        <span class="label">4.&nbsp;&nbsp;POSITION</span>
                                        <span class="line min-w-[11rem]">{{ employee?.full_position || employee?.designation || '' }}</span>
                                        <span class="label ml-3">5.&nbsp;&nbsp;SALARY</span>
                                        <span class="line w-24"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="bar">6.&nbsp;&nbsp;DETAILS OF APPLICATION</div>

                        <table class="box">
                            <tbody>
                                <tr>
                                    <!-- 6.A Type of leave -->
                                    <td class="w-1/2 align-top">
                                        <p class="label mb-1">6.A&nbsp;&nbsp;TYPE OF LEAVE TO BE AVAILED OF</p>
                                        <div v-for="item in FORM_LEAVE_TYPES" :key="item.type" class="option">
                                            <span class="check">{{ leave.type_of_leave === item.type ? '✓' : '' }}</span>
                                            <span>{{ item.label }}</span>
                                        </div>
                                        <p class="mt-2 ml-5">Others:</p>
                                        <p class="ml-5 line w-[85%]">{{ othersText }}</p>
                                    </td>

                                    <!-- 6.B Details of leave -->
                                    <td class="align-top">
                                        <p class="label mb-1">6.B&nbsp;&nbsp;DETAILS OF LEAVE</p>

                                        <p class="italic">In case of Vacation/Special Privilege Leave:</p>
                                        <div class="option ml-3">
                                            <span class="check">{{ isLocationType && withinPh === true ? '✓' : '' }}</span>
                                            <span>Within the Philippines <span class="line min-w-[9rem]">{{ isLocationType && withinPh === true ? leave.purpose : '' }}</span></span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check">{{ isLocationType && withinPh === false ? '✓' : '' }}</span>
                                            <span>Abroad (Specify) <span class="line min-w-[10rem]">{{ isLocationType && withinPh === false ? leave.purpose : '' }}</span></span>
                                        </div>

                                        <p class="italic mt-2">In case of Sick Leave:</p>
                                        <div class="option ml-3">
                                            <span class="check">{{ isSick && inHospital === true ? '✓' : '' }}</span>
                                            <span>In Hospital (Specify Illness) <span class="line min-w-[7rem]">{{ isSick && inHospital === true ? leave.illness : '' }}</span></span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check">{{ isSick && inHospital === false ? '✓' : '' }}</span>
                                            <span>Out Patient (Specify Illness) <span class="line min-w-[7rem]">{{ isSick && inHospital === false ? leave.illness : '' }}</span></span>
                                        </div>

                                        <p class="italic mt-2">In case of Special Leave Benefits for Women:</p>
                                        <p class="ml-3">(Specify Illness) <span class="line min-w-[10rem]">{{ leave.type_of_leave === 'Special Leave Benefits for Women' ? leave.illness : '' }}</span></p>

                                        <p class="italic mt-2">In case of Study Leave:</p>
                                        <div class="option ml-3">
                                            <span class="check">{{ leave.type_of_leave === 'Study Leave' && leave.completion_type === `Completion of Master's Degree` ? '✓' : '' }}</span>
                                            <span>Completion of Master's Degree</span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check">{{ leave.type_of_leave === 'Study Leave' && leave.completion_type === 'BAR/Board Examination Review' ? '✓' : '' }}</span>
                                            <span>BAR/Board Examination Review</span>
                                        </div>

                                        <p class="italic mt-2">Other purpose:</p>
                                        <div class="option ml-3">
                                            <span class="check">{{ leave.type_of_leave === 'Others' && leave.other_type === 'Monetization of Leave Credits' ? '✓' : '' }}</span>
                                            <span>Monetization of Leave Credits</span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check">{{ leave.type_of_leave === 'Others' && leave.other_type === 'Terminal Leave' ? '✓' : '' }}</span>
                                            <span>Terminal Leave</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <!-- 6.C Working days -->
                                    <td class="align-top">
                                        <p class="label">6.C&nbsp;&nbsp;NUMBER OF WORKING DAYS APPLIED FOR</p>
                                        <p class="ml-5 mt-1 line w-[85%] text-center">{{ daysText(leave) }}</p>
                                        <p class="ml-5 mt-2 label font-normal">INCLUSIVE DATES</p>
                                        <p class="ml-5 line w-[85%] text-center">{{ inclusiveDatesText(leave.inclusive_dates) }}</p>
                                    </td>
                                    <!-- 6.D Commutation -->
                                    <td class="align-top">
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
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="bar">7.&nbsp;&nbsp;DETAILS OF ACTION ON APPLICATION</div>

                        <!-- 7: filled in by HR / approving officials -->
                        <table class="box">
                            <tbody>
                                <tr>
                                    <td class="w-1/2 align-top">
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
                                        <div class="signature">
                                            <span class="sig-line"></span>
                                            <span>(Authorized Officer)</span>
                                        </div>
                                    </td>
                                    <td class="align-top">
                                        <p class="label">7.B&nbsp;&nbsp;RECOMMENDATION</p>
                                        <div class="option ml-3 mt-1">
                                            <span class="check"></span>
                                            <span>For approval</span>
                                        </div>
                                        <div class="option ml-3">
                                            <span class="check"></span>
                                            <span>For disapproval due to <span class="line min-w-[8rem]"></span></span>
                                        </div>
                                        <p class="ml-8 line w-[80%]"></p>
                                        <p class="ml-8 line w-[80%] mt-2"></p>
                                        <div class="signature">
                                            <span class="sig-line"></span>
                                            <span>(Authorized Officer)</span>
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
                                        <div class="signature mx-auto">
                                            <span class="sig-line"></span>
                                            <span>(Authorized Official)</span>
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
    inclusiveDatesText,
    daysText,
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
}>();

defineEmits<{
    close: [];
    confirm: [];
}>();

const name = computed(() => splitName(props.employee?.name || ''));
const officeName = computed(() => props.employee?.office?.office_name || props.employee?.office?.name || '');
const isLocationType = computed(() => LOCATION_LEAVE_TYPES.includes(props.leave?.type_of_leave));
const isSick = computed(() => props.leave?.type_of_leave === 'Sick Leave');

/** Saved records send these as 1/0/null, the create form as true/false */
const toFlag = (value: unknown): boolean | null =>
    value === null || value === undefined || value === '' ? null : Boolean(Number(value));
const withinPh = computed(() => toFlag(props.leave?.within_philippines));
const inHospital = computed(() => toFlag(props.leave?.in_hospital));

/** Leave types without a box on the 2020 form go on the "Others" line */
const othersText = computed(() => {
    const type = props.leave?.type_of_leave;
    if (!type || FORM_LEAVE_TYPES.some((item) => item.type === type)) return '';
    return type === 'Others' ? (props.leave?.other_type || 'Others') : type;
});

const generatedAt = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        + ' at ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
});

const print = () => printSafely();
</script>

<style scoped>
.cs-form {
    width: 7.9in;
    padding: 0.15in;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10.5px;
    line-height: 1.35;
}
.form-no { font-size: 9px; font-style: italic; font-weight: bold; }
.agency { display: flex; align-items: center; justify-content: center; gap: 18px; font-size: 11px; margin-top: -6px; }
.logo { width: 62px; height: auto; }
.title { text-align: center; font-size: 15px; font-weight: bold; margin: 8px 0 6px; letter-spacing: 0.5px; }
.box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; }
.box > tbody > tr > td { border: 1px solid #000; padding: 5px 7px; }
.bar { border: 1.5px solid #000; border-top: none; border-bottom: none; text-align: center; font-weight: bold; font-size: 11.5px; padding: 3px 0; background: #e5e7eb; }
.label { font-weight: bold; font-size: 10px; }
.fill { min-height: 16px; font-weight: bold; font-size: 11px; }
.line { display: inline-block; border-bottom: 1px solid #000; min-height: 14px; padding: 0 3px; vertical-align: bottom; font-weight: bold; }
.option { display: flex; align-items: flex-start; gap: 6px; margin: 2px 0; }
.check { flex: none; width: 12px; height: 12px; border: 1px solid #000; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; line-height: 1; margin-top: 1px; }
.signature { width: 70%; margin: 26px auto 0; text-align: center; display: flex; flex-direction: column; align-items: center; }
.sig-line { width: 100%; border-top: 1px solid #000; margin-bottom: 1px; }
.credits { width: 92%; margin: 6px auto 0; border-collapse: collapse; }
.credits th, .credits td { border: 1px solid #000; padding: 3px 5px; height: 20px; text-align: center; font-size: 10px; }
.credits td:first-child { text-align: left; }
.generated { margin-top: 6px; font-size: 8px; color: #555; }

@media print {
    #preview-content { page: cs-form-6; }
    @page cs-form-6 {
        size: 8.5in 13in portrait;
        margin: 0.3in;
    }
}
</style>
