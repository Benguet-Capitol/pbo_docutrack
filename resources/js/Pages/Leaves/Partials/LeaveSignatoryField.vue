<template>
    <!-- One CS Form No. 6 signatory: title (officer / OIC / Acting) + employee -->
    <div class="space-y-1">
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ config.label }}</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="relative flex items-center">
                <i class="fas fa-id-badge absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                <select
                    :value="title || fallback.title"
                    @change="$emit('change', { employeeId, title: ($event.target as HTMLSelectElement).value })"
                    :aria-label="`${config.label} title`"
                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors"
                >
                    <option v-for="option in titles" :key="option" :value="option">{{ option }}</option>
                </select>
            </div>

            <!-- Employee: type to search, pick from the list -->
            <div class="relative">
                <div class="relative flex items-center">
                    <i class="fas fa-user-tie absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                    <input
                        :value="open ? query : selectedName"
                        @focus="onFocus"
                        @input="query = ($event.target as HTMLInputElement).value"
                        @blur="closeSoon"
                        @keydown.down.prevent="move(1)"
                        @keydown.up.prevent="move(-1)"
                        @keydown.enter.prevent="pickActive"
                        @keydown.esc="open = false"
                        type="text"
                        autocomplete="off"
                        :placeholder="selectedName || 'Search an employee'"
                        :aria-label="`${config.label} name`"
                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors"
                    />
                </div>
                <div v-if="open && shown.length" class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg text-xs">
                    <button
                        v-for="(emp, index) in shown"
                        :key="emp.id"
                        type="button"
                        @mousedown.prevent="pick(emp.id)"
                        :class="['w-full text-left px-3 py-1.5', index === active ? 'bg-emerald-50 dark:bg-gray-600' : 'hover:bg-gray-50 dark:hover:bg-gray-600']"
                    >
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ emp.name }}</span>
                        <span class="block text-gray-500 dark:text-gray-400 truncate">{{ emp.designation }}<template v-if="emp.office?.office_abbreviation"> · {{ emp.office.office_abbreviation }}</template></span>
                    </button>
                    <div v-if="matches.length > shown.length" class="px-3 py-1.5 text-gray-500 dark:text-gray-400 italic">
                        {{ matches.length - shown.length }} more — keep typing to narrow the list
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { LEAVE_SIGNATORIES, signatoryTitles, defaultSignatory, type LeaveSignatoryKey } from '../Composables/useLeaveSignatories';

const props = defineProps<{
    signatory: LeaveSignatoryKey;
    employees: any[];
    employeeId: number | null | undefined;
    /** The applicant: decides the default authorized official */
    applicant?: any;
    title: string | null | undefined;
}>();

const emit = defineEmits<{
    change: [value: { employeeId: number | null; title: string }];
}>();

const LIMIT = 50;

const config = computed(() => LEAVE_SIGNATORIES.find((item) => item.key === props.signatory)!);
const titles = computed(() => signatoryTitles(props.signatory));
// Who signs when nothing is chosen (the authorized official depends on the applicant)
const fallback = computed(() => defaultSignatory(props.employees, props.signatory, props.applicant));

const selected = computed(() =>
    (props.employeeId && props.employees.find((emp) => emp.id === Number(props.employeeId)))
    || fallback.value.employee
);
const selectedName = computed(() => selected.value?.name || '');

// The officer first, then the other candidates by name
const candidates = computed(() => {
    const official = fallback.value.employee;
    const others = props.employees
        .filter((emp) => emp.id !== official?.id && ((config.value.isCandidate(emp) && !emp.separated) || emp.id === selected.value?.id))
        .sort((a, b) => a.name.localeCompare(b.name));
    return official ? [official, ...others] : others;
});

const open = ref(false);
const query = ref('');
const active = ref(-1);

const matches = computed(() => {
    const words = query.value.toLowerCase().split(/\s+/).filter(Boolean);
    if (!words.length) return candidates.value;
    return candidates.value.filter((emp) => {
        const text = `${emp.name} ${emp.designation || ''} ${emp.office?.office_abbreviation || ''}`.toLowerCase();
        return words.every((word) => text.includes(word));
    });
});
const shown = computed(() => matches.value.slice(0, LIMIT));

const onFocus = () => {
    query.value = '';
    active.value = -1;
    open.value = true;
};

const pick = (id: number) => {
    emit('change', { employeeId: id, title: props.title || fallback.value.title });
    open.value = false;
};

const move = (step: number) => {
    if (!shown.value.length) return;
    active.value = (active.value + step + shown.value.length) % shown.value.length;
};

const pickActive = () => {
    if (active.value >= 0 && shown.value[active.value]) pick(shown.value[active.value].id);
};

// Let a click on an option land before the list closes
const closeSoon = () => setTimeout(() => (open.value = false), 150);
</script>
