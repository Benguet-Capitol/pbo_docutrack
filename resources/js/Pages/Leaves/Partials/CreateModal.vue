<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-leaf text-emerald-600 dark:text-emerald-400"></i>
                            Create Leave
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <!-- Submit Error -->
                        <div v-if="formErrors['submit']" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-sm">
                            {{ formErrors['submit'] }}
                        </div>

                        <div class="space-y-4">
                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Employee <span class="text-red-500">*</span></label>
                                <select v-model.number="formData.employee_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" :class="[formErrors.employee_id ? 'border-red-500 focus:border-red-500' : '']">
                                    <option value="0">Select Employee</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                </select>
                                <p v-if="formErrors['employee_id']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['employee_id'] }}</p>
                            </div>

                            <!-- Date of Filing -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Filing <span class="text-red-500">*</span></label>
                                <input v-model="formData.date_of_filing" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" :class="[formErrors.date_of_filing ? 'border-red-500 focus:border-red-500' : '']" />
                                <p v-if="formErrors['date_of_filing']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['date_of_filing'] }}</p>
                            </div>

                            <!-- Type of Leave -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type of Leave <span class="text-red-500">*</span></label>
                                <select v-model="formData.type_of_leave" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                    <option v-for="type in leaveTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </div>

                            <!-- Number of Working Days Applied For -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Number of Working Days Applied For <span class="text-red-500">*</span></label>
                                <input v-model.number="formData.number_of_working_days_applied_for" type="number" min="1" step="0.5" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                            </div>

                            <!-- Type of Leave Duration -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Type of Leave Duration</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.is_half_day" type="radio" :value="false" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Full Day</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.is_half_day" type="radio" :value="true" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Half Day</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Half Day Period (AM/PM) - Conditional -->
                            <div v-if="formData.is_half_day">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Half Day Period <span class="text-red-500">*</span></label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.half_day_period" type="radio" value="AM" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Morning (AM)</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.half_day_period" type="radio" value="PM" class="cursor-pointer" />
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
                                            <input v-model="formData.newInclusiveDate" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date (optional)</label>
                                            <input v-model="formData.newInclusiveDateRange" type="date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                        </div>
                                        <button @click.prevent="addInclusiveDate" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-xs font-medium">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="(entry, idx) in formData.inclusive_dates" :key="idx" @click="editInclusiveDate(idx)" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs cursor-pointer hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">
                                            <i class="fas fa-edit text-emerald-600 dark:text-emerald-400"></i>
                                            {{ formatInclusiveDate(entry) }}
                                            <button @click.stop="removeInclusiveDate(idx)" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 font-bold">×</button>
                                        </span>
                                    </div>
                                </div>
                                <p v-if="formErrors['inclusive_dates']" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ formErrors['inclusive_dates'] }}</p>
                            </div>

                            <!-- Off Days -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Off Days</label>
                                <div class="flex gap-2 mb-2">
                                    <input v-model="formData.newOffDay" type="text" placeholder="e.g., Monday, Tuesday" class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500" />
                                    <button @click="addOffDay" type="button" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded-lg font-medium transition-colors">
                                        Add
                                    </button>
                                </div>
                                <div v-if="formData.off_days.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="(day, index) in formData.off_days" :key="index" class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900 px-3 py-1 rounded-lg">
                                        <span class="text-xs text-emerald-800 dark:text-emerald-200">{{ day }}</span>
                                        <button @click="removeOffDay(index)" type="button" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Details - Vacation/Special Privilege/Wellness Leave -->
                            <div v-if="['Vacation Leave', 'Special Privilege Leave', 'Wellness Leave'].includes(formData.type_of_leave)" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Location Details</h3>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.within_philippines" type="radio" name="location_create" :value="true" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Within Philippines</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.within_philippines" type="radio" name="location_create" :value="false" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Abroad</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                                    <textarea v-model="formData.purpose" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                </div>
                            </div>

                            <!-- Medical Details - Sick Leave -->
                            <div v-if="formData.type_of_leave === 'Sick Leave'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Medical Details</h3>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.in_hospital" type="radio" name="hospital_create" :value="true" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">In Hospital</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input v-model="formData.in_hospital" type="radio" name="hospital_create" :value="false" class="cursor-pointer" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">Out Patient</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                    <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                </div>
                            </div>

                            <!-- Health Details - Special Leave Benefits for Women -->
                            <div v-if="formData.type_of_leave === 'Special Leave Benefits for Women'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Health Details</h3>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Illness</label>
                                    <textarea v-model="formData.illness" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500"></textarea>
                                </div>
                            </div>

                            <!-- Study Details - Study Leave -->
                            <div v-if="formData.type_of_leave === 'Study Leave'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Study Details</h3>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Completion Type</label>
                                    <select v-model="formData.completion_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                        <option value="">Select Type</option>
                                        <option value="Completion of Master's Degree">Completion of Master's Degree</option>
                                        <option value="BAR/Board Examination Review">BAR/Board Examination Review</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Leave Type Details - Others -->
                            <div v-if="formData.type_of_leave === 'Others'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-medium text-gray-700 dark:text-gray-300">Leave Type Details</h3>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                    <select v-model="formData.other_type" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-xs dark:bg-gray-700 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500">
                                        <option value="">Select Type</option>
                                        <option value="Monetization of Leave Credits">Monetization of Leave Credits</option>
                                        <option value="Terminal Leave">Terminal Leave</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="$emit('confirm')"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ loading ? 'Creating...' : 'Create' }}
                        </button>
                        <button
                            @click="$emit('close')"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
interface LeaveFormData {
    employee_id: number;
    date_of_filing: string;
    type_of_leave: string;
    number_of_working_days_applied_for: number;
    is_half_day: boolean;
    half_day_period: string;
    inclusive_dates: string[];
    off_days: string[];
    within_philippines: boolean;
    purpose: string;
    in_hospital: boolean;
    illness: string;
    completion_type: string;
    other_type: string;
    newInclusiveDate: string;
    newInclusiveDateRange: string;
    newOffDay: string;
}

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface FormErrors {
    [key: string]: string;
}

const props = defineProps<{
    show: boolean;
    formData: LeaveFormData;
    formErrors: FormErrors;
    employees: Employee[];
    loading: boolean;
    leaveTypes: string[];
}>();

defineEmits<{
    close: [];
    confirm: [];
}>();

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatInclusiveDate = (entry: string): string => {
    if (!entry) return '';
    
    if (entry.includes(' - ')) {
        // This is a date range
        const [startDate, endDate] = entry.split(' - ');
        return `${formatDateForDisplay(startDate.trim())} - ${formatDateForDisplay(endDate.trim())}`;
    } else {
        // This is a single date
        return formatDateForDisplay(entry);
    }
};

const addInclusiveDate = () => {
    if (props.formData.newInclusiveDate) {
        if (props.formData.newInclusiveDateRange) {
            const rangeString = `${props.formData.newInclusiveDate} - ${props.formData.newInclusiveDateRange}`;
            if (!props.formData.inclusive_dates.includes(rangeString)) {
                props.formData.inclusive_dates.push(rangeString);
            }
        } else {
            if (!props.formData.inclusive_dates.includes(props.formData.newInclusiveDate)) {
                props.formData.inclusive_dates.push(props.formData.newInclusiveDate);
            }
        }
        props.formData.newInclusiveDate = '';
        props.formData.newInclusiveDateRange = '';
    }
};

const removeInclusiveDate = (index: number) => {
    props.formData.inclusive_dates.splice(index, 1);
};

const editInclusiveDate = (index: number) => {
    const entry = props.formData.inclusive_dates[index];
    if (entry.includes(' - ')) {
        const [startDate, endDate] = entry.split(' - ');
        props.formData.newInclusiveDate = startDate.trim();
        props.formData.newInclusiveDateRange = endDate.trim();
    } else {
        props.formData.newInclusiveDate = entry;
        props.formData.newInclusiveDateRange = '';
    }
    props.formData.inclusive_dates.splice(index, 1);
};

const addOffDay = () => {
    if (props.formData.newOffDay && !props.formData.off_days.includes(props.formData.newOffDay)) {
        props.formData.off_days.push(props.formData.newOffDay);
        props.formData.newOffDay = '';
    }
};

const removeOffDay = (index: number) => {
    props.formData.off_days.splice(index, 1);
};
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
