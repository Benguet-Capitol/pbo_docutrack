<template>
    <div class="space-y-4">
        <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
            <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
            Transaction History
        </h4>
        
        <!-- No Transactions State -->
        <div v-if="document.transactions.length === 0" class="py-6 text-center">
            <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
            <p class="text-xs text-gray-600 dark:text-gray-400">No transactions found</p>
        </div>

        <!-- Transactions Timeline (Grid) -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-4">
            <div v-for="(transaction, index) in [...document.transactions].reverse()" :key="transaction.id" class="relative">
                <!-- Timeline Dot -->
                <div class="absolute -top-3 left-3 z-10">
                    <div v-if="getActionType(transaction.action) === 'created'" class="w-5 h-5 rounded-full bg-green-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                    <div v-else-if="getActionType(transaction.action) === 'forwarded'" class="w-5 h-5 rounded-full bg-cyan-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                        <i class="fas fa-arrow-right text-white text-xs"></i>
                    </div>
                    <div v-else-if="getActionType(transaction.action) === 'received'" class="w-5 h-5 rounded-full bg-orange-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                        <i class="fas fa-inbox text-white text-xs"></i>
                    </div>
                    <div v-else-if="getActionType(transaction.action) === 'finalized'" class="w-5 h-5 rounded-full bg-purple-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                        <i class="fas fa-check-double text-white text-xs"></i>
                    </div>
                </div>

                <!-- Transaction Card -->
                <div class="pt-6 p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm h-full">
                    <!-- Action Badge -->
                    <div class="mb-2">
                        <span v-if="getActionType(transaction.action) === 'created'" class="inline-block px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Created</span>
                        <span v-else-if="getActionType(transaction.action) === 'forwarded'" class="inline-block px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                        <span v-else-if="getActionType(transaction.action) === 'received'" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">Received</span>
                        <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Transaction Ended</span>
                    </div>

                    <!-- Date/Time -->
                    <div class="flex gap-3 mb-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            <i class="fas fa-calendar text-gray-400 dark:text-gray-500 mr-1"></i>
                            {{ new Date(transaction.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            <i class="fas fa-clock text-gray-400 dark:text-gray-500 mr-1"></i>
                            {{ new Date(transaction.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                        </p>
                    </div>

                    <!-- User and Duration -->
                    <div class="flex gap-2 mb-2 items-start">
                        <p class="text-xs text-gray-700 dark:text-gray-300 break-words font-semibold flex-1">
                            {{ transaction.user?.name || 'Unknown User' }}
                        </p>
                        <p v-if="transaction.duration_hours !== null && transaction.duration_hours !== undefined" class="text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">
                            <i class="fas fa-hourglass-end mr-1"></i>
                            {{ formatDuration(transaction.duration_hours) }}
                        </p>
                    </div>

                    <!-- Action -->
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                        <i class="fas fa-tasks text-gray-400 dark:text-gray-500 mr-1"></i>
                        {{ transaction.action }}
                    </p>

                    <!-- Recipient (if forwarded) -->
                    <p v-if="getActionType(transaction.action) === 'forwarded' && (transaction.forwardedToUser || transaction.forwardedToOffice || transaction.forwardedToMunicipality)" class="text-xs text-gray-600 dark:text-gray-400 mb-2 break-words">
                        <i class="fas fa-arrow-right text-cyan-500 mr-1"></i>
                        <span v-if="transaction.forwardedToUser">{{ transaction.forwardedToUser.name }}</span>
                        <span v-else-if="transaction.forwardedToOffice">{{ transaction.forwardedToOffice.office_name }}</span>
                        <span v-else-if="transaction.forwardedToMunicipality">{{ transaction.forwardedToMunicipality.name }}</span>
                    </p>

                    <!-- Remarks -->
                    <p v-if="transaction.remarks" style="white-space: pre-wrap; word-break: break-word;" class="text-xs text-gray-600 dark:text-gray-400 italic border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                        {{ transaction.remarks }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    document: any;
    formatDuration: (hours: number) => string;
    getActionType: (action: string) => string;
}>();
</script>
