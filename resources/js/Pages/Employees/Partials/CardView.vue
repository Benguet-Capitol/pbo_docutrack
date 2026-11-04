<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="employee in employees"
            :key="employee.id"
            :accent="employee.separated ? 'red' : 'emerald'"
            :title="employee.name"
        >
            <template #icon>
                <div :class="CARD_AVATAR">{{ initials(employee.name) }}</div>
            </template>
            <template #subtitle>{{ employee.full_position || employee.designation || '—' }}</template>
            <div :class="CARD_TAGS">
                <span :class="[LIST_TAG, 'rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300']"><i class="fas fa-id-badge text-[10px]"></i>{{ employee.employee_id }}</span>
                <span v-if="employee.status" :class="[LIST_TAG, 'rounded', STATUS_CLASS[employee.status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300']">{{ employee.status }}</span>
                <span v-if="employee.separated" :class="[LIST_TAG, 'rounded bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200']" :title="`No longer in HRIS since ${employee.separated_at}; hidden from pickers`">Separated</span>
                <span v-if="employee.office && employee.detailed_to_pbo" :class="[LIST_TAG, 'rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200']" title="Works at the PBO; counts as PBO staff">Detailed to PBO</span>
            </div>
            <CardField label="Office" icon="fas fa-building" :value="employee.office ? officeName(employee) : ''" />

            <template v-if="canEditEmployees || canDeleteEmployees" #actions>
                <RowIconButton v-if="canEditEmployees" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', employee)" />
                <RowIconButton v-if="canDeleteEmployees" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', employee)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_AVATAR, CARD_GRID, CARD_TAGS } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';
import { STATUS_CLASS, officeName } from '../Composables/employeeDisplay';
import type { Employee } from '../Composables/useEmployeeData';

defineProps<{
    employees: Employee[];
    canEditEmployees: boolean;
    canDeleteEmployees: boolean;
    getOfficeName?: (office: any, employee: Employee) => string;
}>();

defineEmits<{
    'edit': [employee: Employee];
    'delete': [employee: Employee];
}>();

// Initials for the avatar, e.g. "Juan D. Cruz" -> "JC"
const initials = (name: string) => {
    const words = String(name ?? '').replace(/[.,]/g, ' ').split(/\s+/).filter(Boolean);
    return ((words[0]?.[0] ?? '') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase() || '?';
};
</script>
