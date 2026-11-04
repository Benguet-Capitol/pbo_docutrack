<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="certificate in certificates"
            :key="certificate.id"
            accent="green"
            icon="fas fa-certificate"
            :title="certificate.control_no"
            :date="formattedDate(certificate.date)"
        >
            <div :class="CARD_TAGS">
                <span :class="[LIST_TAG, EMPLOYEE_TAG]">{{ certificate.name }}</span>
            </div>
            <CardField label="Office" icon="fas fa-building" :value="certificate.office" />
            <CardField label="Purpose" icon="fas fa-bullseye">
                <p class="line-clamp-3" :title="certificate.purpose">{{ certificate.purpose || '—' }}</p>
            </CardField>

            <template #actions>
                <RowIconButton color="purple" icon="fas fa-eye" tooltip="Preview" @click.stop="$emit('preview', certificate)" />
                <RowIconButton v-if="can('certificate_of_appearances.edit')" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', certificate)" />
                <RowIconButton v-if="can('certificate_of_appearances.delete')" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', certificate)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_TAGS } from '@/utils/cardView';
import { EMPLOYEE_TAG, LIST_TAG } from '@/utils/listTable';
import { usePermissions } from '@/Composables/usePermissions';
import type { CertificateOfAppearance } from '../Composables/useCoaData';

const { can } = usePermissions();

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
