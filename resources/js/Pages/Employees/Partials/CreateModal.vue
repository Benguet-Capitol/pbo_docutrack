<template>
    <!-- Create Employee Modal -->
    <Teleport to="body" v-if="showCreateModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="onClose">
            <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-plus text-emerald-600 dark:text-emerald-400"></i>
                        Create Employee
                    </h3>
                    <button @click="onClose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">

                        <!-- Employee ID Search Field -->
                        <div class="space-y-2">
                            <label for="create_employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Employee ID
                            </label>
                            <div class="relative flex items-center">
                                <i class="fas fa-id-card absolute left-3 text-gray-400 text-sm z-10"></i>
                                <input
                                    v-model="searchQuery"
                                    id="create_employee_id"
                                    type="text"
                                    placeholder="Type to search Employee ID or Name..."
                                    autocomplete="off"
                                    @input="onSearchInput"
                                    @keydown.escape="closeDropdown"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                                />
                                <!-- Loading spinner -->
                                <i v-if="isFetching" class="fas fa-spinner fa-spin absolute right-3 text-gray-400 text-xs"></i>
                            </div>

                            <!-- Autocomplete Dropdown -->
                            <div
                                v-if="showDropdown"
                                class="absolute z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-52 overflow-y-auto text-xs"
                                style="width: calc(100% - 3rem);"
                            >
                                <div v-if="!dropdownResults.length" class="px-4 py-3 text-gray-400 dark:text-gray-500 italic">
                                    No employees found.
                                </div>
                                <div
                                    v-for="emp in dropdownResults"
                                    :key="emp.employee_id_number"
                                    @mousedown.prevent="selectEmployee(emp)"
                                    class="px-4 py-2 cursor-pointer hover:bg-emerald-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                >
                                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ emp.employee_id_number }}</div>
                                    <div class="text-gray-600 dark:text-gray-300">{{ formatName(emp) }}</div>
                                    <div class="text-gray-400 dark:text-gray-500 text-[10px]">
                                        {{ emp.position_title ?? '' }} &middot; {{ emp.office_desc ?? '' }}
                                    </div>
                                </div>
                            </div>

                            <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                        </div>

                        <!-- Name Field (auto-filled, readonly) -->
                        <div class="space-y-2">
                            <label for="create_name" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.name"
                                    id="create_name"
                                    type="text"
                                    placeholder="Auto-filled"
                                    readonly
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-600 dark:border-gray-600 dark:text-gray-300 focus:outline-none cursor-not-allowed"
                                />
                            </div>
                            <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                        </div>

                        <!-- Designation Field (auto-filled, readonly) -->
                        <div class="space-y-2">
                            <label for="create_designation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Designation</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-briefcase absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    v-model="formData.designation"
                                    id="create_designation"
                                    type="text"
                                    placeholder="Auto-filled"
                                    readonly
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-600 dark:border-gray-600 dark:text-gray-300 focus:outline-none cursor-not-allowed"
                                />
                            </div>
                            <span v-if="formErrors.designation" class="text-red-500 text-xs">{{ formErrors.designation }}</span>
                        </div>

                        <!-- Office Field -->
                        <div class="space-y-2">
                            <label for="create_office" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                <select
                                    v-model.number="formData.office"
                                    id="create_office"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                >
                                    <option :value="''">Select Office</option>
                                    <option v-for="office in offices" :key="office.id" :value="office.id">
                                        {{ office.office_name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="formErrors.office" class="text-red-500 text-xs">{{ formErrors.office }}</span>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button
                        @click="onSave"
                        :disabled="isCheckingUnique"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i :class="isCheckingUnique ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                        {{ isCheckingUnique ? 'Checking...' : 'Save' }}
                    </button>
                    <button
                        @click="onClose"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { FormData } from '../Composables/useEmployeeForm';
import type { Office } from '../Composables/useEmployeeData';

const API_URL = 'http://192.168.2.26/api/v1/getEmployees';
const API_KEY = '2idqUEqD16WlkMwoWohuluNqFIm9ZqKmsw4GuSsM15E';

interface ApiEmployee {
    employee_id_number: string;
    fullname?: string;
    fname: string;
    mname?: string;
    lname: string;
    suffix?: string;
    position_title?: string;
    office_desc?: string;
    type?: string;
}

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
});

const emit = defineEmits<{
    'close': [];
    'save': [];
}>();

// ============== Search State ==============
const searchQuery = ref('');
const allApiEmployees = ref<ApiEmployee[]>([]);
const dropdownResults = ref<ApiEmployee[]>([]);
const showDropdown = ref(false);
const isFetching = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

// ============== Fetch API employees once when modal opens ==============
watch(() => props.showCreateModal, async (val) => {
    if (val) {
        searchQuery.value = '';
        showDropdown.value = false;
        dropdownResults.value = [];
        if (!allApiEmployees.value.length) {
            await fetchApiEmployees();
        }
    }
});

async function fetchApiEmployees() {
    isFetching.value = true;
    try {
        const res = await fetch(API_URL, {
            method: 'GET',
            headers: {
                'X-API-KEY': API_KEY,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        allApiEmployees.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch (e) {
        console.error('Failed to fetch employees from API:', e);
        allApiEmployees.value = [];
    } finally {
        isFetching.value = false;
    }
}

// ============== Search / Filter ==============
function onSearchInput() {
    // Clear autofilled fields when user types again
    props.formData.employee_id = '';
    props.formData.name = '';
    props.formData.designation = '';
    props.formData.office = '';

    if (searchTimer) clearTimeout(searchTimer);

    const q = searchQuery.value.trim();
    if (!q) {
        closeDropdown();
        return;
    }

    searchTimer = setTimeout(() => {
        const lower = q.toLowerCase();
        dropdownResults.value = allApiEmployees.value
            .filter(emp =>
                (emp.employee_id_number?.toLowerCase().includes(lower)) ||
                (emp.fullname?.toLowerCase().includes(lower)) ||
                (formatName(emp).toLowerCase().includes(lower))
            )
            .slice(0, 10);
        showDropdown.value = true;
    }, 250);
}

function closeDropdown() {
    showDropdown.value = false;
}

// ============== Select an employee from the dropdown ==============
function selectEmployee(emp: ApiEmployee) {
    const formatted = formatName(emp);

    // Fill search input with the employee ID
    searchQuery.value = emp.employee_id_number;

    // Fill form fields
    props.formData.employee_id = emp.employee_id_number;
    props.formData.name = formatted;
    props.formData.designation = formatDesignation(emp);

    // Match office from office_desc to the offices list
    matchOffice(emp.office_desc ?? '');

    closeDropdown();

    // Clear related errors
    if (props.formErrors.employee_id) delete props.formErrors.employee_id;
    if (props.formErrors.name) delete props.formErrors.name;
    if (props.formErrors.designation) delete props.formErrors.designation;
}

// ============== Format designation: append (Casual) if type is casual ==============
function formatDesignation(emp: ApiEmployee): string {
    const title = (emp.position_title ?? '').trim();
    const isCasual = emp.type?.toLowerCase() === 'casual';
    return isCasual && title ? `${title} (Casual)` : title;
}

// ============== Format name: Juan B. Dela Cruz Jr. ==============
function formatName(emp: ApiEmployee): string {
    const fname = (emp.fname ?? '').trim();
    const mname = (emp.mname ?? '').trim();
    const lname = (emp.lname ?? '').trim();
    const suffix = (emp.suffix ?? '').trim();
    const isValidMiddle = mname && mname.toLowerCase() !== 'n/a';
    const middleInitial = isValidMiddle ? mname.charAt(0).toUpperCase() + '.' : '';
    return [fname, middleInitial, lname, suffix].filter(Boolean).join(' ');
}

// ============== Match office_desc to offices list ==============
function matchOffice(officeDesc: string) {
    if (!officeDesc) return;

    const q = officeDesc.toLowerCase().trim();

    // Try exact or strong partial match on office_name or abbreviation
    for (const office of props.offices) {
        const nameMatch = office.office_name?.toLowerCase().includes(q) || q.includes(office.office_name?.toLowerCase());
        if (nameMatch) {
            props.formData.office = office.id;
            return;
        }
    }

    // Fallback: partial word match
    const words = q.split(' ').filter(w => w.length > 2);
    for (const office of props.offices) {
        const officeText = office.office_name?.toLowerCase() ?? '';
        if (words.some(w => officeText.includes(w))) {
            props.formData.office = office.id;
            return;
        }
    }
}

// ============== Uniqueness check state ==============
const isCheckingUnique = ref(false);

// ============== Emit handlers ==============
const onClose = () => {
    closeDropdown();
    searchQuery.value = '';
    emit('close');
};

const onSave = async () => {
    // Must have selected from dropdown (employee_id is only set on selection)
    if (!props.formData.employee_id.trim()) {
        props.formErrors.employee_id = 'Please select a valid Employee ID or Name from the search results.';
        return;
    }

    if (isCheckingUnique.value) return;
    isCheckingUnique.value = true;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const params = new URLSearchParams({ employee_id: props.formData.employee_id });

        const res = await fetch(`/employees/check-unique?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await res.json();

        if (!data.is_unique) {
            props.formErrors.employee_id = 'The Employee ID or Name already exists';
            return;
        }

        // Clear any previous error and proceed
        delete props.formErrors.employee_id;
        emit('save');
    } catch {
        // On network error, allow submission to proceed (server will validate)
        emit('save');
    } finally {
        isCheckingUnique.value = false;
    }
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