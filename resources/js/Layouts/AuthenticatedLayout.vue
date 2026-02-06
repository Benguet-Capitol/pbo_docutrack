<script setup lang="ts">
import { ref, onMounted } from 'vue';
import SideNavigation from '@/Components/SideNavigation.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingSideNavigation = ref(true);
const isSidebarOpen = ref(false);
const isDarkMode = ref(false);

onMounted(() => {
    // Check if dark mode was previously saved
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'true') {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('darkMode', 'false');
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-gradient-to-br from-gray-100 via-emerald-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Side Navigation Component -->
        <SideNavigation :show="showingSideNavigation" v-model:isOpen="isSidebarOpen" />

        <!-- Main Content Area with Left Margin for Sidebar -->
        <div :class="['flex-1 flex flex-col', isSidebarOpen ? 'md:ml-64' : 'md:ml-16']">
            <!-- Top Navigation (User Profile & Logout) -->
            <nav class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Hamburger Menu for Small Screens -->
                    <button
                        @click="showingSideNavigation = !showingSideNavigation"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 sm:hidden"
                    >
                        <i class="fas fa-bars h-6 w-6"></i>
                    </button>

                    <div></div>

                    <!-- Dark Mode Toggle & Settings Dropdown -->
                    <div class="flex items-center gap-4">
                        <!-- Dark Mode Toggle Button -->
                        <button
                            @click="toggleDarkMode"
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition duration-150"
                            :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                        >
                            <!-- Sun Icon (Light Mode) -->
                            <i v-if="isDarkMode" class="fas fa-sun text-lg"></i>
                            <!-- Moon Icon (Dark Mode) -->
                            <i v-else class="fas fa-moon text-lg"></i>
                        </button>

                        <!-- Settings Dropdown -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none"
                                        >
                                            {{ $page.props.auth.user.name }}

                                            <i class="fas fa-chevron-down ms-2 h-4 w-4"></i>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        :href="route('profile.edit')"
                                    >
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-gradient-to-r from-gray-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700 border-b border-emerald-200 dark:border-emerald-900"
                v-if="$slots.header"
            >
                <div class="mx-auto px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 bg-gradient-to-br from-gray-100 via-emerald-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
                <slot />
            </main>
        </div>
    </div>
</template>
