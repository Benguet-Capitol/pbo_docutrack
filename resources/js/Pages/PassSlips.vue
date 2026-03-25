<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Pass Slips
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Pass Slip Button -->
                        <button v-if="hasPermission('pass-slips.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Pass Slip
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('pass-slips.create') && 'sm:ml-auto']">
                            <i class="fas fa-search text-gray-400"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search pass slips..."
                                class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                            />
                            <select
                                v-model.number="itemsPerPage"
                                class="border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                            >
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="999999">All</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="px-6 py-12 text-center">
                    <div class="inline-block">
                        <i class="fas fa-spinner fa-spin text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading pass slips...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Pass Slips</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="passSlips.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No pass slips found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new pass slip</p>
                </div>

                <!-- No Pass Slips for Search -->
                <div v-else-if="filteredPassSlips.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No pass slips found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Try adjusting your search or create a new pass slip</p>
                </div>

                <!-- Data Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-20">
                            <col class="w-16">
                            <col class="w-24">
                            <col class="w-40">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-40">
                            <col class="w-20">
                        </colgroup>
                        <!-- Table Header -->
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('control_no')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Control No
                                        <span v-if="sortBy === 'control_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('date')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Date
                                        <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('requested_time')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Leave Time
                                        <span v-if="sortBy === 'requested_time'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('purpose')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Purpose
                                        <span v-if="sortBy === 'purpose'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('location')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Location
                                        <span v-if="sortBy === 'location'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Return Time</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Employees</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody>
                            <tr v-for="slip in paginatedPassSlips" :key="slip.id" class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-xs text-gray-900 dark:text-gray-100 font-semibold">
                                    {{ slip.control_no }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatDate(slip.date) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatTime(slip.requested_time) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ slip.purpose }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ slip.location }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatTime(slip.expected_return_time) }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="flex gap-1 flex-wrap">
                                        <span v-for="emp in slip.employees" :key="emp.id" class="inline-block px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                            {{ emp.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button: Visible if user has permission -->
                                        <button 
                                            v-if="hasPermission('pass-slips.update')" 
                                            @click.stop="handleEditPassSlip(slip)" 
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button: Visible if user has permission -->
                                        <button 
                                            v-if="hasPermission('pass-slips.delete')" 
                                            @click.stop="openDeleteModal(slip)" 
                                            class="relative p-2 text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="!loading && !error && passSlips.length > 0" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredPassSlips.length) }} of {{ filteredPassSlips.length }} results
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            @click="changePage(1)"
                            :disabled="currentPage === 1"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Prev
                        </button>
                        <div class="flex gap-0.5">
                            <button
                                v-for="page in paginationPages"
                                :key="page"
                                @click="page !== '...' && changePage(page as number)"
                                :disabled="page === '...' || currentPage === page"
                                :class="[
                                    'px-2 py-1 text-xs rounded transition-colors',
                                    page === '...'
                                        ? 'text-gray-400 dark:text-gray-500 cursor-default'
                                        : currentPage === page
                                        ? 'bg-emerald-600 text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                        <button
                            @click="changePage(totalPages)"
                            :disabled="currentPage === totalPages"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Pass Slip Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-contract text-emerald-600 dark:text-emerald-400"></i>
                            Create Pass Slip
                        </h3>
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Control No Field -->
                            <div class="space-y-2">
                                <label for="create_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No. <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="create_control_no" type="text" placeholder="Auto-generated" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300 focus:border-gray-300" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generated</p>
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="create_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.date" id="create_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Requesting Employees Field -->
                            <div class="space-y-2">
                                <label for="create_employees" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requesting Employee(s) <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-32 overflow-y-auto">
                                        <div v-if="sortedEmployees.length === 0" class="text-xs text-gray-500 dark:text-gray-400 py-2">
                                            No employees available
                                        </div>
                                        <label v-for="emp in sortedEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                            <input type="checkbox" :value="emp.id" v-model.number="formData.employee_ids" class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                        </label>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Requested Time Field -->
                            <div class="space-y-2">
                                <label for="create_requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested to Leave At <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_time" id="create_requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label for="create_purpose" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.purpose" id="create_purpose" placeholder="Enter purpose" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.purpose ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"></textarea>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Location Field -->
                            <div class="space-y-2">
                                <label for="create_location" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.location" id="create_location" type="text" placeholder="Enter location" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.location ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.location" class="text-red-500 text-xs">{{ formErrors.location }}</span>
                            </div>

                            <!-- Expected Return Time Field -->
                            <div class="space-y-2">
                                <label for="create_return_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Expected Time of Return <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="time" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Specific Time</span>
                                            </label>
                                            <div v-show="formData.returnType === 'time'" class="relative flex items-center">
                                                <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                                <input v-model="formData.expected_return_time" id="create_expected_return_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.expected_return_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" @input="validateReturnTime" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="asap" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">ASAP</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="nwd" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NWD</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="time_slip" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Time Slip</span>
                                            </label>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="nom" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NOM</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" v-model="formData.returnType" value="memo" class="accent-emerald-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Memo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.expected_return_time" class="text-red-500 text-xs">{{ formErrors.expected_return_time }}</span>
                            </div>

                            <!-- Vehicle to be Used Field -->
                            <div class="space-y-2">
                                <label for="create_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle to be Used <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-van-shuttle absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.vehicle" id="create_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option value="RP Vehicle">RP Vehicle</option>
                                        <option value="PUJ">PUJ</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Recommending Approval Employee Field -->
                            <div class="space-y-2">
                                <label for="create_recommending_approval" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Recommending Approval (Supervisor)</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model.number="formData.recommending_approval_employee_id" id="create_recommending_approval" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.recommending_approval_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.recommending_approval_employee_id" class="text-red-500 text-xs">{{ formErrors.recommending_approval_employee_id }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label for="create_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.remarks" id="create_remarks" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button type="submit" @click="submitCreateForm" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ creating ? 'Loading...' : 'Preview & Continue' }}
                        </button>
                        <button @click="closeCreateModal" type="button" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Pass Slip Preview Modal -->
        <Teleport to="body" v-if="showPreviewModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-7xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                            Pass Slip Preview
                        </h3>
                        <button @click="closePreviewModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Preview Content - Two Column Layout -->
                    <div class="grid grid-cols-2 gap-4 p-4" style="background-color: white;">
                        <!-- Copy 1 -->
                        <div>
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-4 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-semibold text-gray-700">Republic of the Philippines</p>
                                <p class="text-base font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-xs text-gray-700 mb-1">Poblacion, La Trinidad 2601</p>
                                <p class="text-xs font-bold text-gray-900 mb-4">PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-base font-bold text-gray-900">PASS SLIP</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                        <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">Provincial Budget Office</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-6 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer() }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content -->
                        <div class="mb-8 text-xs leading-relaxed text-gray-900">
                            <p class="mb-4">
                                Permission is respectfully requested to leave at <span class="border-b border-gray-400 w-24 inline-block text-center">{{ formatTimeDisplay(formData.requested_time) }}</span> 
                                on <span class="border-b border-gray-400 w-36 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span> 
                                to <span class="border-b border-gray-400 w-[560px] inline-block text-center">{{ formData.purpose }}</span> 
                                at the <span class="border-b border-gray-400 w-96 inline-block text-center">{{ formData.location }}</span>.
                            </p>
                            
                            <div class="ml-4 space-y-1">
                                <p>
                                    <span>Expected Time of Return: </span> 
                                    <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">
                                        {{ formData.returnType === 'asap' ? 'As soon as possible' : 
                                           formData.returnType === 'nwd' ? 'Next Working Day' : 
                                           formData.returnType === 'time_slip' ? 'Time Slip' : 
                                           formData.returnType === 'nom' ? 'NOM' : 
                                           formData.returnType === 'memo' ? 'Memo' : 
                                           formatTimeDisplay(formData.expected_return_time) }}
                                    </span>
                                </p>
                                <p>
                                    <span>Vehicle to be used: </span> 
                                    <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">{{ formData.vehicle }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-96 uppercase">
                                {{ getEmployeeNames() }}
                            </p>
                            <p class="text-xs text-gray-700 text-center w-96">Requesting Employee</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getRecommendingApprovalEmployee() && !isProvincialBudgetOfficerRequesting()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-96">
                                    <p class="font-bold text-xs text-center text-gray-900 w-96 uppercase">{{ getRecommendingApprovalEmployee().name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 w-96">{{ getRecommendingApprovalEmployee().designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end pr-8">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialGovernor().name }}</p>
                                <p v-else class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialBudgetOfficer() }}</p>
                                <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700">Department Head</p>
                            </div>
                        </div>

                        <!-- Certificate of Appearance -->
                        <div class="mt-4 pt-4" style="border-top: 3px double #050505;">
                            <p class="font-bold text-xs text-center text-gray-900 mb-2">CERTIFICATE OF APPEARANCE</p>
                            <p class="text-xs text-gray-700 leading-relaxed">
                                This is to CERTIFY that the above mentioned person appeared in this office on {{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}.
                            </p>
                            
                            <!-- Signature and Printed Name Section -->
                            <div class="mt-4 w-96 text-center">
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-6 mb-1"></p>
                                    <p class="text-xs text-center text-gray-700">Signature over Printed Name</p>
                            </div>
                        </div>
                        </div>

                        <!-- Copy 2 (Duplicate) -->
                        <div>
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-4 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-semibold text-gray-700">Republic of the Philippines</p>
                                <p class="text-base font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-xs text-gray-700 mb-1">Poblacion, La Trinidad 2601</p>
                                <p class="text-xs font-bold text-gray-900 mb-4">PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-base font-bold text-gray-900">PASS SLIP</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                         <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">Provincial Budget Office</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-6 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer() }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content -->
                        <div class="mb-8 text-xs leading-relaxed text-gray-900">
                            <p class="mb-4">
                                Permission is respectfully requested to leave at <span class="border-b border-gray-400 w-24 inline-block text-center">{{ formatTimeDisplay(formData.requested_time) }}</span> 
                                on <span class="border-b border-gray-400 w-36 inline-block text-center">{{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span> 
                                to <span class="border-b border-gray-400 w-[560px] inline-block text-center">{{ formData.purpose }}</span> 
                                at the <span class="border-b border-gray-400 w-96 inline-block text-center">{{ formData.location }}</span>.
                            </p>
                            
                            <div class="ml-4 space-y-1">
                                <p>
                                    <span>Expected Time of Return: </span> 
                                    <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">
                                        {{ formData.returnType === 'asap' ? 'As soon as possible' : 
                                           formData.returnType === 'nwd' ? 'Next Working Day' : 
                                           formData.returnType === 'time_slip' ? 'Time Slip' : 
                                           formData.returnType === 'nom' ? 'NOM' : 
                                           formData.returnType === 'memo' ? 'Memo' : 
                                           formatTimeDisplay(formData.expected_return_time) }}
                                    </span>
                                </p>
                                <p>
                                    <span>Vehicle to be used: </span> 
                                    <span class="font-bold border-b border-gray-400 w-48 inline-block text-center">{{ formData.vehicle }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-96 uppercase">
                                {{ getEmployeeNames() }}
                            </p>
                            <p class="text-xs text-gray-700 text-center w-96">Requesting Employee</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getRecommendingApprovalEmployee() && !isProvincialBudgetOfficerRequesting()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-96">
                                    <p class="font-bold text-xs text-center text-gray-900 w-96 uppercase">{{ getRecommendingApprovalEmployee().name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 w-96">{{ getRecommendingApprovalEmployee().designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end pr-8">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialGovernor().name }}</p>
                                <p v-else class="text-xs text-center text-gray-900 font-bold uppercase">{{ getProvincialBudgetOfficer() }}</p>
                                <p v-if="isProvincialBudgetOfficerRequesting() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700">Department Head</p>
                            </div>
                        </div>

                        <!-- Certificate of Appearance -->
                        <div class="mt-4 pt-4" style="border-top: 3px double #050505;">
                            <p class="font-bold text-xs text-center text-gray-900 mb-2">CERTIFICATE OF APPEARANCE</p>
                            <p class="text-xs text-gray-700 leading-relaxed">
                                This is to CERTIFY that the above mentioned person appeared in this office on {{ new Date(formData.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}.
                            </p>
                            
                            <!-- Signature and Printed Name Section -->
                            <div class="mt-4 w-96 text-center">
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-6 mb-1"></p>
                                    <p class="text-xs text-center text-gray-700">Signature over Printed Name</p>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                        <button @click="printPassSlip" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <button @click="confirmPreviewAndSubmit" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ creating ? 'Saving...' : 'Confirm & Save' }}
                        </button>
                        <button @click="closePreviewModal" type="button" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left"></i>
                            Back to Form
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Pass Slip Modal -->
        <Teleport to="body" v-if="showEditModal && passSlipToEdit">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Pass Slip
                        </h3>
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Control No Field -->
                            <div class="space-y-2">
                                <label for="edit_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No.</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="edit_control_no" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-600 dark:text-white cursor-not-allowed opacity-75" />
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="edit_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.date" id="edit_date" type="date" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors opacity-75 cursor-not-allowed bg-gray-100 dark:bg-gray-600 border-gray-300" />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Requesting Employees Field -->
                            <div class="space-y-2">
                                <label for="edit_employees" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requesting Employee(s) <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-32 overflow-y-auto">
                                        <div v-if="sortedEmployees.length === 0" class="text-xs text-gray-500 dark:text-gray-400 py-2">
                                            No employees available
                                        </div>
                                        <label v-for="emp in sortedEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                            <input type="checkbox" :value="emp.id" v-model.number="formData.employee_ids" class="rounded border-gray-300 dark:border-gray-600 accent-blue-600" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                        </label>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Requested Time Field -->
                            <div class="space-y-2">
                                <label for="edit_requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested to Leave At <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_time" id="edit_requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label for="edit_purpose" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.purpose" id="edit_purpose" placeholder="Enter purpose" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.purpose ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"></textarea>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Location Field -->
                            <div class="space-y-2">
                                <label for="edit_location" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.location" id="edit_location" type="text" placeholder="Enter location" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.location ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.location" class="text-red-500 text-xs">{{ formErrors.location }}</span>
                            </div>

                            <!-- Expected Return Time Field -->
                            <div class="space-y-2">
                                <label for="edit_return_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Expected Time of Return <span class="text-red-600">*</span></label>
                                <div class="space-y-2">
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="time" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Specific Time</span>
                                            </label>
                                            <div v-show="formData.returnType === 'time'" class="relative flex items-center">
                                                <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                                <input v-model="formData.expected_return_time" id="edit_expected_return_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.expected_return_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" @input="validateReturnTime" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="asap" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">ASAP</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="nwd" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NWD</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="time_slip" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Time Slip</span>
                                            </label>
                                        </div>
                                        <div class="flex-1">
                                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                <input type="radio" v-model="formData.returnType" value="nom" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">NOM</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" v-model="formData.returnType" value="memo" class="accent-blue-600" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Memo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.expected_return_time" class="text-red-500 text-xs">{{ formErrors.expected_return_time }}</span>
                            </div>

                            <!-- Vehicle to be Used Field -->
                            <div class="space-y-2">
                                <label for="edit_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle to be Used <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-van-shuttle absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model="formData.vehicle" id="edit_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']">
                                        <option value="RP Vehicle">RP Vehicle</option>
                                        <option value="PUJ">PUJ</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Recommending Approval Employee Field -->
                            <div class="space-y-2">
                                <label for="edit_recommending_approval" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Recommending Approval (Supervisor)</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select v-model.number="formData.recommending_approval_employee_id" id="edit_recommending_approval" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.recommending_approval_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']">
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.recommending_approval_employee_id" class="text-red-500 text-xs">{{ formErrors.recommending_approval_employee_id }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label for="edit_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.remarks" id="edit_remarks" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Submit error -->
                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="submitEditForm" :disabled="updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ updating ? 'Updating...' : 'Preview & Continue' }}
                        </button>
                        <button @click="closeEditModal" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Pass Slip Modal -->
        <Teleport to="body" v-if="showDeleteModal && passSlipToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Pass Slip
                        </h3>
                        <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    Are you sure you want to delete pass slip <span class="font-semibold">{{ passSlipToDelete.control_no }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. All associated data will be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="confirmDelete"
                            :disabled="deleting"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-trash-alt"></i>
                            {{ deleting ? 'Deleting...' : 'Delete' }}
                        </button>
                        <button
                            @click="closeDeleteModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import Toast from '@/Components/Toast.vue';
import { ref, onMounted, computed, watch } from 'vue';

interface Employee {
    id: number;
    name: string;
    office_id: number;
    designation?: string;
    office?: {
        id: number;
        name: string;
    };
}

interface PassSlip {
    id: number;
    control_no: string;
    date: string;
    requested_time: string;
    purpose: string;
    location: string;
    expected_return_time: string;
    remarks: string | null;
    vehicle?: string;
    employees: Employee[];
    recommending_approval_employee_id: number | null;
    created_at: string;
    updated_at: string;
}

const passSlips = ref<PassSlip[]>([]);
const employees = ref<Employee[]>([]);
const returnTimeOptions = ref(['12:00 NN', 'ASAP', 'NWD']);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref<'id' | 'control_no' | 'date' | 'requested_time' | 'purpose' | 'location'>('id');
const sortOrder = ref<'asc' | 'desc'>('desc');
const loading = ref(true);
const error = ref<string | null>(null);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showPreviewModal = ref(false);

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);

const passSlipToEdit = ref<PassSlip | null>(null);
const passSlipToDelete = ref<PassSlip | null>(null);

const formData = ref({
    control_no: '',
    date: '',
    requested_time: '',
    purpose: '',
    location: '',
    expected_return_time: '',
    remarks: '',
    employee_ids: [] as number[],
    recommending_approval_employee_id: null as number | null,
    vehicle: 'RP Vehicle' as 'RP Vehicle' | 'PUJ',
    returnType: 'time' as 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo',
});

const formErrors = ref<Record<string, string>>({});

const toastRef = ref();

const hasPermission = (permission: string): boolean => {
    return true;
};

const sortedEmployees = computed(() => {
    return employees.value.slice().sort((a, b) => {
        const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
        const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
        return lastNameA.localeCompare(lastNameB);
    });
});

const filteredPassSlips = computed(() => {
    let filtered = passSlips.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(slip =>
            slip.control_no.toLowerCase().includes(query) ||
            slip.purpose.toLowerCase().includes(query) ||
            slip.location.toLowerCase().includes(query) ||
            slip.employees.some(emp => emp.name.toLowerCase().includes(query))
        );
    }

    filtered.sort((a, b) => {
        let aVal: any;
        let bVal: any;

        if (sortBy.value === 'id') {
            aVal = a.id;
            bVal = b.id;
        } else {
            aVal = a[sortBy.value as keyof PassSlip];
            bVal = b[sortBy.value as keyof PassSlip];
            aVal = aVal?.toString().toLowerCase() || '';
            bVal = bVal?.toString().toLowerCase() || '';
        }

        let comparison = 0;
        if (aVal < bVal) comparison = -1;
        if (aVal > bVal) comparison = 1;
        return sortOrder.value === 'asc' ? comparison : -comparison;
    });

    return filtered;
});

const totalPages = computed(() => {
    return Math.ceil(filteredPassSlips.value.length / itemsPerPage.value);
});

/**
 * Smart pagination: Shows limited page buttons with ellipsis
 * Format: [1, 2, ..., currentPage-1, currentPage, currentPage+1, ..., last-1, last]
 */
const paginationPages = computed(() => {
    const pages: (number | string)[] = [];
    const total = totalPages.value;
    const current = currentPage.value;
    const maxButtons = 5; // Maximum page buttons to show without ellipsis
    const sidePages = 1; // Pages to show on each side of current page
    const edgePages = 1; // Pages to show at start and end
    
    if (total <= maxButtons + 2) {
        // If total pages fit, show all
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page(s)
        for (let i = 1; i <= Math.min(edgePages + 1, total); i++) {
            pages.push(i);
        }
        
        // Calculate range around current page
        const rangeStart = Math.max(edgePages + 2, current - sidePages);
        const rangeEnd = Math.min(total - edgePages - 1, current + sidePages);
        
        // Add ellipsis if needed
        if (rangeStart > edgePages + 2) {
            if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        
        // Add pages around current
        for (let i = rangeStart; i <= rangeEnd; i++) {
            if (i > edgePages + 1 && i < total - edgePages) {
                pages.push(i);
            }
        }
        
        // Add ellipsis if needed before last pages
        if (rangeEnd < total - edgePages - 1) {
            if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        
        // Always show last page(s)
        for (let i = Math.max(edgePages + 2, total - edgePages); i <= total; i++) {
            if (!pages.includes(i)) {
                pages.push(i);
            }
        }
    }
    
    return pages;
});

const paginatedPassSlips = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredPassSlips.value.slice(start, end);
});

const fetchPassSlips = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch('/api/pass-slips');
        if (!response.ok) throw new Error('Failed to fetch pass slips');
        passSlips.value = await response.json();
    } catch (err: any) {
        error.value = err.message;
        showToast('Failed to load pass slips', 'error');
    } finally {
        loading.value = false;
    }
};

const fetchEmployees = async () => {
    try {
        const response = await fetch('/api/employees');
        if (!response.ok) throw new Error('Failed to fetch employees');
        employees.value = await response.json();
    } catch (err: any) {
        console.error('Failed to load employees:', err);
    }
};

const generateControlNo = (dateString?: string): string => {
    // Use provided date or current date
    const dateToUse = dateString ? new Date(dateString) : new Date();
    const year = dateToUse.getFullYear();
    const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
    const prefix = 'PS';
    
    const sameYearCount = passSlips.value.filter(slip =>
        slip.control_no.startsWith(`${prefix}-${year}`)
    ).length;
    
    const series = String(sameYearCount + 1).padStart(4, '0');
    return `${prefix}-${year}-${month}-${series}`;
};

const validateReturnTime = () => {
    if (formData.value.returnType === 'time') {
        if (!formData.value.expected_return_time.trim()) {
            formErrors.value['expected_return_time'] = 'Please enter a return time';
            return;
        }
        
        // Check if return time is after requested time
        if (formData.value.expected_return_time <= formData.value.requested_time) {
            formErrors.value['expected_return_time'] = 'Return time must be after the requested leave time';
        } else {
            delete formErrors.value['expected_return_time'];
        }
    } else {
        delete formErrors.value['expected_return_time'];
    }
};

// Format time to HH:MM format for API submission
const formatTimeForAPI = (timeString: string): string => {
    if (!timeString) return '';
    
    // If it's already in HH:MM format, return as is
    if (/^\d{2}:\d{2}$/.test(timeString)) {
        return timeString;
    }
    
    // If it's in H:MM format, pad the hour
    if (/^\d:\d{2}$/.test(timeString)) {
        return '0' + timeString;
    }
    
    // Handle other cases - try to extract HH:MM
    const match = timeString.match(/(\d{1,2}):(\d{2})/);
    if (match) {
        const hour = match[1].padStart(2, '0');
        const minute = match[2];
        return `${hour}:${minute}`;
    }
    
    return timeString;
};

/**
 * Format time for display (12-hour format)
 */
const formatTimeDisplay = (timeString: string): string => {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

/**
 * Get comma-separated employee names
 */
const getEmployeeNames = (): string => {
    const selectedEmployees = sortedEmployees.value.filter(emp => 
        formData.value.employee_ids.includes(emp.id)
    );
    return selectedEmployees.map(emp => emp.name).join(', ') || '[Employee Name]';
};

const getRecommendingApprovalEmployee = () => {
    if (!formData.value.recommending_approval_employee_id) {
        return null;
    }
    return employees.value.find(emp => emp.id === formData.value.recommending_approval_employee_id) || null;
};

const getProvincialGovernor = (): Employee | null => {
    return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
};

const getProvincialBudgetOfficer = (): string => {
    const pbo = employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer');
    return pbo ? pbo.name : '-';
};

const isProvincialBudgetOfficerRequesting = (): boolean => {
    return formData.value.employee_ids.some(empId => {
        const emp = employees.value.find(e => e.id === empId);
        return emp?.designation === 'Provincial Budget Officer';
    });
};

const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.date.trim()) {
        formErrors.value['date'] = 'Date is required';
    }
    
    if (!formData.value.requested_time.trim()) {
        formErrors.value['requested_time'] = 'Requested time is required';
    }

    if (!formData.value.purpose.trim()) {
        formErrors.value['purpose'] = 'Purpose is required';
    }

    if (!formData.value.expected_return_time.trim() && formData.value.returnType === 'time') {
        formErrors.value['expected_return_time'] = 'Expected return time is required for specific time';
    }

    if (formData.value.employee_ids.length === 0) {
        formErrors.value['employee_ids'] = 'At least one employee is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const openCreateModal = async () => {
    const today = new Date().toISOString().split('T')[0];
    formData.value = {
        control_no: generateControlNo(today),
        date: today,
        requested_time: '08:00',
        purpose: '',
        location: '',
        expected_return_time: '12:00',
        remarks: '',
        employee_ids: [],
        returnType: 'time',
        recommending_approval_employee_id: null,
        vehicle: 'RP Vehicle',
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const closePreviewModal = () => {
    showPreviewModal.value = false;
};

// Watch for changes to date and regenerate control_no
watch(() => formData.value.date, (newDate) => {
    if (showCreateModal.value && newDate) {
        // Regenerate control number based on the new date
        formData.value.control_no = generateControlNo(newDate);
    }
});

// Watch for changes to employee_ids and auto-set recommending approval when PBO is selected
watch(() => formData.value.employee_ids, (newEmployeeIds) => {
    if (isProvincialBudgetOfficerRequesting()) {
        const pgov = getProvincialGovernor();
        if (pgov) {
            formData.value.recommending_approval_employee_id = pgov.id;
        }
    }
}, { deep: true });

const submitCreateForm = async () => {
    if (!validateForm()) return;
    validateReturnTime();
    if (Object.keys(formErrors.value).length > 0) return;
    
    // Open preview modal instead of directly submitting
    showPreviewModal.value = true;
};

const confirmPreviewAndSubmit = async () => {
    try {
        // Check if we're updating or creating
        const isUpdate = passSlipToEdit.value !== null;
        const isLoading = isUpdate ? 'updating' : 'creating';
        eval(`${isLoading}.value = true`);
        
        // Build submitData with only fields the API expects
        let expectedReturnTime = formData.value.expected_return_time;
        if (formData.value.returnType === 'asap') {
            expectedReturnTime = 'ASAP';
        } else if (formData.value.returnType === 'nwd') {
            expectedReturnTime = 'NWD';
        } else if (formData.value.returnType === 'time_slip') {
            expectedReturnTime = 'Time Slip';
        } else if (formData.value.returnType === 'nom') {
            expectedReturnTime = 'NOM';
        } else if (formData.value.returnType === 'memo') {
            expectedReturnTime = 'Memo';
        } else {
            // Format time to HH:MM
            expectedReturnTime = formatTimeForAPI(expectedReturnTime);
        }
        
        const submitData = {
            control_no: formData.value.control_no,
            date: formData.value.date,
            requested_time: formatTimeForAPI(formData.value.requested_time),
            purpose: formData.value.purpose,
            location: formData.value.location,
            expected_return_time: expectedReturnTime,
            remarks: formData.value.remarks,
            employee_ids: formData.value.employee_ids,
            recommending_approval_employee_id: formData.value.recommending_approval_employee_id,
            vehicle: formData.value.vehicle,
        };
        
        // Build the URL and method based on operation
        const url = isUpdate ? `/api/pass-slips/${passSlipToEdit.value!.id}` : '/api/pass-slips';
        const method = isUpdate ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error(`${isUpdate ? 'Update' : 'Create'} error response:`, errorData);
            
            // Handle validation errors (422)
            if (response.status === 422 && errorData.errors) {
                Object.keys(errorData.errors).forEach(field => {
                    formErrors.value[field] = errorData.errors[field][0];
                });
                const errorMessages = Object.values(errorData.errors)
                    .flat()
                    .join(', ');
                throw new Error(errorMessages);
            }
            
            throw new Error(errorData.error || errorData.message || `Failed to ${isUpdate ? 'update' : 'create'} pass slip`);
        }

        const result = await response.json();
        await fetchPassSlips();
        showPreviewModal.value = false;
        
        if (isUpdate) {
            closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                `Pass slip <strong>${result.data.control_no}</strong> has been updated successfully!`,
                3000
            );
        } else {
            closeCreateModal();
            toastRef.value?.add(
                'success',
                'Success',
                `Pass slip <strong>${result.data.control_no}</strong> has been created successfully!`,
                3000
            );
        }
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        creating.value = false;
        updating.value = false;
    }
};

const handleEditPassSlip = (slip: PassSlip) => {
    passSlipToEdit.value = slip;
    
    // Determine return type from expected_return_time
    let returnType: 'time' | 'asap' | 'nwd' | 'time_slip' | 'nom' | 'memo' = 'time';
    let returnTimeValue = slip.expected_return_time;
    
    if (slip.expected_return_time === 'ASAP') {
        returnType = 'asap';
        returnTimeValue = '';
    } else if (slip.expected_return_time === 'NWD') {
        returnType = 'nwd';
        returnTimeValue = '';
    } else if (slip.expected_return_time === 'Time Slip') {
        returnType = 'time_slip';
        returnTimeValue = '';
    } else if (slip.expected_return_time === 'NOM') {
        returnType = 'nom';
        returnTimeValue = '';
    } else if (slip.expected_return_time === 'Memo') {
        returnType = 'memo';
        returnTimeValue = '';
    }
    
    // Format date to YYYY-MM-DD for date input (avoid timezone issues)
    let formattedDate = slip.date;
    if (slip.date) {
        // Simply extract YYYY-MM-DD part from the date string to avoid timezone conversion
        formattedDate = slip.date.split('T')[0];
    }
    
    formData.value = {
        control_no: slip.control_no,
        date: formattedDate,
        requested_time: slip.requested_time,
        purpose: slip.purpose,
        location: slip.location,
        expected_return_time: returnTimeValue,
        remarks: slip.remarks || '',
        employee_ids: slip.employees.map(emp => emp.id),
        returnType: returnType,
        recommending_approval_employee_id: slip.recommending_approval_employee_id || null,
        vehicle: (slip.vehicle || 'RP Vehicle') as 'RP Vehicle' | 'PUJ',
    };
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    passSlipToEdit.value = null;
};

const submitEditForm = async () => {
    if (!passSlipToEdit.value) return;
    
    if (!validateForm()) return;
    validateReturnTime();
    if (Object.keys(formErrors.value).length > 0) return;
    
    // Open preview modal instead of directly submitting
    showPreviewModal.value = true;
};

const openDeleteModal = (slip: PassSlip) => {
    passSlipToDelete.value = slip;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    passSlipToDelete.value = null;
};

const confirmDelete = async () => {
    if (!passSlipToDelete.value) return;

    const deletingSlip = passSlipToDelete.value;

    try {
        deleting.value = true;
        const response = await fetch(`/api/pass-slips/${deletingSlip.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to delete pass slip');
        }

        await fetchPassSlips();
        closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Deleted',
            `Pass slip <strong>${deletingSlip.control_no}</strong> has been deleted successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const toggleSort = (field: 'control_no' | 'date' | 'requested_time' | 'purpose' | 'location') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: '2-digit' 
    });
};

const formatTime = (timeString: string): string => {
    if (!timeString) return '';
    
    try {
        // Parse HH:MM:SS or just HH:MM
        const parts = timeString.split(':');
        if (parts.length < 2) return timeString;
        
        let hours = parseInt(parts[0], 10);
        const minutes = parts[1];
        
        const am_pm = hours >= 12 ? 'PM' : 'AM';
        if (hours > 12) hours -= 12;
        if (hours === 0) hours = 12;
        
        const hoursStr = String(hours).padStart(2, '0');
        return `${hoursStr}:${minutes} ${am_pm}`;
    } catch {
        return timeString;
    }
};

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    if (toastRef.value) {
        toastRef.value.show(message, type);
    }
};

/**
 * Print the pass slip preview
 */
const printPassSlip = () => {
    window.print();
};

onMounted(() => {
    fetchPassSlips();
    fetchEmployees();
});
</script>

<!-- Scoped Styles: Modal animations and transitions -->
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

/* Print Styles: Hide modal chrome and scrollbars */
@media print {
    .sticky {
        position: static !important;
    }
    
    .sticky.top-0 {
        display: none !important;
    }
    
    .sticky.bottom-0 {
        display: none !important;
    }
    
    .overflow-y-auto {
        overflow: visible !important;
    }
    
    .max-h-\[90vh\] {
        max-height: none !important;
    }
    
    body, html {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
