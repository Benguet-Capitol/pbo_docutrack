<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="municipality in municipalities"
            :key="municipality.id"
            accent="emerald"
            icon="fas fa-landmark"
            :title="municipality.name"
        >
            <template #subtitle>{{ municipality.code || '—' }}</template>
            <template v-if="municipality.city_class" #aside>
                <span :class="[LIST_TAG, 'rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300']"><i class="fas fa-layer-group text-[10px]"></i>{{ municipality.city_class }}</span>
            </template>
            <CardField label="Budget Officer" icon="fas fa-user-tie" :value="municipality.municipal_budget_officer" />

            <template v-if="canEditMunicipalities || canDeleteMunicipalities" #actions>
                <RowIconButton v-if="canEditMunicipalities" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', municipality)" />
                <RowIconButton v-if="canDeleteMunicipalities" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', municipality)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';
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
