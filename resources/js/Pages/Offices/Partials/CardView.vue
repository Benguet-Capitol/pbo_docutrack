<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="office in offices"
            :key="office.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div v-if="office.fund || office.branch" class="flex flex-wrap items-center gap-1.5 mb-2">
                    <span v-if="office.fund" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 text-[11px] font-semibold max-w-full" :title="office.fund">
                        <i class="fas fa-coins text-[10px]"></i><span class="truncate">{{ office.fund }}</span>
                    </span>
                    <span v-if="office.branch" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px] font-semibold">
                        <i class="fas fa-sitemap text-[10px] text-gray-400"></i>{{ office.branch }}
                    </span>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-base font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ office.office_abbreviation }}</h4>
                        <p class="text-gray-600 dark:text-gray-300 line-clamp-2 break-words" :title="office.office_name">{{ office.office_name }}</p>
                    </div>
                </div>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                <div v-for="code in codes(office)" :key="code.label" class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ code.label }}</div>
                    <div class="font-mono truncate" :class="code.value ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'" :title="code.value || ''">{{ code.value || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div v-if="canEditOffices || canDeleteOffices" class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton v-if="canEditOffices" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', office)" />
                <RowIconButton v-if="canDeleteOffices" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', office)" />
            </div>
            <div v-else class="mt-3"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import type { Office } from '../Composables/useOfficeData';

defineProps<{
    offices: Office[];
    canEditOffices: boolean;
    canDeleteOffices: boolean;
}>();

defineEmits<{
    'edit': [office: Office];
    'delete': [office: Office];
}>();

const codes = (office: Office) => [
    { label: 'FPP Code', value: office.fpp_code },
    { label: 'Responsibility Code', value: office.responsibility_code },
];
</script>
