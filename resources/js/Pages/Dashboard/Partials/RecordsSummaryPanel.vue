<template>
    <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- Header Section -->
        <div class="px-4 sm:px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-file-archive text-gray-600 dark:text-gray-400"></i>
                Records Summary
            </h3>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-600 dark:text-gray-400">Loading records...</p>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="m-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400"></i>
                <div>
                    <p class="font-medium text-red-900 dark:text-red-200">Error loading records</p>
                    <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Records Grid -->
        <div v-else-if="recordSummary.length > 0" class="w-full p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 auto-rows-max">
            <div
                v-for="record in recordSummary"
                :key="record.record_type"
                @mouseenter="hoveredRecord = record.record_type"
                @mouseleave="hoveredRecord = null"
                class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                :class="getCardClass(record.record_type)"
            >
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i :class="['fas', getRecordIcon(record.record_type), getIconTextColor(record.record_type)]"></i>
                        {{ record.record_type }}
                    </h4>
                </div>
                <div :class="['text-3xl font-bold mb-1', getNumberColor(record.record_type)]">
                    {{ record.count }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Total records
                </p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
            <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-400">No records found</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Start creating records to see them here</p>
        </div>

        <!-- Total Summary -->
        <div v-if="recordSummary.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
            <div class="flex items-center justify-between">
                <p class="font-medium text-gray-900 dark:text-white">Total Records</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalRecords }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';

// ============== State ==============
const loading = ref(false);
const error = ref('');
const records = ref<any[]>([]);
const hoveredRecord = ref<string | null>(null);

// ============== Computed ==============
const recordSummary = computed(() => {
    const summary: Record<string, number> = {};
    
    records.value.forEach(record => {
        const type = record.record_type || 'Unclassified';
        summary[type] = (summary[type] || 0) + 1;
    });
    
    // Define the desired order of record types
    const orderMap: Record<string, number> = {
        'Provincial Budget': 0,
        'Municipal Budget': 1,
        'Issuances / Circulars / Other References and Documents': 2,
    };
    
    // Convert to array and sort by the defined order
    return Object.entries(summary)
        .map(([record_type, count]) => ({ record_type, count }))
        .sort((a, b) => {
            const orderA = orderMap[a.record_type] ?? 999;
            const orderB = orderMap[b.record_type] ?? 999;
            return orderA - orderB;
        });
});

const totalRecords = computed(() => {
    return records.value.length;
});

// ============== Methods ==============
const getRecordIcon = (recordType: string): string => {
    const iconMap: Record<string, string> = {
        'Provincial Budget': 'fa-landmark',
        'Municipal Budget': 'fa-city',
        'Issuances / Circulars / Other References and Documents': 'fa-scroll',
    };
    
    return iconMap[recordType] || 'fa-file';
};

const getCardClass = (recordType: string): string => {
    const isHovered = hoveredRecord.value === recordType;
    
    if (isHovered) {
        // Hovered state with colored background and border
        const colorMap: Record<string, string> = {
            'Provincial Budget': 'bg-purple-50 dark:bg-purple-900/20 border-purple-500',
            'Municipal Budget': 'bg-orange-50 dark:bg-orange-900/20 border-orange-500',
            'Issuances / Circulars / Other References and Documents': 'bg-blue-50 dark:bg-blue-900/20 border-blue-500',
        };
        return colorMap[recordType] || 'bg-gray-50 dark:bg-gray-700 border-gray-400';
    } else {
        // Default state - white/gray background with gray border
        return 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600';
    }
};

const getNumberColor = (recordType: string): string => {
    // Always show colored numbers (don't change on hover)
    const colorMap: Record<string, string> = {
        'Provincial Budget': 'text-purple-600 dark:text-purple-400',
        'Municipal Budget': 'text-orange-600 dark:text-orange-400',
        'Issuances / Circulars / Other References and Documents': 'text-blue-600 dark:text-blue-400',
    };
    
    return colorMap[recordType] || 'text-gray-600 dark:text-gray-400';
};

const getIconTextColor = (recordType: string): string => {
    // Always show colored icons (don't change on hover)
    const colorMap: Record<string, string> = {
        'Provincial Budget': 'text-purple-600 dark:text-purple-400',
        'Municipal Budget': 'text-orange-600 dark:text-orange-400',
        'Issuances / Circulars / Other References and Documents': 'text-blue-600 dark:text-blue-400',
    };
    
    return colorMap[recordType] || 'text-gray-600 dark:text-gray-400';
};

const fetchRecords = async () => {
    loading.value = true;
    error.value = '';
    
    try {
        const response = await fetch('/api/records');
        if (!response.ok) {
            throw new Error('Failed to fetch records');
        }
        
        const data = await response.json();
        records.value = Array.isArray(data) ? data : data.data || [];
    } catch (err) {
        console.error('Error fetching records:', err);
        error.value = err instanceof Error ? err.message : 'Failed to load records';
    } finally {
        loading.value = false;
    }
};

// ============== Lifecycle ==============
onMounted(() => {
    fetchRecords();
});
</script>

<style scoped>
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
