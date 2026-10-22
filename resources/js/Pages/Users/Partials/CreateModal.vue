<template>
    <!-- Create User Modal -->
    <Teleport to="body" v-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-plus text-emerald-600 dark:text-emerald-400"></i>
                        Create User
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Employee Selection -->
                        <div class="space-y-2">
                            <label for="employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee</label>
                            <!-- Searchable: every HRIS employee can have an account -->
                            <div class="relative">
                                <div class="relative flex items-center">
                                    <i class="fas fa-id-card absolute left-3 text-gray-400 text-sm"></i>
                                    <input
                                        v-model="employeeSearch"
                                        id="employee_id"
                                        type="text"
                                        autocomplete="off"
                                        :placeholder="selectedEmployee ? `${selectedEmployee.name} (${selectedEmployee.employee_id})` : 'Search name, employee ID or office'"
                                        class="block w-full pl-10 pr-8 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                        :class="selectedEmployee && !employeeSearch ? 'placeholder-gray-900 dark:placeholder-white' : ''"
                                    />
                                    <i v-if="selectedEmployee" class="fas fa-check-circle absolute right-3 text-emerald-500 text-sm" title="Employee selected"></i>
                                </div>
                                <div v-if="employeeMatches.length > 0" class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                                    <button
                                        v-for="emp in employeeMatches"
                                        :key="emp.id"
                                        type="button"
                                        @click="pickEmployee(emp)"
                                        class="w-full text-left px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-gray-700"
                                    >
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ emp.name }}</span>
                                        <span class="text-gray-500 dark:text-gray-400"> · {{ emp.employee_id }}<template v-if="emp.office"> · {{ emp.office.office_abbreviation }}</template></span>
                                    </button>
                                </div>
                                <p v-else-if="employeeSearch.trim().length >= 2" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">No employee matches "{{ employeeSearch }}".</p>
                            </div>
                            <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                        </div>

                        <!-- Name (Auto-filled from Employee) -->
                        <div class="space-y-2">
                            <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name (Auto-filled)</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.name"
                                    id="name"
                                    type="text"
                                    placeholder="Name"
                                    readonly
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                        </div>

                        <!-- Username -->
                        <div class="space-y-2">
                            <label for="username" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Username</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-at absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.username"
                                    id="username"
                                    type="text"
                                    placeholder="Username"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.username" class="text-red-500 text-xs">{{ formErrors.username }}</span>
                        </div>

                        <!-- Office (Auto-filled from Employee) -->
                        <div class="space-y-2">
                            <label for="office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office (Auto-filled from Employee)</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="formData.office"
                                    id="office"
                                    :disabled="!formData.employee_id"
                                    :class="['block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none appearance-none', !formData.employee_id ? 'bg-gray-100 border-gray-300 cursor-not-allowed opacity-60' : 'border-gray-300 focus:border-emerald-500 bg-white dark:bg-gray-700']"
                                >
                                    <option value="">Select Office</option>
                                    <option v-for="office in offices" :key="office.id" :value="office.id">
                                        {{ office.office_name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="formErrors.office" class="text-red-500 text-xs">{{ formErrors.office }}</span>
                        </div>

                        <!-- Role -->
                        <div class="space-y-2">
                            <label for="usertype" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-users absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model="formData.usertype"
                                    id="usertype"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option value="">Select Role</option>
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
                            </div>
                            <span v-if="formErrors.usertype" class="text-red-500 text-xs">{{ formErrors.usertype }}</span>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Password</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-lock absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.password"
                                    id="password"
                                    type="password"
                                    placeholder="Password"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.password" class="text-red-500 text-xs">{{ formErrors.password }}</span>
                        </div>

                        <!-- Password Confirmation -->
                        <div class="space-y-2">
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-lock absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.password_confirmation"
                                    id="password_confirmation"
                                    type="password"
                                    placeholder="Confirm Password"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <span v-if="formErrors.password_confirmation" class="text-red-500 text-xs">{{ formErrors.password_confirmation }}</span>
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
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <MissingFieldsHint :fields="missingFields" :message="submitMessage" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import MissingFieldsHint from '@/Components/MissingFieldsHint.vue';
import { computed, ref, watch } from 'vue';
import type { FormData } from '../Composables/useUserForm';
import type { Office, Employee } from '../Composables/useUserData';

const props = defineProps({
    showCreateModal: {
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
    employees: {
        type: Array as () => Employee[],
        required: true,
    },
});

// ============== Required fields: Save/Create stays disabled until they're filled ==============
const missingFields = computed(() => {
    const f: any = props.formData || {};
    const missing: string[] = [];
    if (!f.employee_id) missing.push("Employee");
    if (!String(f.username ?? '').trim()) missing.push("Username");
    if (!String(f.usertype ?? '').trim()) missing.push("Role");
    if (!f.office) missing.push("Office");
    if (!String(f.password ?? '').trim()) missing.push("Password");
    if (!String(f.password_confirmation ?? '').trim()) missing.push("Confirm Password");
    return missing;
});
const submitMessage = computed<string | null>(() => {
    const f: any = props.formData || {};
    return f.password && f.password.length < 6 ? 'Password must be at least 6 characters' : (f.password && f.password_confirmation && f.password !== f.password_confirmation ? 'Passwords do not match' : null);
});
const canSubmit = computed(() => missingFields.value.length === 0 && !submitMessage.value);

const emit = defineEmits<{
    'close': [];
    'save': [];
    'employee-change': [];
}>();

const onClose = () => {
    emit('close');
};

const onSave = () => {
    emit('save');
};

const onEmployeeChange = () => {
    emit('employee-change');
};

// ============== Employee search (all HRIS employees) ==============
const employeeSearch = ref('');

const selectedEmployee = computed(() =>
    (props.employees as any[] || []).find((emp: any) => emp.id === Number(props.formData.employee_id)) || null
);

const employeeMatches = computed(() => {
    const query = employeeSearch.value.trim().toLowerCase();
    if (query.length < 2) return [];
    return (props.employees as any[] || [])
        .filter((emp: any) => !emp.separated)
        .filter((emp: any) => emp.name?.toLowerCase().includes(query)
            || String(emp.employee_id ?? '').toLowerCase().includes(query)
            || emp.office?.office_abbreviation?.toLowerCase().includes(query))
        .slice(0, 10);
});

const pickEmployee = (emp: any) => {
    props.formData.employee_id = emp.id;
    employeeSearch.value = '';
    onEmployeeChange();
};

// Fresh search each time the modal opens
watch(() => props.showCreateModal, () => {
    employeeSearch.value = '';
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
