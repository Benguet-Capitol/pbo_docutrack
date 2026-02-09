<template>
    <div class="fixed top-4 right-4 z-50 space-y-2 pointer-events-none">
        <transition-group name="toast" tag="div">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="pointer-events-auto transition-all duration-300 animate-slideIn"
                :class="[
                    'px-4 py-3 rounded-lg shadow-lg flex items-start gap-3 max-w-md',
                    notification.severity === 'success'
                        ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800'
                        : notification.severity === 'info'
                        ? 'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800'
                        : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800'
                ]"
            >
                <div
                    :class="[
                        'flex-shrink-0 text-xl mt-0.5',
                        notification.severity === 'success'
                            ? 'text-green-600 dark:text-green-400'
                            : notification.severity === 'info'
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-red-600 dark:text-red-400'
                    ]"
                >
                    <i :class="notification.severity === 'success' ? 'fas fa-check-circle' : notification.severity === 'info' ? 'fas fa-info-circle' : 'fas fa-exclamation-circle'"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 :class="[
                        'font-semibold text-sm',
                        notification.severity === 'success'
                            ? 'text-green-900 dark:text-green-100'
                            : notification.severity === 'info'
                            ? 'text-blue-900 dark:text-blue-100'
                            : 'text-red-900 dark:text-red-100'
                    ]">
                        {{ notification.summary }}
                    </h4>
                    <p :class="[
                        'text-xs mt-1',
                        notification.severity === 'success'
                            ? 'text-green-700 dark:text-green-200'
                            : notification.severity === 'info'
                            ? 'text-blue-700 dark:text-blue-200'
                            : 'text-red-700 dark:text-red-200'
                    ]" v-html="notification.detail">
                    </p>
                </div>
                <button
                    @click="removeNotification(notification.id)"
                    :class="[
                        'flex-shrink-0 text-lg hover:opacity-70 transition-opacity',
                        notification.severity === 'success'
                            ? 'text-green-600 dark:text-green-400'
                            : notification.severity === 'info'
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-red-600 dark:text-red-400'
                    ]"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface Notification {
    id: number;
    severity: 'success' | 'error' | 'info';
    summary: string;
    detail: string;
    life: number;
}

const notifications = ref<Notification[]>([]);
let notificationId = 0;

/**
 * Add a notification to the toast container
 * @param severity - 'success' (green), 'info' (blue), or 'error' (red)
 * @param summary - Notification title
 * @param detail - Notification message
 * @param life - Duration in milliseconds before auto-removal
 */
const add = (severity: 'success' | 'info' | 'error', summary: string, detail: string, life: number = 3000) => {
    const id = ++notificationId;
    notifications.value.push({
        id,
        severity,
        summary,
        detail,
        life
    });

    // Auto-remove notification after specified time
    setTimeout(() => {
        removeNotification(id);
    }, life);
};

/**
 * Remove a notification by ID
 */
const removeNotification = (id: number) => {
    notifications.value = notifications.value.filter(n => n.id !== id);
};

// Expose methods for external use
defineExpose({
    add,
    removeNotification
});
</script>

<style scoped>
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100%) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0) translateY(0);
    }
}

.animate-slideIn {
    animation: slideIn 0.3s ease-out;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
