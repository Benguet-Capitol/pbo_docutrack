<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <UserStatisticsPanel
            :user-statistics="userStatistics"
            :expanded-user-id="expandedUserId"
            :selected-user-details="selectedUserDetails"
            :format-hours="formatHours"
            :toggle-user-expanded="toggleUserExpanded"
        />

        <DocumentStatisticsPanel
            :document-statistics="documentStatistics"
            :expanded-document-type="expandedDocumentType"
            :get-documents-for-type="getDocumentsForType"
            :format-hours="formatHours"
            :calculate-processing-time="calculateProcessingTime"
            :get-time-left-styles="getTimeLeftStyles"
            :get-time-left-text="getTimeLeftText"
            :toggle-document-type-expanded="toggleDocumentTypeExpanded"
        />
    </div>
</template>

<script setup lang="ts">
import UserStatisticsPanel from './UserStatisticsPanel.vue';
import DocumentStatisticsPanel from './DocumentStatisticsPanel.vue';

defineProps<{
    userStatistics: any[];
    expandedUserId: number | null;
    selectedUserDetails: any[];
    documentStatistics: any[];
    expandedDocumentType: string | null;
    formatHours: (hours: number) => string;
    toggleUserExpanded: (userId: number) => void;
    calculateProcessingTime: (doc: any) => string;
    getTimeLeftStyles: (doc: any) => object;
    getTimeLeftText: (doc: any) => string;
    toggleDocumentTypeExpanded: (type: string) => void;
    getDocumentsForType: (type: string) => any[];
}>();
</script>
