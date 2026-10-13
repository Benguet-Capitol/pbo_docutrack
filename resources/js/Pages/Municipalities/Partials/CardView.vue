<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="municipality in municipalities"
            :key="municipality.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3">
                <div v-if="municipality.city_class" class="flex flex-wrap items-center gap-1.5 mb-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 text-[11px] font-semibold">
                        <i class="fas fa-layer-group text-[10px]"></i>{{ municipality.city_class }}
                    </span>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-base font-bold text-gray-900 dark:text-gray-100 leading-tight break-words">{{ municipality.name }}</h4>
                        <p class="font-mono" :class="municipality.code ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'">{{ municipality.code || '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="mx-4 mt-3 rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5 min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget Officer</div>
                <div class="truncate" :class="municipality.municipal_budget_officer ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'" :title="municipality.municipal_budget_officer || ''">
                    {{ municipality.municipal_budget_officer || '—' }}
                </div>
            </div>

            <div class="flex-1"></div>
            <div v-if="canEditMunicipalities || canDeleteMunicipalities" class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton v-if="canEditMunicipalities" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', municipality)" />
                <RowIconButton v-if="canDeleteMunicipalities" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', municipality)" />
            </div>
            <div v-else class="mt-3"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import type { Municipality } from '../Composables/useMunicipalityData';

defineProps<{
    municipalities: Municipality[];
    canEditMunicipalities: boolean;
    canDeleteMunicipalities: boolean;
}>();

defineEmits<{
    'edit': [municipality: Municipality];
    'delete': [municipality: Municipality];
}>();
</script>
