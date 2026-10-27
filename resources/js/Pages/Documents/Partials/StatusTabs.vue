<template>
    <!-- Status Tabs: Filter documents by status -->
    <div class="px-6 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="flex gap-1 overflow-x-auto">
            <button
                v-for="tab in statusTabs"
                :key="tab.key"
                @click="activeStatusTab = tab.key"
                :class="[
                    'px-5 py-2 text-xs font-medium whitespace-nowrap border-b-2 transition-colors flex items-center gap-2',
                    activeStatusTab === tab.key
                        ? tabActiveStyles[tab.key]
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                ]"
            >
                {{ tab.label }}
                <span
                    :class="[
                        'px-1.5 py-0.5 rounded-full text-xs',
                        activeStatusTab === tab.key
                            ? tabBadgeStyles[tab.key]
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                    ]"
                >{{ tab.count }}</span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDocumentsContext } from '../Composables/useDocumentsPage';

// Status tabs with live counts
const {
    tabActiveStyles, tabBadgeStyles, activeStatusTab, statusTabs,
} = useDocumentsContext();
</script>
