<template>
    <!-- Card View: used on small screens, or when chosen on desktop -->
    <div :class="CARD_GRID">
        <RecordCard
            v-for="user in users"
            :key="user.id"
            accent="emerald"
            :title="user.name"
        >
            <template #icon>
                <div :class="CARD_AVATAR">{{ initials(user.name) }}</div>
            </template>
            <template #subtitle><i class="fas fa-at mr-1 text-[10px]"></i>{{ user.username }}</template>
            <CardField label="Role" icon="fas fa-user-shield">
                <span v-if="user.usertype" :class="[LIST_TAG, 'rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300']">{{ user.usertype }}</span>
                <template v-else>—</template>
            </CardField>

            <template v-if="canManageUsers" #actions>
                <RowIconButton color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click.stop="$emit('edit', user)" />
                <RowIconButton color="red" icon="fas fa-trash-alt" tooltip="Delete" @click.stop="$emit('delete', user)" />
            </template>
        </RecordCard>
    </div>
</template>

<script setup lang="ts">
import RowIconButton from '@/Components/RowIconButton.vue';
import CardField from '@/Components/CardField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { CARD_AVATAR, CARD_GRID } from '@/utils/cardView';
import { LIST_TAG } from '@/utils/listTable';
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
