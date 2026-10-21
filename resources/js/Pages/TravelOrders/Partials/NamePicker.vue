<template>
    <!-- Name field: type any name, or search and pick an employee from the list -->
    <div class="relative">
        <div class="relative flex items-center">
            <i v-if="icon" :class="[icon, 'absolute left-3 text-gray-400 text-sm pointer-events-none']"></i>
            <input
                :id="inputId"
                :value="modelValue"
                @input="onInput(($event.target as HTMLInputElement).value)"
                @focus="open = true"
                @blur="closeSoon"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="pickActive"
                @keydown.esc="open = false"
                type="text"
                autocomplete="off"
                :placeholder="placeholder"
                :class="[
                    'block w-full pr-8 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors',
                    icon ? 'pl-10' : 'pl-4',
                    invalid ? 'border-red-500 focus:border-red-500' : ['border-gray-300', focusClass],
                ]"
            />
            <button
                v-if="modelValue"
                type="button"
                @mousedown.prevent="select('')"
                class="absolute right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                title="Clear"
            >
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <div
            v-if="open && matches.length"
            class="absolute z-20 mt-1 w-full max-h-60 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg text-xs"
        >
            <template v-for="(option, index) in shown" :key="option.name + index">
                <div
                    v-if="index === 0 || shown[index - 1].group !== option.group"
                    class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 sticky top-0"
                >
                    {{ option.group }}
                </div>
                <button
                    type="button"
                    @mousedown.prevent="select(option.name)"
                    :class="[
                        'w-full text-left px-3 py-1.5 flex items-center justify-between gap-2',
                        index === active ? 'bg-emerald-50 dark:bg-gray-600' : 'hover:bg-gray-50 dark:hover:bg-gray-600',
                    ]"
                >
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ option.name }}</span>
                    <span class="text-gray-500 dark:text-gray-400 text-right">{{ option.label }}</span>
                </button>
            </template>
            <div v-if="matches.length > shown.length" class="px-3 py-1.5 text-gray-500 dark:text-gray-400 italic">
                {{ matches.length - shown.length }} more — keep typing to narrow the list
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

export interface NameOption {
    name: string;
    label: string;
    group: string;
}

const props = withDefaults(
    defineProps<{
        modelValue: string;
        options: NameOption[];
        inputId?: string;
        placeholder?: string;
        icon?: string;
        focusClass?: string;
        invalid?: boolean;
        /** Most options to list at once (long lists ask the user to keep typing) */
        limit?: number;
    }>(),
    { placeholder: 'Type a name', focusClass: 'focus:border-emerald-500', limit: 0 }
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);
const active = ref(-1);

const matches = computed(() => {
    const words = String(props.modelValue || '').toLowerCase().split(/\s+/).filter(Boolean);
    if (!words.length) return props.options;
    return props.options.filter((option) => {
        const text = `${option.name} ${option.label}`.toLowerCase();
        return words.every((word) => text.includes(word));
    });
});

const shown = computed(() => (props.limit > 0 ? matches.value.slice(0, props.limit) : matches.value));

const onInput = (value: string) => {
    open.value = true;
    active.value = -1;
    emit('update:modelValue', value);
};

const select = (name: string) => {
    emit('update:modelValue', name);
    open.value = false;
    active.value = -1;
};

const move = (step: number) => {
    open.value = true;
    if (!shown.value.length) return;
    active.value = (active.value + step + shown.value.length) % shown.value.length;
};

const pickActive = () => {
    if (open.value && active.value >= 0 && shown.value[active.value]) select(shown.value[active.value].name);
};

// Let a click on an option land before the list closes
const closeSoon = () => setTimeout(() => (open.value = false), 150);
</script>
