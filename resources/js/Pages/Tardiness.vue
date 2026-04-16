<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Tardiness/Undertime
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Button -->
                        <button v-if="hasPermission('tardiness.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Tardiness/Undertime
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('tardiness.create') && 'sm:ml-auto']">
                            <i class="fas fa-search text-gray-400"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search records..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading records...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Records</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="tardiness.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No records found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new tardiness/undertime record</p>
                </div>

                <!-- No Records for Search -->
                <div v-else-if="filteredTardiness.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No Tardiness/Undertime found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Try adjusting your search or create a new record</p>
                </div>

                <!-- Data Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-24">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Control No</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Date Filed</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Type</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Employee</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Requested Date</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Requested Time</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Return Time</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Reason</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="record in paginatedTardiness" :key="record.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-2 text-xs text-gray-900 dark:text-gray-100 font-semibold">
                                    {{ record.control_no }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatDate(record.date_filed) }}
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        record.type === 'Tardiness' 
                                            ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'
                                            : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                                    ]">
                                        {{ record.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ record.employee?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatDate(record.requested_date) }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ formatTime(record.requested_time) }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ record.return_time ? formatTime(record.return_time) : 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400" :title="record.reason">
                                    {{ record.reason }}
                                </td>
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="hasPermission('tardiness.edit')" 
                                            @click.stop="openEditModal(record)" 
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <button 
                                            v-if="hasPermission('tardiness.delete')" 
                                            @click.stop="openDeleteModal(record)" 
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

                <!-- Pagination Controls -->
                <div v-if="!loading && tardiness.length > 0" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredTardiness.length) }} of {{ filteredTardiness.length }} results
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            @click="currentPage = 1"
                            :disabled="currentPage === 1"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button
                            @click="currentPage = currentPage - 1"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Prev
                        </button>
                        <div class="flex gap-0.5">
                            <button
                                v-for="page in paginationRange"
                                :key="page"
                                @click="page !== '...' && (currentPage = page as number)"
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
                            @click="currentPage = currentPage + 1"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                        <button
                            @click="currentPage = totalPages"
                            :disabled="currentPage === totalPages"
                            class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-clock text-emerald-600 dark:text-emerald-400"></i>
                            Create Tardiness/Undertime
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
                                <label for="create_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="create_control_no" type="text" placeholder="Auto-generated" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300 focus:border-gray-300" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generated</p>
                            </div>

                            <!-- Date Filed Field -->
                            <div class="space-y-2">
                                <label for="create_date_filed" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date Filed <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.date_filed" id="create_date_filed" type="date" :max="todayDate" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date_filed ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.date_filed" class="text-red-500 text-xs">{{ formErrors.date_filed }}</span>
                            </div>

                            <!-- Type Field -->
                            <div class="space-y-2">
                                <label for="create_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Type <span class="text-red-600">*</span></label>
                                <select
                                    v-model="formData.type"
                                    id="create_type"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                >
                                    <option value="Undertime">Undertime</option>
                                    <option value="Tardiness">Tardiness</option>
                                </select>
                                <span v-if="formErrors.type" class="text-red-500 text-xs">{{ formErrors.type }}</span>
                            </div>

                            <!-- Requested Date Field -->
                            <div class="space-y-2">
                                <label for="create_requested_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_date" id="create_requested_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.requested_date" class="text-red-500 text-xs">{{ formErrors.requested_date }}</span>
                            </div>

                            <!-- Employee Field -->
                            <div class="space-y-2">
                                <label for="create_employee" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee <span class="text-red-600">*</span></label>
                                <select
                                    v-model.number="formData.employee_id"
                                    id="create_employee"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                >
                                    <option value="0">Select an employee</option>
                                    <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                        {{ emp.name }}
                                    </option>
                                </select>
                                <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                            </div>

                            <!-- Requested Time Field -->
                            <div class="space-y-2">
                                <label for="create_requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Time <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_time" id="create_requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                            </div>

                            <!-- Reason Field -->
                            <div class="space-y-2">
                                <label for="create_reason" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.reason" id="create_reason" placeholder="Enter reason for tardiness/undertime" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.reason ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"></textarea>
                                </div>
                                <span v-if="formErrors.reason" class="text-red-500 text-xs">{{ formErrors.reason }}</span>
                            </div>

                            <!-- Return Time -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Return Time</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="radio" v-model="formData.returnType" value="time" class="rounded" />
                                        Specific Time
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="radio" v-model="formData.returnType" value="nwd" class="rounded" />
                                        NWD
                                    </label>
                                </div>
                                <input
                                    v-if="formData.returnType === 'time'"
                                    v-model="formData.return_time"
                                    type="time"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 mt-2 transition-colors"
                                />
                            </div>

                            <!-- Supervisor Field -->
                            <div class="space-y-2">
                                <label for="create_supervisor" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.supervisor_employee_id"
                                        @change="(e) => { formData.supervisor_employee_id = (e.target as HTMLSelectElement).value ? Number((e.target as HTMLSelectElement).value) : null }"
                                        id="create_supervisor"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                        :class="[formErrors.supervisor_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                    >
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                            {{ emp.name }}
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.supervisor_employee_id" class="text-red-500 text-xs">{{ formErrors.supervisor_employee_id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="submitCreateForm" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-eye"></i>
                            {{ creating ? 'Loading...' : 'Preview & Continue' }}
                        </button>
                        <button @click="closeCreateModal" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Modal -->
        <Teleport to="body" v-if="showEditModal && recordToEdit">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Tardiness/Undertime
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
                                <label for="edit_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="edit_control_no" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-600 dark:text-white cursor-not-allowed opacity-75" />
                                </div>
                            </div>

                            <!-- Date Filed Field -->
                            <div class="space-y-2">
                                <label for="edit_date_filed" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date Filed <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.date_filed" id="edit_date_filed" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date_filed ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.date_filed" class="text-red-500 text-xs">{{ formErrors.date_filed }}</span>
                            </div>

                            <!-- Type Field -->
                            <div class="space-y-2">
                                <label for="edit_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Type <span class="text-red-600">*</span></label>
                                <select
                                    v-model="formData.type"
                                    id="edit_type"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                >
                                    <option value="Undertime">Undertime</option>
                                    <option value="Tardiness">Tardiness</option>
                                </select>
                                <span v-if="formErrors.type" class="text-red-500 text-xs">{{ formErrors.type }}</span>
                            </div>

                            <!-- Requested Date Field -->
                            <div class="space-y-2">
                                <label for="edit_requested_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_date" id="edit_requested_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.requested_date" class="text-red-500 text-xs">{{ formErrors.requested_date }}</span>
                            </div>

                            <!-- Employee Field -->
                            <div class="space-y-2">
                                <label for="edit_employee" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee <span class="text-red-600">*</span></label>
                                <select
                                    v-model.number="formData.employee_id"
                                    id="edit_employee"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                >
                                    <option value="0">Select an employee</option>
                                    <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                        {{ emp.name }}
                                    </option>
                                </select>
                                <span v-if="formErrors.employee_id" class="text-red-500 text-xs">{{ formErrors.employee_id }}</span>
                            </div>

                            <!-- Requested Time Field -->
                            <div class="space-y-2">
                                <label for="edit_requested_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requested Time <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-clock absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.requested_time" id="edit_requested_time" type="time" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.requested_time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.requested_time" class="text-red-500 text-xs">{{ formErrors.requested_time }}</span>
                            </div>

                            <!-- Reason Field -->
                            <div class="space-y-2">
                                <label for="edit_reason" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reason <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea v-model="formData.reason" id="edit_reason" placeholder="Enter reason for tardiness/undertime" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.reason ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"></textarea>
                                </div>
                                <span v-if="formErrors.reason" class="text-red-500 text-xs">{{ formErrors.reason }}</span>
                            </div>

                            <!-- Return Time -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Return Time</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="radio" v-model="formData.returnType" value="time" class="rounded" />
                                        Specific Time
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="radio" v-model="formData.returnType" value="nwd" class="rounded" />
                                        NWD
                                    </label>
                                </div>
                                <input
                                    v-if="formData.returnType === 'time'"
                                    v-model="formData.return_time"
                                    type="time"
                                    class="block w-full px-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 mt-2 transition-colors"
                                />
                            </div>

                            <!-- Supervisor Field -->
                            <div class="space-y-2">
                                <label for="edit_supervisor" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user-check absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.supervisor_employee_id"
                                        @change="(e) => { formData.supervisor_employee_id = (e.target as HTMLSelectElement).value ? Number((e.target as HTMLSelectElement).value) : null }"
                                        id="edit_supervisor"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                        :class="[formErrors.supervisor_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                    >
                                        <option :value="null">Select a supervisor</option>
                                        <option v-for="emp in sortedEmployees" :key="emp.id" :value="emp.id">
                                            {{ emp.name }}
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.supervisor_employee_id" class="text-red-500 text-xs">{{ formErrors.supervisor_employee_id }}</span>
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

        <!-- Delete Modal -->
        <Teleport to="body" v-if="showDeleteModal && recordToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Tardiness/Undertime
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
                                    Are you sure you want to delete tardiness/undertime record <span class="font-semibold">{{ recordToDelete.control_no }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. All associated data will be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 px-6 py-4 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
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

        <!-- Request for Undertime Preview Modal -->
        <Teleport to="body" v-if="showPreviewModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-7xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                            Request for Undertime Preview
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
                                <p class="text-xs font-bold text-gray-900 mb-2">PROVINCIAL BUDGET OFFICE</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                        <div>
                            <p class="font-bold text-lg text-center text-gray-900 mb-6">REQUEST FOR UNDERTIME</p>
                        </div>

                        <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date_filed).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ getEmployeeOffice() }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-6 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer()?.name || '-' }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content Letter -->
                        <div class="mb-8 text-xs leading-relaxed text-gray-900">
                            <p class="font-semibold text-left mb-4">Ma'am:</p>
                            
                            <p class="mb-4">
                                May I request permission to go undertime for <span class="border-b border-gray-400 w-32 inline-block text-center font-semibold">{{ computeUndertime() }}</span> 
                                from <span class="border-b border-gray-400 w-32 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span>
                                on <span class="border-b border-gray-400 w-48 inline-block text-center font-semibold">{{ new Date(formData.date_filed).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>.
                            </p>

                            <p class="mb-10">
                                I will be leaving my work station to <span class="border-b border-gray-400 w-[410px] inline-block text-center font-semibold">{{ formData.reason }}</span> 
                                and be back <span class="border-b border-gray-400 w-48 inline-block text-center font-semibold">{{ formData.returnType === 'nwd' ? 'Next Working Day' : formatTimeDisplay(formData.return_time) }}</span>.
                            </p>

                            <p class="mb-8 text-left">Respectfully yours,</p>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p v-if="getRequestingEmployee()" class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 uppercase w-96">
                                {{ getRequestingEmployee()?.name }}
                            </p>
                            <p v-else class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-96">[Employee Name]</p>
                            <p v-if="getRequestingEmployee()" class="text-xs text-center text-gray-700 w-96">{{ getRequestingEmployee()?.designation }}</p>
                            <p v-else class="text-xs text-center text-gray-700 w-96">[Designation]</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getSupervisor() && !isRequestingEmployeeProvincialBudgetOfficer()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-96">
                                    <p class="font-bold text-xs text-center text-gray-900 uppercase w-96">{{ getSupervisor()?.name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">{{ getSupervisor()?.designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end pr-8">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="font-bold text-xs text-gray-900 uppercase w-96">{{ getProvincialGovernor()?.name }}</p>
                                <p v-else-if="getProvincialBudgetOfficer()" class="font-bold text-xs text-gray-900 uppercase w-96">{{ getProvincialBudgetOfficer()?.name }}</p>
                                <p v-else class="font-bold text-xs text-gray-900 uppercase w-96">-</p>
                                
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700">Department Head</p>
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
                                <p class="text-xs font-bold text-gray-900 mb-2">PROVINCIAL BUDGET OFFICE</p>
                            </div>
                            <div style="width: 70px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>

                        <div>
                            <p class="font-bold text-lg text-center text-gray-900 mb-6">REQUEST FOR UNDERTIME</p>
                        </div>

                        <!-- Top Information Row -->
                        <div class="mb-6 text-xs space-y-1">
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Date: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ new Date(formData.date_filed).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Office: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ getEmployeeOffice() }}</span></p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700"><span class="w-20 inline-block text-right">Control No.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                            </div>
                        </div>

                        <!-- Provincial Budget Officer Info -->
                        <div class="text-left mb-6 w-40">
                            <p class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-72 uppercase">{{ getProvincialBudgetOfficer()?.name || '-' }}</p>
                            <p class="text-xs text-gray-700 text-center w-72">Provincial Budget Officer</p>
                        </div>

                        <!-- Main Content Letter -->
                        <div class="mb-8 text-xs leading-relaxed text-gray-900">
                            <p class="font-semibold text-left mb-4">Ma'am:</p>
                            
                            <p class="mb-4">
                                May I request permission to go undertime for <span class="border-b border-gray-400 w-32 inline-block text-center font-semibold">{{ computeUndertime() }}</span> 
                                from <span class="border-b border-gray-400 w-32 inline-block text-center font-semibold">{{ formatTimeDisplay(formData.requested_time) }}</span>
                                on <span class="border-b border-gray-400 w-48 inline-block text-center font-semibold">{{ new Date(formData.date_filed).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>.
                            </p>

                            <p class="mb-10">
                                I will be leaving my work station to <span class="border-b border-gray-400 w-[410px] inline-block text-center font-semibold">{{ formData.reason }}</span> 
                                and be back <span class="border-b border-gray-400 w-48 inline-block text-center font-semibold">{{ formData.returnType === 'nwd' ? 'Next Working Day' : formatTimeDisplay(formData.return_time) }}</span>.
                            </p>

                            <p class="mb-8 text-left">Respectfully yours,</p>
                        </div>

                        <!-- Requesting Employee Section -->
                        <div class="mb-6">
                            <p v-if="getRequestingEmployee()" class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 uppercase w-96">
                                {{ getRequestingEmployee()?.name }}
                            </p>
                            <p v-else class="font-bold text-xs text-gray-900 text-center border-b border-gray-400 w-96">[Employee Name]</p>
                            <p v-if="getRequestingEmployee()" class="text-xs text-center text-gray-700 w-96">{{ getRequestingEmployee()?.designation }}</p>
                            <p v-else class="text-xs text-center text-gray-700 w-96">[Designation]</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getSupervisor() && !isRequestingEmployeeProvincialBudgetOfficer()" class="mb-6">
                            <p class="text-xs text-gray-700 mb-8">Recommending Approval:</p>
                            <div class="space-y-8">
                                <div class="w-96">
                                    <p class="font-bold text-xs text-center text-gray-900 uppercase w-96">{{ getSupervisor()?.name }}</p>
                                    <p class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">{{ getSupervisor()?.designation }}</p>
                                    <p class="text-xs text-center text-gray-700 w-96">Immediate Supervisor</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Section -->
                        <div class="mb-6 flex justify-end pr-8">
                            <div class="w-96 text-center">
                                <p class="text-xs text-left text-gray-900 mb-8">APPROVED:</p>
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="font-bold text-xs text-gray-900 uppercase w-96">{{ getProvincialGovernor()?.name }}</p>
                                <p v-else-if="getProvincialBudgetOfficer()" class="font-bold text-xs text-gray-900 uppercase w-96">{{ getProvincialBudgetOfficer()?.name }}</p>
                                <p v-else class="font-bold text-xs text-gray-900 uppercase w-96">-</p>
                                
                                <p v-if="isRequestingEmployeeProvincialBudgetOfficer() && getProvincialGovernor()" class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">Provincial Governor</p>
                                <p v-else class="text-xs text-center text-gray-700 border-b border-gray-400 pb-1 w-96">Provincial Budget Officer</p>
                                <p class="text-xs text-center text-gray-700">Department Head</p>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                        <button @click="printTardinessRequest" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <button @click="confirmPreviewAndSubmit" :disabled="creating || updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i v-if="creating || updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ (creating || updating) ? 'Saving...' : 'Confirm & Save' }}
                        </button>
                        <button @click="closePreviewModal" type="button" :disabled="creating || updating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-arrow-left"></i>
                            Back to Form
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Toast from '@/Components/Toast.vue';
import PageHead from '@/Components/PageHead.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
    office_id?: number;
    office?: {
        id: number;
        name: string;
    };
}

interface TardinessRecord {
    id: number;
    control_no: string;
    date_filed: string;
    type: string;
    requested_date: string;
    employee_id: number;
    employee: Employee;
    requested_time: string;
    reason: string;
    return_time: string | null;
    supervisor_employee_id: number | null;
    supervisor?: Employee;
    created_at: string;
    updated_at: string;
}

const tardiness = ref<TardinessRecord[]>([]);
const employees = ref<Employee[]>([]);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const loading = ref(false);
const error = ref<string | null>(null);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showPreviewModal = ref(false);

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);

const recordToEdit = ref<TardinessRecord | null>(null);
const recordToDelete = ref<TardinessRecord | null>(null);

const formData = ref({
    control_no: '',
    date_filed: '',
    type: 'Undertime',
    requested_date: '',
    employee_id: 0,
    requested_time: '',
    reason: '',
    return_time: '',
    returnType: 'time' as 'time' | 'nwd',
    supervisor_employee_id: null as number | null,
});

const formErrors = ref<Record<string, string>>({});
const toastRef = ref();

// Watch for returnType changes to auto-set return_time for NWD
watch(
    () => formData.value.returnType,
    (newType) => {
        if (newType === 'nwd') {
            formData.value.return_time = '17:00:00'; // 5:00 PM
        }
    }
);

// Watch for date_filed changes to regenerate control number based on its month
watch(
    () => formData.value.date_filed,
    (newDate) => {
        if (newDate) {
            formData.value.control_no = generateControlNo(newDate);
        }
    }
);

// Watch for employee_id changes to handle Provincial Budget Officer approval
watch(
    () => formData.value.employee_id,
    (newEmployeeId) => {
        if (newEmployeeId) {
            const emp = employees.value.find(e => e.id === newEmployeeId);
            if (emp?.designation === 'Provincial Budget Officer') {
                // If requesting employee is PBO, auto-set approver to Provincial Governor
                const pgov = getProvincialGovernor();
                if (pgov) {
                    formData.value.supervisor_employee_id = pgov.id;
                }
            }
        }
    }
);

const todayDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

const hasPermission = (permission: string): boolean => {
    return true; // Placeholder - actual permission checking in layout
};

const generateControlNo = (dateString?: string): string => {
    const dateToUse = dateString ? new Date(dateString) : new Date();
    const year = dateToUse.getFullYear();
    const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
    const prefix = 'TU';
    
    const sameYearCount = tardiness.value.filter(record =>
        record.control_no.startsWith(`${prefix}-${year}`)
    ).length;
    
    const series = String(sameYearCount + 1).padStart(4, '0');
    return `${prefix}-${year}-${month}-${series}`;
};

const filteredTardiness = computed(() => {
    let filtered = tardiness.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(record =>
            record.control_no.toLowerCase().includes(query) ||
            record.employee?.name.toLowerCase().includes(query) ||
            record.reason.toLowerCase().includes(query) ||
            record.type.toLowerCase().includes(query)
        );
    }

    // Sort by ID in descending order (newest first)
    return filtered.sort((a, b) => b.id - a.id);
});

const sortedEmployees = computed(() => {
    return employees.value.slice().sort((a, b) => {
        const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
        const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
        return lastNameA.localeCompare(lastNameB);
    });
});

const totalPages = computed(() => {
    return Math.ceil(filteredTardiness.value.length / itemsPerPage.value);
});

const paginatedTardiness = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTardiness.value.slice(start, end);
});

const paginationRange = computed(() => {
    const range: (number | string)[] = [];
    const maxPages = 5;
    const halfPages = Math.floor(maxPages / 2);

    if (totalPages.value <= maxPages) {
        for (let i = 1; i <= totalPages.value; i++) {
            range.push(i);
        }
    } else {
        let start = Math.max(1, currentPage.value - halfPages);
        let end = Math.min(totalPages.value, start + maxPages - 1);

        if (end - start < maxPages - 1) {
            start = Math.max(1, end - maxPages + 1);
        }

        if (start > 1) {
            range.push(1);
            if (start > 2) range.push('...');
        }

        for (let i = start; i <= end; i++) {
            range.push(i);
        }

        if (end < totalPages.value) {
            if (end < totalPages.value - 1) range.push('...');
            range.push(totalPages.value);
        }
    }

    return range;
});

const fetchTardiness = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch('/api/tardiness');
        if (!response.ok) throw new Error('Failed to fetch records');
        const result = await response.json();
        tardiness.value = result.data || result;
    } catch (err: any) {
        error.value = err.message;
        toastRef.value?.add('error', 'Error', err.message, 4000);
    } finally {
        loading.value = false;
    }
};

const computeUndertime = (): string => {
    // Return empty if times are not provided
    if (!formData.value.requested_time || !formData.value.return_time) {
        return '';
    }
    
    try {
        // Parse requested_time (when employee wants to leave)
        const [startHour, startMin] = formData.value.requested_time.split(':').map(Number);
        // Parse return_time (when employee returns)
        // For NWD, return_time is automatically set to 17:00:00 (5:00 PM)
        const [endHour, endMin] = formData.value.return_time.split(':').map(Number);
        
        // Convert to total minutes since midnight
        const requestedTotalMin = startHour * 60 + startMin;
        const returnTotalMin = endHour * 60 + endMin;
        
        // Check if time span crosses midnight (for lunch calculation)
        const isDayWrap = returnTotalMin < requestedTotalMin;
        
        // Calculate difference: return_time - requested_time
        let diffMin = returnTotalMin - requestedTotalMin;
        
        // Handle case where it spans to next day (e.g., 11:00 PM to 1:00 AM)
        if (diffMin < 0) {
            diffMin += 24 * 60;
        }
        
        // Exclude lunch break (12:00 PM to 1:00 PM)
        const LUNCH_START = 12 * 60;  // 720 minutes
        const LUNCH_END = 13 * 60;    // 780 minutes
        const LUNCH_DURATION = 60;    // 1 hour
        
        let shouldSubtractLunch = false;
        
        if (isDayWrap) {
            // Spans midnight - lunch period is always included
            shouldSubtractLunch = true;
        } else {
            // Same day - check if lunch period falls within the time span
            if (requestedTotalMin < LUNCH_START && returnTotalMin > LUNCH_END) {
                shouldSubtractLunch = true;
            }
        }
        
        if (shouldSubtractLunch) {
            diffMin -= LUNCH_DURATION;
        }
        
        // Convert total minutes back to hours and minutes
        const hours = Math.floor(diffMin / 60);
        const minutes = diffMin % 60;
        
        // Format the result
        if (hours === 0) {
            return minutes > 0 ? `${minutes} mins` : '0 mins';
        } else if (minutes === 0) {
            return `${hours} hrs`;
        } else {
            return `${hours} hrs and ${minutes} mins`;
        }
    } catch (e) {
        console.error('Error computing undertime:', e);
        return '';
    }
};

const getRequestingEmployee = (): Employee | null => {
    if (!formData.value.employee_id) return null;
    return employees.value.find(emp => emp.id === formData.value.employee_id) || null;
};

const getSupervisor = (): Employee | null => {
    if (!formData.value.supervisor_employee_id) return null;
    return employees.value.find(emp => emp.id === formData.value.supervisor_employee_id) || null;
};

const getProvincialBudgetOfficer = (): Employee | null => {
    return employees.value.find((emp: Employee) => emp.designation === 'Provincial Budget Officer') || null;
};

const getProvincialGovernor = (): Employee | null => {
    return employees.value.find((emp: Employee) => emp.designation === 'Provincial Governor') || null;
};

const isRequestingEmployeeProvincialBudgetOfficer = (): boolean => {
    const emp = getRequestingEmployee();
    return emp?.designation === 'Provincial Budget Officer';
};

const getEmployeeOffice = (): string => {
    const emp = getRequestingEmployee();
    return emp?.office?.name || 'Provincial Budget Office';
};

const formatDateForInput = (dateStr: string | null | undefined): string => {
    if (!dateStr) return '';
    // Use local date methods to avoid timezone conversion issues
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const formatTimeForInput = (timeStr: string | null | undefined): string => {
    if (!timeStr) return '';
    // Extract HH:mm from time string (removes seconds if present)
    return timeStr.substring(0, 5);
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (timeStr: string): string => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

const formatTimeDisplay = (timeStr: string): string => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
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

const validateForm = (): boolean => {
    formErrors.value = {};

    if (!formData.value.date_filed.trim()) {
        formErrors.value['date_filed'] = 'Date filed is required';
    }

    if (!formData.value.type) {
        formErrors.value['type'] = 'Type is required';
    }

    if (!formData.value.requested_date.trim()) {
        formErrors.value['requested_date'] = 'Requested date is required';
    }

    if (!formData.value.employee_id) {
        formErrors.value['employee_id'] = 'Employee is required';
    }

    if (!formData.value.requested_time.trim()) {
        formErrors.value['requested_time'] = 'Requested time is required';
    }

    if (!formData.value.reason.trim()) {
        formErrors.value['reason'] = 'Reason is required';
    }

    return Object.keys(formErrors.value).length === 0;
};

const openCreateModal = () => {
    const today = new Date().toISOString().split('T')[0];
    formData.value = {
        control_no: '',
        date_filed: today,
        type: 'Undertime',
        requested_date: '',
        employee_id: 0,
        requested_time: '',
        reason: '',
        return_time: '',
        returnType: 'time',
        supervisor_employee_id: null,
    };
    // Generate control number based on date_filed
    formData.value.control_no = generateControlNo(today);
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitCreateForm = async () => {
    if (!validateForm()) return;
    
    // Open preview modal instead of directly submitting
    showPreviewModal.value = true;
};

const closePreviewModal = () => {
    showPreviewModal.value = false;
};

const confirmPreviewAndSubmit = async () => {
    try {
        const isUpdate = recordToEdit.value !== null;
        const isLoading = isUpdate ? 'updating' : 'creating';
        eval(`${isLoading}.value = true`);
        
        let returnTime = formData.value.return_time;
        if (formData.value.returnType === 'nwd') {
            returnTime = '17:00:00'; // 5:00 PM for NWD
        }

        const submitData = {
            control_no: formData.value.control_no,
            date_filed: formData.value.date_filed,
            type: formData.value.type,
            requested_date: formData.value.requested_date,
            employee_id: formData.value.employee_id,
            requested_time: formData.value.requested_time,
            reason: formData.value.reason,
            return_time: returnTime,
            supervisor_employee_id: formData.value.supervisor_employee_id,
        };

        const url = isUpdate ? `/api/tardiness/${recordToEdit.value!.id}` : '/api/tardiness';
        const method = isUpdate ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(submitData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            
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
            
            throw new Error(errorData.error || errorData.message || `Failed to ${isUpdate ? 'update' : 'create'} record`);
        }

        const result = await response.json();
        showPreviewModal.value = false;

        if (isUpdate) {
            const index = tardiness.value.findIndex(r => r.id === recordToEdit.value!.id);
            if (index !== -1) {
                tardiness.value[index] = result.data;
            }
            closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                `Record <strong>${result.data.control_no}</strong> updated successfully!`,
                3000
            );
        } else {
            tardiness.value.push(result.data);
            closeCreateModal();
            toastRef.value?.add(
                'success',
                'Success',
                `Record <strong>${result.data.control_no}</strong> created successfully!`,
                3000
            );
        }
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
        formErrors.value['submit'] = errorMsg;
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        creating.value = false;
        updating.value = false;
    }
};

const openEditModal = (record: TardinessRecord) => {
    recordToEdit.value = record;
    
    formData.value = {
        control_no: record.control_no,
        date_filed: formatDateForInput(record.date_filed),
        type: record.type,
        requested_date: formatDateForInput(record.requested_date),
        employee_id: record.employee_id,
        requested_time: formatTimeForInput(record.requested_time),
        reason: record.reason,
        return_time: record.return_time || '',
        returnType: (record.return_time === 'NWD' || record.return_time === '17:00:00') ? 'nwd' : 'time',
        supervisor_employee_id: record.supervisor_employee_id || null,
    };
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    recordToEdit.value = null;
};

const submitEditForm = async () => {
    if (!validateForm() || !recordToEdit.value) return;

    // Open preview modal instead of directly submitting
    showPreviewModal.value = true;
};

const openDeleteModal = (record: TardinessRecord) => {
    recordToDelete.value = record;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    recordToDelete.value = null;
};

const confirmDelete = async () => {
    if (!recordToDelete.value) return;

    try {
        deleting.value = true;

        const response = await fetch(`/api/tardiness/${recordToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.message || 'Failed to delete record');
        }

        const controlNo = recordToDelete.value.control_no;
        tardiness.value = tardiness.value.filter(r => r.id !== recordToDelete.value!.id);
        closeDeleteModal();

        toastRef.value?.add(
            'error',
            'Deleted',
            `Record <strong>${controlNo}</strong> deleted successfully!`,
            3000
        );
    } catch (e) {
        const errorMsg = e instanceof Error ? e.message : 'An error occurred';
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

/**
 * Print the undertime request preview
 */
const printTardinessRequest = () => {
    window.print();
};

onMounted(() => {
    fetchTardiness();
    fetchEmployees();
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

/* Print Styles: Hide modal chrome and scrollbars */
@media print {
    /* Hide all modal chrome and UI elements */
    .sticky {
        position: static !important;
    }
    
    .sticky.top-0 {
        display: none !important;
    }
    
    .sticky.bottom-0 {
        display: none !important;
    }
    
    /* Remove scrollbars and margins */
    body {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    
    html {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    
    /* Remove scroll from modal/container */
    .overflow-y-auto {
        overflow: visible !important;
    }
    
    /* Remove shadows from printed form */
    .shadow-2xl {
        box-shadow: none !important;
    }
    
    [class*="shadow"] {
        box-shadow: none !important;
    }
}
</style>
