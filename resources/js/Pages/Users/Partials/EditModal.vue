<template>
    <!-- Edit User Modal -->
    <Teleport to="body" v-if="showEditModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                        Edit User
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <label for="edit_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.name"
                                    id="edit_name"
                                    type="text"
                                    placeholder="Name"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                />
                            </div>
                            <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                        </div>

                        <!-- Username -->
                        <div class="space-y-2">
                            <label for="edit_username" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Username</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-at absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.username"
                                    id="edit_username"
                                    type="text"
                                    placeholder="Username"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                                />
                            </div>
                            <span v-if="formErrors.username" class="text-red-500 text-xs">{{ formErrors.username }}</span>
                        </div>

                        <!-- Role -->
                        <div class="space-y-2">
                            <label for="edit_usertype" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-users absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.usertype"
                                    id="edit_usertype"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                >
                                    <option value="">Select Role</option>
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
                            </div>
                            <span v-if="formErrors.usertype" class="text-red-500 text-xs">{{ formErrors.usertype }}</span>
                        </div>

                        <!-- Office -->
                        <div class="space-y-2">
                            <label for="edit_office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="formData.office"
                                    id="edit_office"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 appearance-none"
                                >
                                    <option value="">Select Office</option>
                                    <option v-for="office in offices" :key="office.id" :value="office.id">
                                        {{ office.office_name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="formErrors.office" class="text-red-500 text-xs">{{ formErrors.office }}</span>
                        </div>

                        <!-- Reset Password: optional; blank keeps the current password -->
                        <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <i class="fas fa-key text-amber-500"></i>
                                    Reset Password
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave blank to keep the current password. Give the new password to the user.</p>
                            </div>

                            <div class="space-y-2">
                                <label for="edit_password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">New Password</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-lock absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.password"
                                        id="edit_password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="New Password (min. 6 characters)"
                                        autocomplete="new-password"
                                        class="block w-full pl-10 pr-10 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        :title="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                <span v-if="formErrors.password" class="text-red-500 text-xs">{{ formErrors.password }}</span>
                            </div>

                            <div class="space-y-2">
                                <label for="edit_password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Confirm New Password</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-lock absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="formData.password_confirmation"
                                        id="edit_password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Confirm New Password"
                                        autocomplete="new-password"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                    />
                                </div>
                                <span v-if="formErrors.password_confirmation" class="text-red-500 text-xs">{{ formErrors.password_confirmation }}</span>
                            </div>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex flex-wrap items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button :disabled="!canSubmit"
                        @click="onSave"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <MissingFieldsHint :fields="missingFields" :message="submitMessage || (hasChanges ? null : NO_CHANGES_MESSAGE)" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import { useFormChanges, NO_CHANGES_MESSAGE } from '@/Composables/useFormChanges';
import { ref, watch, computed } from 'vue';
import type { FormData } from '../Composables/useUserForm';
import type { Office } from '../Composables/useUserData';

const props = defineProps({
    showEditModal: {
        type: Boolean,
        required: true,
    },
    formData: {
        type: Object as () => FormData,
        required: true,
    },
    formErrors: {
        type: Object as () => Record<string, string>,
        required: true,
    },
    offices: {
        type: Array as () => Office[],
        required: true,
    },
    roles: {
        type: Array as () => string[],
        required: true,
    },
});

// ============== Required fields: Save/Create stays disabled until they're filled ==============
const missingFields = computed(() => {
    const f: any = props.formData || {};
    const missing: string[] = [];
    if (!String(f.name ?? '').trim()) missing.push("Name");
    if (!String(f.username ?? '').trim()) missing.push("Username");
    if (!String(f.usertype ?? '').trim()) missing.push("Role");
    if (!f.office) missing.push("Office");
    return missing;
});
const submitMessage = computed<string | null>(() => {
    const f: any = props.formData || {};
    return (f.password || f.password_confirmation) ? (String(f.password ?? '').length < 6 ? 'New password must be at least 6 characters' : (f.password !== f.password_confirmation ? 'Passwords do not match' : null)) : null;
});
// Update stays disabled until something is changed
const { hasChanges } = useFormChanges(() => props.formData, () => !!props.showEditModal);
const canSubmit = computed(() => missingFields.value.length === 0 && !submitMessage.value && hasChanges.value);

const emit = defineEmits<{
    'close': [];
    'save': [];
}>();

const onClose = () => {
    emit('close');
};

const onSave = () => {
    emit('save');
};

/** Reveals the new password while typing; hidden again each time the modal opens */
const showPassword = ref(false);
watch(() => props.showEditModal, () => {
    showPassword.value = false;
});
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
