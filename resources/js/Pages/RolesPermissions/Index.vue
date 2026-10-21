<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-200">
                Roles &amp; Permissions
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button
                            @click="openCreate"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200"
                        >
                            <i class="fas fa-plus"></i>
                            Create Role
                        </button>
                        <div class="relative flex items-center w-full sm:w-80">
                            <i class="fas fa-search text-gray-400 absolute left-3"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search roles..."
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        A role's permissions decide which modules its users see and what they can create, edit or delete. Changes apply on each user's next page load.
                    </p>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Loading roles...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="m-6 px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-300"><i class="fas fa-exclamation-circle mr-2"></i>{{ error }}</p>
                </div>

                <!-- Role Cards -->
                <div v-else class="p-4">
                    <div v-if="filteredRoles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="role in filteredRoles"
                            :key="role.id"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-t-4 rounded-lg shadow-sm hover:shadow-md transition-shadow text-xs flex flex-col"
                            :class="role.is_system ? 'border-t-amber-500' : 'border-t-emerald-500'"
                        >
                            <div class="px-4 pt-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                        :class="role.is_system ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'"
                                    >
                                        <i :class="role.is_system ? 'fas fa-lock' : 'fas fa-user-shield'"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 break-words">{{ role.name }}</h4>
                                            <span v-if="role.is_system" class="px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 text-[10px] font-semibold uppercase">System</span>
                                            <span v-if="role.name === currentRole" class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-[10px] font-semibold uppercase">Your role</span>
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-300 line-clamp-2 break-words mt-0.5">{{ role.description || 'No description' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mx-4 mt-3 grid grid-cols-2 gap-2">
                                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Users</div>
                                    <div class="text-base font-bold text-gray-800 dark:text-gray-200">{{ role.users_count }}</div>
                                </div>
                                <div class="rounded-lg border border-gray-100 dark:border-gray-700 px-2 py-1.5">
                                    <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Permissions</div>
                                    <div class="text-base font-bold text-emerald-700 dark:text-emerald-300">{{ role.permissions.length }}<span class="text-xs font-medium text-gray-500"> / {{ totalPermissions }}</span></div>
                                </div>
                            </div>

                            <!-- Modules this role can open -->
                            <div class="mx-4 mt-3 flex flex-wrap gap-1">
                                <span
                                    v-for="group in groupsWithAccess(role)"
                                    :key="group"
                                    class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px]"
                                >{{ group }}</span>
                            </div>

                            <div class="flex-1"></div>
                            <div class="mt-3 flex justify-end gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                                <RowIconButton v-if="role.is_system" color="purple" icon="fas fa-eye" tooltip="View" @click="openEdit(role)" />
                                <template v-else>
                                    <RowIconButton color="blue" icon="fas fa-pencil-alt" tooltip="Edit" @click="openEdit(role)" />
                                    <RowIconButton color="red" icon="fas fa-trash-alt" tooltip="Delete" @click="openDelete(role)" />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-search text-3xl opacity-40 mb-2 block"></i>
                        <p class="text-sm">No roles match your search.</p>
                    </div>
                </div>
            </div>
        </div>

        <RoleModal
            :show="showRoleModal"
            :role="editingRole"
            :permission-groups="permissionGroups"
            :total-permissions="totalPermissions"
            :saving="saving"
            :error-message="modalError"
            @close="showRoleModal = false"
            @save="handleSave"
        />

        <DeleteModal
            :show="showDeleteModal"
            :role="roleToDelete"
            :deleting="deleting"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import RowIconButton from '@/Components/RowIconButton.vue';
import RoleModal from './Partials/RoleModal.vue';
import DeleteModal from './Partials/DeleteModal.vue';
import { useRolesData, type Role, type RolePayload } from './Composables/useRolesData';

const toastRef = ref<InstanceType<typeof Toast> | null>(null);

const { permissionGroups, loading, error, searchQuery, totalPermissions, filteredRoles, fetchRoles, saveRole, deleteRole } = useRolesData();

const page = usePage();
const currentRole = computed(() => (page.props.auth?.user as any)?.usertype || '');

/** Module groups where the role has at least one permission */
const groupsWithAccess = (role: Role) =>
    permissionGroups.value
        .filter((group) => group.permissions.some((p) => role.permissions.includes(p.key)))
        .map((group) => group.group);

// ============== Create / Edit ==============
const showRoleModal = ref(false);
const editingRole = ref<Role | null>(null);
const saving = ref(false);
const modalError = ref<string | null>(null);

const openCreate = () => {
    editingRole.value = null;
    modalError.value = null;
    showRoleModal.value = true;
};

const openEdit = (role: Role) => {
    editingRole.value = role;
    modalError.value = null;
    showRoleModal.value = true;
};

const handleSave = async (payload: RolePayload) => {
    saving.value = true;
    modalError.value = null;
    const affectsCurrentUser = editingRole.value?.name === currentRole.value;
    try {
        const message = await saveRole(payload, editingRole.value?.id);
        showRoleModal.value = false;
        toastRef.value?.add(editingRole.value ? 'info' : 'success', 'Success', message, 3000);
        // The signed-in user's own permissions changed: refresh the shared props (menu, buttons)
        if (affectsCurrentUser) router.reload();
    } catch (e) {
        modalError.value = e instanceof Error ? e.message : 'An error occurred';
    } finally {
        saving.value = false;
    }
};

// ============== Delete ==============
const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);
const deleting = ref(false);

const openDelete = (role: Role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const handleDelete = async () => {
    if (!roleToDelete.value) return;
    deleting.value = true;
    try {
        const message = await deleteRole(roleToDelete.value.id);
        showDeleteModal.value = false;
        toastRef.value?.add('error', 'Deleted', message, 3000);
    } catch (e) {
        toastRef.value?.add('error', 'Error', e instanceof Error ? e.message : 'An error occurred', 4000);
    } finally {
        deleting.value = false;
    }
};

onMounted(fetchRoles);
</script>
