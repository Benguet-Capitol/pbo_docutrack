<template>
    <Teleport to="body" v-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-users text-emerald-600 dark:text-emerald-400"></i>
                        Bulk Create Notice of Meeting
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Employees -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Employees <span class="text-red-600">*</span>
                                    <span class="text-gray-400 font-normal">({{ formData.employee_ids.length }} selected)</span>
                                </label>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <button type="button" @click="selectAllFiltered" class="text-emerald-600 dark:text-emerald-400 hover:underline">Select all</button>
                                    <button type="button" @click="clearAllEmployees" class="text-gray-500 dark:text-gray-400 hover:underline">Clear</button>
                                </div>
                            </div>
                            <div class="relative flex items-center">
                                <i class="fas fa-search absolute left-3 text-gray-400 text-xs"></i>
                                <input
                                    v-model="employeeSearch"
                                    type="text"
                                    placeholder="Search employee..."
                                    class="block w-full pl-8 pr-3 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none border-gray-300 focus:border-emerald-500"
                                />
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                <label
                                    v-for="employee in filteredEmployees"
                                    :key="employee.id"
                                    class="flex items-center gap-2 px-3 py-2 text-xs cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="formData.employee_ids.includes(employee.id)"
                                        @change="toggleEmployee(employee.id)"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <span class="min-w-0">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ employee.name }}</span>
                                        <span v-if="employee.designation" class="block text-gray-500">{{ employee.designation }}</span>
                                    </span>
                                </label>
                                <p v-if="filteredEmployees.length === 0" class="px-3 py-4 text-center text-gray-400 text-xs">No employees match</p>
                            </div>
                            <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                        </div>

                        <!-- Dates -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Dates <span class="text-red-600">*</span>
                                <span class="text-gray-400 font-normal">({{ formData.dates.length }} selected)</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="dateToAdd"
                                    type="date"
                                    class="block flex-1 px-3 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none border-gray-300 focus:border-emerald-500"
                                />
                                <button
                                    type="button"
                                    @click="addDate"
                                    class="px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg"
                                >
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg max-h-48 overflow-y-auto p-2 flex flex-wrap gap-2 content-start">
                                <span
                                    v-for="date in sortedDates"
                                    :key="date"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300"
                                >
                                    {{ formatDateDisplay(date) }}
                                    <button type="button" @click="removeDate(date)" class="hover:text-red-600">
                                        <i class="fas fa-times text-[10px]"></i>
                                    </button>
                                </span>
                                <p v-if="formData.dates.length === 0" class="text-gray-400 text-xs px-1 py-2">No dates added yet</p>
                            </div>
                            <span v-if="formErrors.dates" class="text-red-500 text-xs">{{ formErrors.dates }}</span>
                        </div>
                    </div>

                    <!-- Time Field -->
                    <div class="space-y-2 mt-4">
                        <label for="bulk_meeting_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Time <span class="text-red-600">*</span></label>
                        <div class="relative flex items-center">
                            <i class="fas fa-clock absolute left-3 text-gray-400 text-sm"></i>
                            <input
                                :value="formData.time"
                                @input="(e) => emitField('time', (e.target as HTMLInputElement).value)"
                                id="bulk_meeting_time"
                                type="time"
                                class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                :class="[formErrors.time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                            />
                        </div>
                        <span v-if="formErrors.time" class="text-red-500 text-xs">{{ formErrors.time }}</span>
                    </div>

                    <!-- Particulars Field -->
                    <div class="space-y-2 mt-4">
                        <label for="bulk_meeting_particulars" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Particulars <span class="text-red-600">*</span></label>
                        <div class="relative flex items-start">
                            <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                            <textarea
                                :value="formData.particulars"
                                @input="(e) => emitField('particulars', (e.target as HTMLTextAreaElement).value)"
                                id="bulk_meeting_particulars"
                                placeholder="e.g. BAC Meeting, Budget Hearing with Governor's Office, etc."
                                rows="3"
                                class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none"
                                :class="[formErrors.particulars ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                            ></textarea>
                        </div>
                        <span v-if="formErrors.particulars" class="text-red-500 text-xs">{{ formErrors.particulars }}</span>
                    </div>

                    <!-- Submit error -->
                    <div v-if="formErrors.submit" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                    </div>

                    <!-- Preview count -->
                    <p v-if="formData.employee_ids.length > 0 && formData.dates.length > 0" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        This will create <strong>{{ formData.employee_ids.length * formData.dates.length }}</strong> Notice(s) of Meeting
                        ({{ formData.employee_ids.length }} employee{{ formData.employee_ids.length > 1 ? 's' : '' }} × {{ formData.dates.length }} date{{ formData.dates.length > 1 ? 's' : '' }}).
                    </p>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        type="submit"
                        @click="$emit('submit')"
                        :disabled="creating || !canSubmit"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                    >
                        <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ creating ? 'Saving...' : 'Create All' }}
                    </button>
                    <button @click="$emit('close')" type="button" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

export interface BulkNoticeOfMeetingFormData {
    employee_ids: number[];
    dates: string[];
    time: string;
    particulars: string;
}

const props = defineProps<{
    show: boolean;
    employees: Array<{ id: number; name: string; designation?: string }>;
    formData: BulkNoticeOfMeetingFormData;
    formErrors: Record<string, string>;
    creating: boolean;
}>();

const emit = defineEmits<{
    'update:form-data': [data: BulkNoticeOfMeetingFormData];
    'close': [];
    'submit': [];
}>();

const employeeSearch = ref('');
const dateToAdd = ref('');

const emitField = (key: keyof BulkNoticeOfMeetingFormData, value: any) => {
    emit('update:form-data', { ...props.formData, [key]: value });
};

const filteredEmployees = computed(() => {
    const q = employeeSearch.value.trim().toLowerCase();
    if (!q) return props.employees;
    return props.employees.filter(e =>
        e.name.toLowerCase().includes(q) || (e.designation || '').toLowerCase().includes(q)
    );
});

const toggleEmployee = (id: number) => {
    const current = props.formData.employee_ids;
    const next = current.includes(id) ? current.filter(x => x !== id) : [...current, id];
    emitField('employee_ids', next);
};

const selectAllFiltered = () => {
    const filteredIds = filteredEmployees.value.map(e => e.id);
    const merged = Array.from(new Set([...props.formData.employee_ids, ...filteredIds]));
    emitField('employee_ids', merged);
};

const clearAllEmployees = () => {
    emitField('employee_ids', []);
};

const addDate = () => {
    if (!dateToAdd.value) return;
    if (!props.formData.dates.includes(dateToAdd.value)) {
        emitField('dates', [...props.formData.dates, dateToAdd.value]);
    }
    dateToAdd.value = '';
};

const removeDate = (date: string) => {
    emitField('dates', props.formData.dates.filter(d => d !== date));
};

const sortedDates = computed(() => [...props.formData.dates].sort());

const formatDateDisplay = (dateStr: string): string => {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const canSubmit = computed(() =>
    props.formData.employee_ids.length > 0 &&
    props.formData.dates.length > 0 &&
    !!props.formData.time &&
    !!props.formData.particulars.trim()
);
</script>

<style scoped>
@keyframes scaleInUp {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>