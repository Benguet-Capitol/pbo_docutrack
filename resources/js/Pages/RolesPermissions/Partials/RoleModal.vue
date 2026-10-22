<template>
    <!-- Create / Edit / View Role Modal -->
    <Teleport to="body" v-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-shield text-emerald-600 dark:text-emerald-400"></i>
                        {{ readOnly ? 'View Role' : role ? 'Edit Role' : 'Create Role' }}
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto flex-1 space-y-4">
                    <div v-if="readOnly" class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300">
                        <i class="fas fa-lock mr-1"></i>
                        {{ role?.name }} is a system role. It always has every permission and cannot be changed, so there is always an account that can repair roles.
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="role_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-id-badge absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="form.name"
                                    id="role_name"
                                    type="text"
                                    maxlength="50"
                                    :disabled="readOnly"
                                    placeholder="e.g. Records Officer"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60"
                                />
                            </div>
                            <p v-if="role && role.users_count > 0 && form.name.trim() !== role.name && !readOnly" class="text-xs text-amber-600 dark:text-amber-400">
                                Renaming also updates the {{ role.users_count }} user(s) who have this role.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label for="role_description" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-align-left absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="form.description"
                                    id="role_description"
                                    type="text"
                                    maxlength="255"
                                    :disabled="readOnly"
                                    placeholder="What this role is for (optional)"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 pt-2">
                            Permissions
                            <span class="ml-1 px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">{{ selected.size }} of {{ totalPermissions }}</span>
                        </p>
                        <div class="relative flex items-center">
                            <i class="fas fa-search absolute left-3 text-gray-400 text-xs"></i>
                            <input
                                v-model="permissionSearch"
                                type="text"
                                placeholder="Filter permissions..."
                                class="pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 w-full sm:w-56"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            v-for="group in visibleGroups"
                            :key="group.group"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
                        >
                            <label class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    :checked="groupState(group) === 'all'"
                                    :indeterminate="groupState(group) === 'some'"
                                    :disabled="readOnly"
                                    @change="toggleGroup(group)"
                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                                />
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 flex-1">{{ group.group }}</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ group.permissions.filter((p) => selected.has(p.key)).length }}/{{ group.permissions.length }}</span>
                            </label>
                            <div class="px-3 py-2 space-y-1.5">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission.key"
                                    class="flex items-start gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selected.has(permission.key)"
                                        :disabled="readOnly"
                                        @change="togglePermission(permission.key)"
                                        class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <span>{{ permission.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <p v-if="visibleGroups.length === 0" class="py-6 text-center text-xs text-gray-500 dark:text-gray-400">No permissions match "{{ permissionSearch }}".</p>

                    <div v-if="errorMessage" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-xs text-red-600 dark:text-red-400">{{ errorMessage }}</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        v-if="!readOnly"
                        @click="submit"
                        :disabled="saving || !form.name.trim() || (!!role && !hasChanges)"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 disabled:opacity-60"
                    >
                        <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                        {{ role ? 'Update' : 'Create' }}
                    </button>
                    <button
                        @click="$emit('close')"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200"
                    >
                        <i class="fas fa-times"></i>
                        {{ readOnly ? 'Close' : 'Cancel' }}
                    </button>
                    <MissingFieldsHint v-if="!readOnly" :fields="form.name.trim() ? [] : ['Role Name']" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { useFormChanges } from '@/Composables/useFormChanges';
import { ref, computed, watch } from 'vue';
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import type { Role, PermissionGroup, RolePayload } from '../Composables/useRolesData';

const props = defineProps<{
    show: boolean;
    role: Role | null;
    permissionGroups: PermissionGroup[];
    totalPermissions: number;
    saving: boolean;
    errorMessage: string | null;
}>();

const emit = defineEmits<{
    close: [];
    save: [payload: RolePayload];
}>();

const form = ref({ name: '', description: '' });
const selected = ref(new Set<string>());
const permissionSearch = ref('');

const readOnly = computed(() => !!props.role?.is_system);

// Update stays disabled until the name, description or permissions change
const { hasChanges } = useFormChanges(() => ({ form: form.value, selected: selected.value }), () => props.show);

// Reset the form each time the modal opens
watch(() => props.show, (show) => {
    if (!show) return;
    form.value = { name: props.role?.name || '', description: props.role?.description || '' };
    selected.value = new Set(props.role?.permissions || []);
    permissionSearch.value = '';
});

const visibleGroups = computed(() => {
    const query = permissionSearch.value.trim().toLowerCase();
    if (!query) return props.permissionGroups;
    return props.permissionGroups
        .map((group) => ({
            group: group.group,
            permissions: group.group.toLowerCase().includes(query)
                ? group.permissions
                : group.permissions.filter((p) => p.label.toLowerCase().includes(query)),
        }))
        .filter((group) => group.permissions.length > 0);
});

const groupState = (group: PermissionGroup): 'all' | 'some' | 'none' => {
    const count = group.permissions.filter((p) => selected.value.has(p.key)).length;
    return count === 0 ? 'none' : count === group.permissions.length ? 'all' : 'some';
};

const togglePermission = (key: string) => {
    const next = new Set(selected.value);
    next.has(key) ? next.delete(key) : next.add(key);
    selected.value = next;
};

/** Selects every (visible) permission in the group, or clears them when all are already selected */
const toggleGroup = (group: PermissionGroup) => {
    const next = new Set(selected.value);
    const selectAll = groupState(group) !== 'all';
    group.permissions.forEach((p) => (selectAll ? next.add(p.key) : next.delete(p.key)));
    selected.value = next;
};

const submit = () => {
    emit('save', {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        permissions: [...selected.value],
    });
};
</script>

<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}
</style>
