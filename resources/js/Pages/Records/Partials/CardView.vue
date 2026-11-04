<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="record in records"
            :key="record.id"
            accent="blue"
            :title="record.record_no"
            :date="new Date(record.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"
        >
            <template #icon>
                <div class="flex h-9 w-9 flex-shrink-0 flex-col items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">
                    <i class="fas fa-file-alt"></i>
                    <span v-if="record.file_size" class="mt-0.5 text-[8px] font-bold uppercase leading-none">{{ record.file_extension || 'FILE' }}</span>
                </div>
            </template>
            <p :class="[CARD_SUBJECT, 'line-clamp-2']" :title="record.title">{{ record.title }}</p>
            <div v-if="record.record_subtype || record.file_size" :class="CARD_TAGS">
                <span v-if="record.record_subtype" :class="[LIST_TAG, 'rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200']">{{ record.record_subtype }}</span>
                <span v-if="record.file_size" :class="[LIST_TAG, 'rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300']"><i class="fas fa-paperclip text-[10px]"></i>{{ formatFileSize(record.file_size) }}</span>
            </div>
            <CardField v-if="record.remarks" label="Remarks" icon="fas fa-comment-dots">
                <p class="line-clamp-3" :title="record.remarks">{{ record.remarks }}</p>
            </CardField>

            <template v-if="record.image_path || canEdit || canDelete" #actions>
                <RowIconButton v-if="record.image_path" color="purple" icon="fas fa-eye" tooltip="View" @click.stop="$emit('view', record)" />
                <RowIconButton v-if="record.image_path" color="emerald" icon="fas fa-download" tooltip="Download" @click.stop="$emit('download', record)" />
                <RowIconButton v-if="canEdit" color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', record)" />
                <RowIconButton v-if="canDelete" color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', record)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_GRID, CARD_SUBJECT, CARD_TAGS } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';
import type { Record } from '../Composables/useRecordsData';

defineProps<{
    records: Record[];
}>();

defineEmits<{
    'view': [record: Record];
    'download': [record: Record];
    'edit': [record: Record];
    'delete': [record: Record];
}>();

const page = usePage();

const hasPermission = (permission: string): boolean => {
    const permissions = (page.props.auth as any)?.permissions || [];
    return permissions.includes(permission);
};

const canEdit = computed(() => hasPermission('records.edit'));
const canDelete = computed(() => hasPermission('records.delete'));

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};
</script>
