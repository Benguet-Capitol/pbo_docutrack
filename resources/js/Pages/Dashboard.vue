<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
</script>

<template>
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Work in Progress Banner -->
                <div class="rounded-lg border-2 border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 dark:border-amber-800 p-8 shadow-sm">
                    <!-- Header with Icon -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/40">
                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <!-- Title -->
                            <h3 class="text-lg font-bold text-amber-900 dark:text-amber-100">
                                Work in Progress
                            </h3>
                            <!-- Description -->
                            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">
                                The dashboard is currently under development. We're working hard to bring you an enhanced experience with real-time analytics, key metrics, and comprehensive reporting tools.
                            </p>
                            <!-- Feature List -->
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="flex items-start gap-2">
                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-amber-800 dark:text-amber-200">Real-time analytics dashboard</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-amber-800 dark:text-amber-200">Key performance metrics</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-amber-800 dark:text-amber-200">Advanced reporting tools</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs text-amber-800 dark:text-amber-200">Custom dashboards</span>
                                </div>
                            </div>
                            <!-- CTA -->
                            <div class="mt-4">
                                <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">
                                    In the meantime, you can manage your data using the navigation menu on the left.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Section (Optional Placeholder) -->
                <div class="mt-12">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                        Quick Access
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Placeholder Stats -->
                        <a href="/offices" class="group relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/60 transition-colors">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Offices</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage offices</p>
                                </div>
                            </div>
                        </a>

                        <a href="/employees" class="group relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/40 group-hover:bg-green-200 dark:group-hover:bg-green-900/60 transition-colors">
                                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.308 4 4 0 010-8.308M15 21H3a6 6 0 016-6h6a6 6 0 016 6h-12z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Employees</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage employees</p>
                                </div>
                            </div>
                        </a>

                        <a href="/users" class="group relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/60 transition-colors">
                                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Users</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage users</p>
                                </div>
                            </div>
                        </a>

                        <a href="#" class="group relative overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md transition-shadow opacity-50 cursor-not-allowed">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-900/40">
                                    <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-400 dark:text-gray-500">Reports</h4>
                                    <p class="text-sm text-gray-400 dark:text-gray-600">Coming soon</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
