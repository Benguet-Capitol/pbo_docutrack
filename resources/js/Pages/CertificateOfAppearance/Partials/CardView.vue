<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="certificate in certificates"
            :key="certificate.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-green-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono break-all">{{ certificate.control_no }}</h4>
                    <span class="text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <i class="far fa-calendar text-[10px]"></i> {{ formattedDate(certificate.date) }}
                    </span>
                </div>
                <span class="mt-2 inline-block px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-semibold">
                    {{ certificate.name }}
                </span>
            </div>

            <div class="mx-4 mt-3 grid grid-cols-1 gap-2">
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Office</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words">{{ certificate.office || '—' }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Purpose</div>
                    <div class="text-gray-800 dark:text-gray-200 break-words line-clamp-3" :title="certificate.purpose">{{ certificate.purpose || '—' }}</div>
                </div>
            </div>

            <div class="flex-1"></div>
            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="Preview" @click.stop="$emit('preview', certificate)" />
                <RowIconButton color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', certificate)" />
                <RowIconButton color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', certificate)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import type { CertificateOfAppearance } from '../Composables/useCoaData';

defineProps<{
    certificates: CertificateOfAppearance[];
}>();

defineEmits<{
    edit: [certificate: CertificateOfAppearance];
    delete: [certificate: CertificateOfAppearance];
    preview: [certificate: CertificateOfAppearance];
}>();

const formattedDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
