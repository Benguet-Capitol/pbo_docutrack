<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
            v-for="user in users"
            :key="user.id"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 border-t-emerald-500 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
        >
            <div class="px-4 pt-3 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-600 dark:bg-emerald-700 text-white flex items-center justify-center flex-shrink-0 text-sm font-bold">
                    {{ initials(user.name) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight break-words">{{ user.name }}</h4>
                    <p class="text-gray-600 dark:text-gray-300 truncate" :title="user.username">
                        <i class="fas fa-at text-[10px] text-gray-400"></i> {{ user.username }}
                    </p>
                    <span v-if="user.usertype" class="mt-1.5 inline-block px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 text-[11px] font-semibold">
                        {{ user.usertype }}
                    </span>
                </div>
            </div>

            <div class="flex-1"></div>
            <div v-if="canManageUsers" class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <RowIconButton color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', user)" />
                <RowIconButton color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', user)" />
            </div>
            <div v-else class="mt-3"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import type { User } from '../Composables/useUserData';

defineProps<{
    users: User[];
    canManageUsers: boolean;
}>();

defineEmits<{
    'edit': [user: User];
    'delete': [user: User];
}>();

// Initials for the avatar, e.g. "Juan D. Cruz" -> "JC"
const initials = (name: string) => {
    const words = String(name ?? '').replace(/[.,]/g, ' ').split(/\s+/).filter(Boolean);
    return ((words[0]?.[0] ?? '') + (words.length > 1 ? words[words.length - 1][0] : '')).toUpperCase() || '?';
};
</script>
