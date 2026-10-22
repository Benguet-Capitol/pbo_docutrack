<template>
    <!-- One leave type of the application: its type, days, dates and details -->
    <div class="space-y-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div v-if="removable || total > 1" class="flex items-center justify-between -mt-1">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Leave type {{ index + 1 }} of {{ total }}</span>
            <button
                v-if="removable"
                type="button"
                @click="$emit('remove')"
                class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700 dark:text-red-400"
            >
                <i class="fas fa-trash-alt"></i> Remove
            </button>
        </div>

        <!-- Type of Leave -->
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type of Leave <span class="text-red-500">*</span></label>
            <select v-model="entry.type_of_leave" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                <option v-for="type in leaveTypes" :key="type" :value="type" :disabled="takenTypes.includes(type) && type !== entry.type_of_leave">{{ type }}</option>
            </select>
            <!-- Yearly credit for limited leave types -->
            <p
                v-if="credit"
                class="mt-1 text-xs"
                :class="credit.exceeds ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'"
            >
                <i class="fas fa-calendar-check mr-1"></i>
                {{ credit.year }}: {{ formatDays(credit.used) }} of {{ credit.limit }} days used,
                <span class="font-semibold">{{ formatDays(credit.remaining) }} remaining</span>
                <template v-if="credit.exceeds"> (this request needs {{ formatDays(credit.requested) }})</template>
            </p>
        </div>

        <!-- Number of Working Days Applied For -->
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Working Days Applied For <span class="text-red-500">*</span></label>
            <input :value="entry.is_half_day ? 0.5 : entry.number_of_working_days_applied_for" type="number" readonly tabindex="-1" class="w-full border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-xs bg-gray-100 dark:bg-gray-900 dark:text-white cursor-not-allowed focus:outline-none" />
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                <i class="fas fa-calculator mr-1"></i>Counted from the inclusive dates, without the off days ({{ offDaysText(entry.off_days) }}).
            </p>
        </div>

        <!-- Type of Leave Duration -->
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Type of Leave Duration</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2">
                    <input v-model="entry.is_half_day" type="radio" :value="false" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Full Day</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="entry.is_half_day" type="radio" :value="true" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Half Day</span>
                </label>
            </div>
        </div>

        <!-- Half Day Period (AM/PM) - Conditional -->
        <div v-if="entry.is_half_day">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Half Day Period <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2">
                    <input v-model="entry.half_day_period" type="radio" value="AM" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Morning (AM)</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="entry.half_day_period" type="radio" value="PM" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Afternoon (PM)</span>
                </label>
            </div>
        </div>

        <!-- Inclusive Dates -->
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Inclusive Dates <span class="text-red-500">*</span></label>
            <div class="space-y-2">
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                        <input v-model="entry.newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                        <input v-model="entry.newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                    </div>
                    <button type="button" @click.prevent="addInclusiveDate" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-xs font-medium">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(dateValue, idx) in entry.inclusive_dates" :key="idx" @click="editInclusiveDate(idx)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs cursor-pointer hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">
                        <i class="fas fa-edit text-emerald-600 dark:text-emerald-400"></i>
                        {{ formatInclusiveDate(dateValue) }}
                        <button @click.stop="removeInclusiveDate(idx)" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 font-bold">×</button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Off Days -->
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Off Days</label>
            <div class="flex gap-2 mb-2">
                <input v-model="entry.newOffDay" type="text" placeholder="Default: Saturday and Sunday (e.g. Monday, Tuesday)" class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                <button @click="addOffDay" type="button" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded-lg font-medium transition-colors">
                    Add
                </button>
            </div>
            <div v-if="entry.off_days.length > 0" class="flex flex-wrap gap-2">
                <div v-for="(day, index) in entry.off_days" :key="index" class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900 px-3 py-1 rounded-lg">
                    <span class="text-xs text-emerald-800 dark:text-emerald-200">{{ day }}</span>
                    <button @click="removeOffDay(index)" type="button" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Location Details - Vacation/Special Privilege/Wellness Leave -->
        <div v-if="['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(entry.type_of_leave)" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="font-medium text-gray-700 dark:text-gray-300">Location Details</h3>
            <div class="flex gap-4">
                <label class="flex items-center gap-2">
                    <input v-model="entry.within_philippines" type="radio" :name="`location-${uid}`" :value="true" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Within Philippines</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="entry.within_philippines" type="radio" :name="`location-${uid}`" :value="false" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Abroad</span>
                </label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                <textarea v-model="entry.purpose" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
            </div>
        </div>

        <!-- Medical Details - Sick Leave -->
        <div v-if="entry.type_of_leave === 'Sick Leave'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="font-medium text-gray-700 dark:text-gray-300">Medical Details</h3>
            <div class="flex gap-4">
                <label class="flex items-center gap-2">
                    <input v-model="entry.in_hospital" type="radio" :name="`hospital-${uid}`" :value="true" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">In Hospital</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="entry.in_hospital" type="radio" :name="`hospital-${uid}`" :value="false" class="cursor-pointer" />
                    <span class="text-xs text-gray-700 dark:text-gray-300">Out Patient</span>
                </label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                <textarea v-model="entry.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
            </div>
        </div>

        <!-- Health Details - Special Leave Benefits for Women -->
        <div v-if="entry.type_of_leave === 'Special Leave Benefits for Women'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="font-medium text-gray-700 dark:text-gray-300">Health Details</h3>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                <textarea v-model="entry.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
            </div>
        </div>

        <!-- Study Details - Study Leave -->
        <div v-if="entry.type_of_leave === 'Study Leave'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="font-medium text-gray-700 dark:text-gray-300">Study Details</h3>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Type</label>
                <select v-model="entry.completion_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                    <option value="">Select Type</option>
                    <option value="Completion of Master's Degree">Completion of Master's Degree</option>
                    <option value="BAR/Board Examination Review">BAR/Board Examination Review</option>
                </select>
            </div>
        </div>

        <!-- Leave Type Details - Others -->
        <div v-if="entry.type_of_leave === 'Others'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="font-medium text-gray-700 dark:text-gray-300">Leave Type Details</h3>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                <select v-model="entry.other_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                    <option value="">Select Type</option>
                    <option value="Monetization of Leave Credits">Monetization of Leave Credits</option>
                    <option value="Terminal Leave">Terminal Leave</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { formatDays } from '@/Composables/useLeaveCredits';
import { countWorkingDays, offDaysText } from '../Composables/useLeaveFormPrint';
import type { LeaveEntry } from '../Composables/useLeavesForm';

const props = defineProps<{
    entry: LeaveEntry;
    index: number;
    total: number;
    leaveTypes: string[];
    /** Types the other entries already use (each type once per application) */
    takenTypes: string[];
    /** Yearly credit for a limited leave type, if any */
    credit?: { year: number; limit: number; used: number; remaining: number; requested: number; exceeds: boolean } | null;
    removable?: boolean;
    /** Makes radio group names unique across entries and modals */
    uid: string;
}>();

defineEmits<{
    remove: [];
}>();

// Working days follow the inclusive dates and off days (not typed in)
watch(
    () => [props.entry.inclusive_dates.slice(), props.entry.off_days.slice()],
    () => {
        props.entry.number_of_working_days_applied_for = countWorkingDays(props.entry.inclusive_dates, props.entry.off_days);
    },
    { deep: true },
);

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDate = (value: string): string => {
    if (!value) return '';
    if (value.includes(' - ')) {
        const [startDate, endDate] = value.split(' - ');
        return `${formatDateForDisplay(startDate.trim())} - ${formatDateForDisplay(endDate.trim())}`;
    }
    return formatDateForDisplay(value);
};

const addInclusiveDate = () => {
    const entry = props.entry;
    if (!entry.newInclusiveDate) return;
    // A range typed backwards ("to" before "from") is put in order
    let from = entry.newInclusiveDate;
    let to = entry.newInclusiveDateRange || '';
    if (to && to < from) [from, to] = [to, from];
    const value = to && to !== from ? `${from} - ${to}` : from;
    if (!entry.inclusive_dates.includes(value)) entry.inclusive_dates.push(value);
    entry.newInclusiveDate = '';
    entry.newInclusiveDateRange = '';
};

const removeInclusiveDate = (index: number) => {
    props.entry.inclusive_dates.splice(index, 1);
};

const editInclusiveDate = (index: number) => {
    const entry = props.entry;
    const value = entry.inclusive_dates[index];
    if (value.includes(' - ')) {
        const [startDate, endDate] = value.split(' - ');
        entry.newInclusiveDate = startDate.trim();
        entry.newInclusiveDateRange = endDate.trim();
    } else {
        entry.newInclusiveDate = value;
        entry.newInclusiveDateRange = '';
    }
    entry.inclusive_dates.splice(index, 1);
};

const addOffDay = () => {
    const entry = props.entry;
    if (entry.newOffDay && !entry.off_days.includes(entry.newOffDay)) {
        entry.off_days.push(entry.newOffDay);
        entry.newOffDay = '';
    }
};

const removeOffDay = (index: number) => {
    props.entry.off_days.splice(index, 1);
};
</script>
