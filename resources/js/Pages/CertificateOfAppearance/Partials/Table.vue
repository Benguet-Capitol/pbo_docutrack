<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left table-fixed">
            <colgroup>
                <col class="w-24">
                <col class="w-32">
                <col class="w-28">
                <col class="w-40">
                <col class="w-20">
                <col class="w-32">
            </colgroup>
            <!-- Table Header -->
            <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Control No</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Name</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Office</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Purpose</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Date</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody>
                <tr v-for="certificate in certificates" :key="certificate.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 text-xs text-gray-900 dark:text-gray-100 font-semibold">
                        {{ certificate.control_no }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ certificate.name }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ certificate.office }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ certificate.purpose }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ formattedDate(certificate.date) }}
                    </td>
                    <td class="px-4 py-2 text-xs">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click.stop="$emit('preview', certificate)"
                                class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-eye"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Preview</span>
                            </button>
                            <button
                                @click.stop="$emit('edit', certificate)"
                                class="relative p-2 text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-pencil-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                            </button>
                            <button
                                @click.stop="$emit('delete', certificate)"
                                class="relative p-2 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group"
                            >
                                <i class="fas fa-trash-alt"></i>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
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
