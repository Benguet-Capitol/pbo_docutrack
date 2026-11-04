<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="office in offices"
            :key="office.id"
            accent="emerald"
            icon="fas fa-building"
            :title="office.office_abbreviation"
        >
            <p :class="[CARD_SUBJECT, 'line-clamp-2']" :title="office.office_name">{{ office.office_name }}</p>
            <div v-if="office.fund || office.branch" :class="CARD_TAGS">
                <span v-if="office.fund" :class="[LIST_TAG, 'rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300']" :title="office.fund"><i class="fas fa-coins text-[10px]"></i>{{ office.fund }}</span>
                <span v-if="office.branch" :class="[LIST_TAG, 'rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300']"><i class="fas fa-sitemap text-[10px]"></i>{{ office.branch }}</span>
            </div>
            <div :class="[CARD_STATS, 'mt-auto grid-cols-2']">
                <CardField v-for="code in codes(office)" :key="code.label" :label="code.label" :value="code.value" />
            </div>

            <template v-if="canEditOffices || canDeleteOffices" #actions>
                <RowIconButton v-if="canEditOffices" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', office)" />
                <RowIconButton v-if="canDeleteOffices" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', office)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_STATS, CARD_SUBJECT, CARD_TAGS } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';
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
