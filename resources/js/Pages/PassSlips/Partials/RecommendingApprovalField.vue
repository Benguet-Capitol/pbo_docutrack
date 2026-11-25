<template>
    <div class="space-y-2">
        <label for="recommending_approval_employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
            Recommending Approval (Immediate Supervisor{{ rows.length > 1 ? 's' : '' }})
        </label>
        <div v-for="(id, index) in rows" :key="index" class="flex items-center gap-2">
            <div class="relative flex items-center flex-1">
                <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                <select
                    :id="index === 0 ? 'recommending_approval_employee_id' : undefined"
                    :value="id ?? ''"
                    @change="choose(index, ($event.target as HTMLSelectElement).value)"
                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none"
                    :class="[error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                >
                    <option value="">Select a supervisor</option>
                    <option v-for="emp in options(id)" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
            </div>
            <button
                v-if="rows.length > 1"
                type="button"
                @click="remove(index)"
                class="shrink-0 w-8 h-8 inline-flex items-center justify-center text-red-600 hover:text-white hover:bg-red-600 border border-red-300 dark:border-red-700 rounded-lg transition-colors"
                title="Remove this supervisor"
            >
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <button
            v-if="!disabled && rows[rows.length - 1] && options(null).length > 0"
            type="button"
            @click="rows = [...rows, null]"
            class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-400 hover:underline"
        >
            <i class="fas fa-plus text-[10px]"></i>
            Add another supervisor
        </button>
        <p v-if="rows.length > 1" class="text-[11px] text-gray-500 dark:text-gray-400">For requesting employees under different supervisors: each one signs the slip.</p>
        <span v-if="error" class="text-red-500 text-xs">{{ error }}</span>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { inPboScope } from '@/Composables/useOfficials';
import type { Employee } from '../Composables/usePassSlipsData';

/**
 * The pass slip's immediate supervisors: one by default, more ("Add another supervisor") when the requesting employees
 * are under different supervisors. Emits the chosen ids in order; the first is the slip's main supervisor.
 */
const props = defineProps<{
    modelValue: number[];
    employees: Employee[];
    error?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [ids: number[]] }>();

// The rows on screen: the chosen ids plus an unfilled one being added
const rows = ref<Array<number | null>>([]);
watch(
    () => props.modelValue,
    (ids) => {
        const chosen = rows.value.filter((id): id is number => id !== null);
        // Already showing these (keeps a row being added); a new slip still needs its first, empty row
        if (rows.value.length && chosen.length === ids.length && chosen.every((id, i) => id === ids[i])) return;
        rows.value = ids.length ? [...ids] : [null];
    },
    { immediate: true, deep: true },
);

const publish = () => emit('update:modelValue', rows.value.filter((id): id is number => id !== null));

const choose = (index: number, value: string) => {
    rows.value = rows.value.map((id, i) => (i === index ? (value ? Number(value) : null) : id));
    publish();
};

const remove = (index: number) => {
    rows.value = rows.value.filter((_, i) => i !== index);
    if (!rows.value.length) rows.value = [null];
    publish();
};

// The office's staff (plus whoever a saved slip already names), less the supervisors chosen in the other rows
const options = (current: number | null) =>
    props.employees.filter((e) => (inPboScope(e) || e.id === current || props.modelValue.includes(e.id)) && (e.id === current || !rows.value.includes(e.id)));
</script>
