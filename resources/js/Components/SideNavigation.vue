<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Props {
    show: boolean;
    isOpen?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
});

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const isSidebarOpen = ref(props.isOpen);
const isSidebarHovered = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    emit('update:isOpen', isSidebarOpen.value);
};
const page = usePage();

const auth = computed(() => page.props.auth.user);
const userInitial = computed(() => auth.value?.name?.charAt(0).toUpperCase() || 'U');
const usertype = computed(() => auth.value?.usertype || '');

/**
 * Check if current user can view a specific navigation item
 */
const canViewItem = (item: string): boolean => {
    const role = usertype.value;
    
    // Developer, Administrator, and Administrative can view Leaves, Pass Slips, Time Slips, Travel Orders, Tardiness
    if (item === 'leaves' || item === 'pass-slips' || item === 'time-slips' || item === 'travel-orders' || item === 'tardiness') {
        return ['Developer', 'Administrator', 'Administrative'].includes(role);
    }
    
    // Only Developer, Administrator, Administrative, and Receiving can view Certificates
    if (item === 'certificates-of-appearance') {
        return ['Developer', 'Administrator', 'Administrative', 'Receiving'].includes(role);
    }
    
    // Everyone can view Documents and Records
    if (item === 'documents' || item === 'records') return true;
    
    // Only Developer and Administrator can view Users
    if (item === 'users') return role === 'Developer' || role === 'Administrator';
    
    // Developer, Administrator, and Administrative can view Employees
    if (item === 'employees') return ['Developer', 'Administrator', 'Administrative'].includes(role);
    
    // Everyone except none can view Offices and Municipalities
    if (item === 'offices' || item === 'municipalities') return true;
    
    return false;
};

const handleSidebarHover = (hovered: boolean) => {
    isSidebarHovered.value = hovered;
};
</script>

<template>
    <!-- Side Navigation -->
    <aside
        v-show="show"
        class="fixed inset-y-0 z-20 flex flex-col py-4 space-y-6 bg-gradient-to-b from-white via-gray-50 to-white shadow-xl dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
        :class="[
            isSidebarOpen || isSidebarHovered ? 'translate-x-0 w-64' : '-translate-x-full w-64 md:w-16 md:translate-x-0',
        ]"
        style="transition-property: width, transform; transition-duration: 200ms; box-shadow: rgba(0, 0, 0, 0.1) 8px 0 16px;"
        @mouseenter="handleSidebarHover(true)"
        @mouseleave="handleSidebarHover(false)"
    >
        <!-- Header Section -->
        <div class="flex items-center justify-between flex-shrink-0 px-3">
            <!-- Logo -->
            <Link
                :href="route('dashboard')"
                class="inline-flex items-center gap-2"
            >
                <!-- Logo Icon -->
                <i class="ml-1 mr-1 fas fa-hexagon-nodes text-3xl text-emerald-600 dark:text-emerald-400"></i>

                <span v-show="isSidebarOpen || isSidebarHovered" class="text-xl font-semibold text-gray-900 dark:text-white">PBO|DocuTrack</span>

                <span class="sr-only">Dashboard</span>
            </Link>

            <!-- Toggle button -->
            <button
                v-show="isSidebarOpen || isSidebarHovered"
                type="button"
                @click="toggleSidebar"
                class="hidden lg:block p-2 text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <i v-if="!isSidebarOpen" class="fas fa-chevron-right text-lg"></i>
                <i v-else class="fas fa-chevron-left text-lg"></i>
            </button>

            <button
                v-show="isSidebarOpen || isSidebarHovered"
                type="button"
                @click="toggleSidebar"
                class="lg:hidden p-2 text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Navigation Content -->
        <nav class="flex flex-col flex-1 gap-2 px-2 overflow-y-auto">
            <!-- Dashboard Link -->
            <Link
                :href="route('dashboard')"
                :class="[
                    route().current('dashboard')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-tachometer-alt text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Dashboard</span>
            </Link>

            <!-- Section Divider -->
            <div
                v-show="isSidebarOpen || isSidebarHovered"
                class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 px-3 pt-4 pb-2 mt-2 border-b-2 border-emerald-200 dark:border-emerald-900 transition-opacity"
            >
                Main
            </div>

            <!-- Documents Link -->
            <Link
                :href="route('documents.index')"
                :class="[
                    route().current('documents.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-file-alt text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Documents</span>
            </Link>

            <!-- Records Link -->
            <Link
                v-if="canViewItem('records')"
                :href="route('records.index')"
                :class="[
                    route().current('records.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-archive text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Records</span>
            </Link>

            <!-- Leaves Link -->
            <Link
                v-if="canViewItem('leaves')"
                :href="route('leaves.index')"
                :class="[
                    route().current('leaves.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-calendar-alt text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Leaves</span>
            </Link>

            <!-- Travel Orders Link -->
            <Link
                v-if="canViewItem('travel-orders')"
                :href="route('travel-orders.index')"
                :class="[
                    route().current('travel-orders.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-bus text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Travel Orders</span>
            </Link>

            <!-- Pass Slips Link -->
            <Link
                v-if="canViewItem('pass-slips')"
                :href="route('pass-slips.index')"
                :class="[
                    route().current('pass-slips.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-file-contract text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Pass Slips</span>
            </Link>

            <!-- Time Slips Link -->
            <Link
                v-if="canViewItem('time-slips')"
                :href="route('time-slips.index')"
                :class="[
                    route().current('time-slips.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-hourglass-half text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Time Slips</span>
            </Link>

            <!-- Tardiness/Undertime Link -->
            <Link
                v-if="canViewItem('tardiness')"
                :href="route('tardiness.index')"
                :class="[
                    route().current('tardiness.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-clock text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Tardiness/Undertime</span>
            </Link>

            <!-- Certificates of Appearance Link -->
            <Link
                v-if="canViewItem('certificates-of-appearance')"
                :href="route('certificates-of-appearance.index')"
                :class="[
                    route().current('certificates-of-appearance.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-location-dot text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Certificates of Appearance</span>
            </Link>

            <!-- Auxiliary Section Divider -->
            <div
                v-show="(isSidebarOpen || isSidebarHovered) && (canViewItem('users') || canViewItem('employees') || canViewItem('offices') || canViewItem('municipalities'))"
                class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 px-3 pt-4 pb-2 mt-2 border-b-2 border-emerald-200 dark:border-emerald-900 transition-opacity"
            >
                Auxiliary
            </div>

            <!-- Users Link -->
            <Link
                v-if="canViewItem('users')"
                :href="route('users.index')"
                :class="[
                    route().current('users.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-users text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Users</span>
            </Link>

            <!-- Employees Link -->
            <Link
                v-if="canViewItem('employees')"
                :href="route('employees.index')"
                :class="[
                    route().current('employees.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-briefcase text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Employees</span>
            </Link>

            <!-- Offices Link -->
            <Link
                v-if="canViewItem('offices')"
                :href="route('offices.index')"
                :class="[
                    route().current('offices.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-building text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Offices</span>
            </Link>

            <!-- Municipalities Link -->
            <Link
                v-if="canViewItem('municipalities')"
                :href="route('municipalities.index')"
                :class="[
                    route().current('municipalities.*')
                        ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-md hover:from-emerald-600 hover:to-emerald-700'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm',
                    'flex-shrink-0 flex items-center gap-3 px-3 py-2.5 transition-all rounded-lg overflow-hidden',
                ]"
            >
                <i class="fas fa-city text-xl flex-shrink-0 dark:text-gray-100" aria-hidden="true"></i>
                <span class="text-sm font-medium whitespace-nowrap" v-show="isSidebarOpen || isSidebarHovered">Municipalities</span>
            </Link>   
        </nav>

        <!-- Footer Section -->
        <div class="px-3 py-4 mt-auto border-t border-gray-200 dark:border-gray-700 bg-gradient-to-b from-transparent to-gray-50 dark:to-gray-900">
            <div class="flex items-center gap-3">
                <!-- Avatar -->
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-600 flex items-center justify-center shadow-lg hover:shadow-xl transition-shadow duration-200">
                    <span class="text-sm font-bold text-white">{{ userInitial }}</span>
                </div>

                <!-- User Info -->
                <div v-show="isSidebarOpen || isSidebarHovered" class="flex-1 min-w-0 transition-opacity">
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight">
                            {{ auth.name }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate leading-tight">
                            {{ auth.username }}
                        </p>
                        <span v-if="usertype" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 shadow-sm w-fit whitespace-nowrap">
                            {{ usertype }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Toggle Button (Mobile) -->
        <div v-show="!isSidebarOpen" class="px-3 flex-shrink-0 lg:hidden">
            <button
                type="button"
                @click="toggleSidebar"
                class="w-full p-2 text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                <i class="fas fa-bars text-lg"></i>
            </button>
        </div>
    </aside>
</template>
