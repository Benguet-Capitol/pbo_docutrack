<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Travel Orders
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section: Contains Create button, search bar, and items-per-page selector -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Create Travel Order Button -->
                        <button v-if="hasPermission('travel-orders.create')" @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create Travel Order
                        </button>
                        <div :class="['flex items-center gap-3', !hasPermission('travel-orders.create') && 'sm:ml-auto']">
                            <i class="fas fa-search text-gray-400"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search travel orders..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading travel orders...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Travel Orders</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="travelOrders.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No travel orders found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new travel order</p>
                </div>

                <!-- No Travel Orders for Search -->
                <div v-else-if="filteredTravelOrders.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No travel orders found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Try adjusting your search or create a new travel order</p>
                </div>

                <!-- Data Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-24">
                            <col class="w-20">
                            <col class="w-28">
                            <col class="w-28">
                            <col class="w-32">
                            <col class="w-28">
                            <col class="w-20">
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
                                    <button @click="toggleSort('employees')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Employees
                                        <span v-if="sortBy === 'employees'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('going_to')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Going To
                                        <span v-if="sortBy === 'going_to'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Dates</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Purpose</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">Vehicle</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="order in paginatedTravelOrders" :key="order.id" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">{{ order.control_no }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ formatDateForDisplay(order.date) }}</td>
                                <td class="px-4 py-2 text-xs">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="emp in order.employees" :key="emp.id" class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                            {{ emp.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ order.going_to }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span v-if="order.from_date === order.to_date">
                                        {{ formatDateForDisplay(order.from_date) }}
                                    </span>
                                    <span v-else>
                                        {{ formatDateForDisplay(order.from_date) }} - {{ formatDateForDisplay(order.to_date) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="p in order.purpose" :key="p" class="inline-block px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-xs">
                                            {{ p }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ order.vehicle }}</td>
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit Button -->
                                        <button
                                            v-if="hasPermission('travel-orders.update')"
                                            @click.stop="handleEditTravelOrder(order)"
                                            class="relative p-2 text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group"
                                        >
                                            <i class="fas fa-pencil-alt"></i>
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-950 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-20">Edit</span>
                                        </button>
                                        <!-- Delete Button -->
                                        <button
                                            v-if="hasPermission('travel-orders.delete')"
                                            @click.stop="openDeleteModal(order)"
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
                <div v-if="!loading && !error && travelOrders.length > 0" class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredTravelOrders.length) }} of {{ filteredTravelOrders.length }} results
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
                                v-for="page in paginationPages"
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
                            <i class="fas fa-bus text-emerald-600 dark:text-emerald-400"></i>
                            Create Travel Order
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
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="create_control_no" type="text" disabled placeholder="Auto-generated" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                                </div>
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
                                        <div v-if="employees.length === 0" class="text-xs text-gray-500 dark:text-gray-400 py-2">
                                            No employees available
                                        </div>
                                        <label v-for="emp in sortedEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                            <input type="checkbox" :value="emp.id" v-model.number="formData.employee_ids" class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                        </label>
                                    </div>
                                    <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-500 dark:text-gray-100">
                                        {{ formData.employee_ids.length }} employee(s) selected
                                    </p>
                                </div>
                                <span v-if="formErrors.employee_ids" class="text-red-500 text-xs">{{ formErrors.employee_ids }}</span>
                            </div>

                            <!-- Going To Field -->
                            <div class="space-y-2">
                                <label for="create_going_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Going To <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.going_to" id="create_going_to" type="text" placeholder="Destination" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.going_to ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.going_to" class="text-red-500 text-xs">{{ formErrors.going_to }}</span>
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label for="create_from_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">From <span class="text-red-600">*</span></label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                        <input v-model="formData.from_date" id="create_from_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.from_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                    </div>
                                    <span v-if="formErrors.from_date" class="text-red-500 text-xs">{{ formErrors.from_date }}</span>
                                </div>
                                <div class="space-y-2">
                                    <label for="create_to_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">To <span class="text-red-600">*</span></label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                        <input v-model="formData.to_date" id="create_to_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.to_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                    </div>
                                    <span v-if="formErrors.to_date" class="text-red-500 text-xs">{{ formErrors.to_date }}</span>
                                </div>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="flex gap-2">
                                    <textarea v-model="formData.newPurpose" placeholder="Add purpose..." class="flex-1 px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors resize-none" rows="3"></textarea>
                                    <button @click.prevent="addPurpose" type="button" class="px-4 py-2 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors h-fit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div v-if="formData.purpose.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="(p, index) in formData.purpose" :key="index" class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded-full text-xs">
                                        {{ p }}
                                        <button @click="removePurpose(index)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </span>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
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

                            <!-- Approver Field -->
                            <div class="space-y-2">
                                <label for="create_approver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Approved By <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.approver_employee_id"
                                        @change="(e) => { formData.approver_employee_id = (e.target as HTMLSelectElement).value ? Number((e.target as HTMLSelectElement).value) : null }"
                                        id="create_approver"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                        :class="[formErrors.approver_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                    >
                                        <option :value="null">Select approver</option>
                                        <option v-for="emp in employees.filter(e => e.designation === 'Provincial Governor' || e.designation === 'Provincial Budget Officer')" :key="emp.id" :value="emp.id">
                                            {{ emp.name }} ({{ emp.designation }})
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.approver_employee_id" class="text-red-500 text-xs">{{ formErrors.approver_employee_id }}</span>
                            </div>

                            <!-- Driver Field -->
                            <div class="space-y-2">
                                <label for="create_driver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Driver</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.driver" id="create_driver" type="text" list="create_driver_options" placeholder="Enter or select driver name" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.driver ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                    <datalist id="create_driver_options">
                                        <option v-for="driver in driverOptions" :key="driver" :value="driver" />
                                    </datalist>
                                </div>
                                <span v-if="formErrors.driver" class="text-red-500 text-xs">{{ formErrors.driver }}</span>
                            </div>

                            <!-- Vehicle Field -->
                            <div class="space-y-2">
                                <label for="create_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-car absolute left-3 text-gray-400 text-sm"></i>
                                    <select v-model="formData.vehicle" id="create_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']">
                                        <option value="PUJ">PUJ</option>
                                        <option value="RP Vehicle">RP Vehicle</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Plate Number Field (RP Vehicle Only) -->
                            <div v-if="formData.vehicle === 'RP Vehicle'" class="space-y-2">
                                <label for="create_plate_number" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Plate Number</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.plate_number" id="create_plate_number" type="text" placeholder="Enter plate number" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.plate_number ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.plate_number" class="text-red-500 text-xs">{{ formErrors.plate_number }}</span>
                            </div>

                            <span v-if="formErrors.submit" class="text-red-500 text-xs">{{ formErrors.submit }}</span>
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
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit Travel Order
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
                                <label for="edit_control_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-barcode absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.control_no" id="edit_control_no" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label for="edit_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.date" id="edit_date" type="date" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors opacity-75 cursor-not-allowed bg-gray-100 dark:bg-gray-600 border-gray-300" />
                                </div>
                            </div>

                            <!-- Requesting Employees Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Requesting Employee(s)</label>
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-32 overflow-y-auto">
                                    <label v-for="emp in sortedEmployees" :key="emp.id" class="flex items-center gap-2 py-1 cursor-pointer">
                                        <input type="checkbox" :value="emp.id" v-model.number="formData.employee_ids" class="rounded border-gray-300 dark:border-gray-600 accent-emerald-600" />
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ emp.name }}</span>
                                    </label>
                                </div>
                                <p v-if="formData.employee_ids.length > 0" class="text-xs text-gray-800 dark:text-gray-100">{{ formData.employee_ids.length }} employee(s) selected</p>
                            </div>

                            <!-- Going To Field -->
                            <div class="space-y-2">
                                <label for="edit_going_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Going To <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-map-marker-alt absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.going_to" id="edit_going_to" type="text" placeholder="Destination" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.going_to ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.going_to" class="text-red-500 text-xs">{{ formErrors.going_to }}</span>
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label for="edit_from_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">From <span class="text-red-600">*</span></label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                        <input v-model="formData.from_date" id="edit_from_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.from_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                    </div>
                                    <span v-if="formErrors.from_date" class="text-red-500 text-xs">{{ formErrors.from_date }}</span>
                                </div>
                                <div class="space-y-2">
                                    <label for="edit_to_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">To <span class="text-red-600">*</span></label>
                                    <div class="relative flex items-center">
                                        <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                        <input v-model="formData.to_date" id="edit_to_date" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.to_date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                    </div>
                                    <span v-if="formErrors.to_date" class="text-red-500 text-xs">{{ formErrors.to_date }}</span>
                                </div>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="flex gap-2">
                                    <textarea v-model="formData.newPurpose" placeholder="Add purpose..." class="flex-1 px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500 transition-colors resize-none" rows="3"></textarea>
                                    <button @click.prevent="addPurpose" type="button" class="px-4 py-2 text-xs bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors h-fit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div v-if="formData.purpose.length > 0" class="flex flex-wrap gap-2">
                                    <div v-for="(p, index) in formData.purpose" :key="index" class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded-full text-xs">
                                        <span v-if="editingPurposeIndex !== index">{{ p }}</span>
                                        <textarea v-else v-model="editingPurposeValue" class="bg-emerald-200 dark:bg-emerald-800 border-0 px-2 py-1 rounded text-emerald-900 dark:text-emerald-100 text-xs focus:outline-none resize-none" rows="4" @keyup.enter="saveEditPurpose(index)" @keyup.escape="cancelEditPurpose"></textarea>
                                        <div class="flex gap-1">
                                            <button v-if="editingPurposeIndex !== index" @click="startEditPurpose(index)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button v-else @click="saveEditPurpose(index)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                            <button v-if="editingPurposeIndex !== index" @click="removePurpose(index)" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button v-else @click="cancelEditPurpose" type="button" class="hover:text-emerald-600 dark:hover:text-emerald-300 transition-colors">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
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

                            <!-- Approver Field -->
                            <div class="space-y-2">
                                <label for="edit_approver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Approved By <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-file-signature absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <select
                                        :value="formData.approver_employee_id"
                                        @change="(e) => { formData.approver_employee_id = (e.target as HTMLSelectElement).value ? Number((e.target as HTMLSelectElement).value) : null }"
                                        id="edit_approver"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                        :class="[formErrors.approver_employee_id ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']"
                                    >
                                        <option :value="null">Select approver</option>
                                        <option v-for="emp in employees.filter(e => e.designation === 'Provincial Governor' || e.designation === 'Provincial Budget Officer')" :key="emp.id" :value="emp.id">
                                            {{ emp.name }} ({{ emp.designation }})
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.approver_employee_id" class="text-red-500 text-xs">{{ formErrors.approver_employee_id }}</span>
                            </div>

                            <!-- Driver Field -->
                            <div class="space-y-2">
                                <label for="edit_driver" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Driver</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input v-model="formData.driver" id="edit_driver" type="text" list="edit_driver_options" placeholder="Enter or select driver name" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.driver ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                    <datalist id="edit_driver_options">
                                        <option v-for="driver in driverOptions" :key="driver" :value="driver" />
                                    </datalist>
                                </div>
                                <span v-if="formErrors.driver" class="text-red-500 text-xs">{{ formErrors.driver }}</span>
                            </div>

                            <!-- Vehicle Field -->
                            <div class="space-y-2">
                                <label for="edit_vehicle" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Vehicle <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-car absolute left-3 text-gray-400 text-sm"></i>
                                    <select v-model="formData.vehicle" id="edit_vehicle" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none" :class="[formErrors.vehicle ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']">
                                        <option value="PUJ">PUJ</option>
                                        <option value="RP Vehicle">RP Vehicle</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.vehicle" class="text-red-500 text-xs">{{ formErrors.vehicle }}</span>
                            </div>

                            <!-- Plate Number Field (RP Vehicle Only) -->
                            <div v-if="formData.vehicle === 'RP Vehicle'" class="space-y-2">
                                <label for="edit_plate_number" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Plate Number</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                    <input v-model="formData.plate_number" id="edit_plate_number" type="text" placeholder="Enter plate number" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.plate_number ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-blue-500']" />
                                </div>
                                <span v-if="formErrors.plate_number" class="text-red-500 text-xs">{{ formErrors.plate_number }}</span>
                            </div>

                            <span v-if="formErrors.submit" class="text-red-500 text-xs">{{ formErrors.submit }}</span>
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

        <!-- Delete Confirmation Modal -->
        <Teleport to="body" v-if="showDeleteModal && travelOrderToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete Travel Order
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
                                    Are you sure you want to delete travel order <span class="font-semibold">{{ travelOrderToDelete.control_no }}</span>?
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
                            @click="submitDeleteForm"
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

        <!-- Preview Modal -->
        <Teleport to="body" v-if="showPreviewModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-emerald-600 dark:text-emerald-400"></i>
                            Travel Order Preview
                        </h3>
                        <button @click="closePreviewModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body - Single Copy Preview -->
                    <div class="p-4" style="background-color: white;">
                        <!-- Header Section with Logos -->
                        <div class="flex items-center justify-center gap-2 mb-6 pb-2" style="border-bottom: 3px double #050505;">
                            <div style="width: 85px; flex-shrink: 0;">
                                <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                                <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                                <p class="text-lg font-bold text-gray-900" v-if="isApproverProvincialGovernor()">OFFICE OF THE PROVINCIAL GOVERNOR</p>
                                <p class="text-lg font-bold text-gray-900" v-else>PROVINCIAL BUDGET OFFICE</p>
                                <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                            </div>
                            <div style="width: 85px; flex-shrink: 0;">
                                <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                            </div>
                        </div>
                        
                        <div>
                            <p class="font-bold text-2xl text-center text-gray-900 mb-8">TRAVEL ORDER</p>
                        </div>
                        <!-- Date -->
                        <div class="mb-6 flex justify-end pr-8">
                            <div class="text-center text-sm">
                                <p><span class="w-26 inline-block text-right">PBO CONTROL NO.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formData.control_no }}</span></p>
                                <p v-if="isApproverProvincialGovernor()" class="mt-2"><span class="w-26 inline-block text-right">PGO CONTROL NO.: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">   </span></p>
                                <p class="mt-1"><span class="w-26 inline-block text-right">DATE: </span><span class="font-bold text-gray-900 w-48 border-b border-gray-400 inline-block text-center">{{ formatDateForDisplayFull(formData.date) }}</span></p>
                            </div>
                        </div>

                        <!-- Requesting Employees -->
                        <div class="space-y-2 text-sm mb-10">
                            <div class="space-y-1">
                                <!-- Vertical Layout for less than 5 employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length && formData.employee_ids.length < 5" class="space-y-1 mb-6">
                                    <div class="flex gap-1 items-start">
                                        <p class="w-7">TO:</p>
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold border-b border-gray-400 uppercase block">{{ employees.find(e => e.id === formData.employee_ids[0])?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === formData.employee_ids[0])?.designation || '' }}</span>
                                        </div>
                                    </div>
                                    <div v-for="(empId, index) in formData.employee_ids.slice(1)" :key="empId" class="flex gap-2">
                                        <span class="w-7"></span>
                                        <div class="text-center">
                                            <span class="w-56 text-center font-semibold border-b border-gray-400 uppercase block">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 2-Column Grid Layout for 5-8 employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length >= 5 && formData.employee_ids.length < 9" class="mb-6">
                                    <p class="mb-1">TO:</p>
                                    <div class="grid grid-cols-2 gap-1 space-y-1">
                                        <div v-for="(empId, index) in formData.employee_ids" :key="empId" class="text-center">
                                            <span class="block text-center font-semibold border-b border-gray-400 uppercase text-sm">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3-Column Grid Layout for 9 or more employees -->
                                <div v-if="formData.employee_ids && formData.employee_ids.length >= 9" class="mb-6">
                                    <p class="mb-1">TO:</p>
                                    <div class="grid grid-cols-3 gap-1 space-y-1">
                                        <div v-for="(empId, index) in formData.employee_ids" :key="empId" class="text-center">
                                            <span class="block text-center font-semibold border-b border-gray-400 uppercase text-xs">{{ employees.find(e => e.id === empId)?.name || '' }}</span>
                                            <span class="text-xs text-gray-700">{{ employees.find(e => e.id === empId)?.designation || '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nature of Travel -->
                        <div class="space-y-2 text-sm mb-6">
                            <p><span class="inline-block">NATURE OF TRAVEL:</span><span class="border-b border-gray-400 w-48 inline-block text-center font-semibold">OFFICIAL BUSINESS</span></p>
                        </div>

                        <!-- Body Content -->
                        <div class="space-y-2 text-sm mb-6">
                            <p class="space-y-2">
                                <span class="inline-block indent-8">You are hereby authorized to go to </span> 
                                <span class="border-b border-gray-400 w-[540px] inline-block text-center font-semibold pr-8">{{ formData.going_to }}</span> 
                                on
                                <span class="border-b border-gray-400 w-40 inline-block text-center font-semibold">{{ formatDateForDisplayFull(formData.from_date) }}</span>
                                <span v-if="formData.to_date && formData.to_date !== formData.from_date"> to <span class="border-b border-gray-400 w-40 inline-block text-center font-semibold">{{ formatDateForDisplayFull(formData.to_date) }}</span></span>
                                for the following official duties, viz:
                            </p>
                        </div>

                        <!-- Purpose List -->
                        <div class="space-y-2 text-sm mb-6">
                            <ol v-if="formData.purpose && formData.purpose.length" class="list-decimal pl-8 list-outside space-y-3">
                                <li v-for="(purpose, idx) in formData.purpose" :key="idx" class="ml-4"><span class="border-b border-gray-400 font-semibold text-left pb-1" style="display: inline; box-decoration-break: clone; -webkit-box-decoration-break: clone; max-width: 750px; line-height: 1.8;">{{ purpose }}</span></li>
                            </ol>
                        </div>

                                
                        <p class="text-justify indent-8 space-y-2 text-sm mb-8">
                            You are expected to report on your mission immediately upon your return.
                        </p>

                        <!-- Driver -->
                        <div v-if="formData.driver" class="text-sm flex items-start gap-2 mb-2">
                            <p>DRIVER:</p>
                            <p class="border-b border-gray-400 w-40 inline-block text-center font-semibold uppercase">{{ formData.driver }}</p>
                        </div>

                        <!-- Vehicle -->
                        <div class="text-sm flex items-start gap-2 mb-8">
                            <p>VEHICLE:</p>
                            <p class="border-b border-gray-400 w-40 inline-block text-center font-semibold" v-if="formData.vehicle === 'RP Vehicle' && formData.plate_number">{{ formData.vehicle }} - {{ formData.plate_number }}</p>
                            <p class="border-b border-gray-400 w-40 inline-block text-center font-semibold"v-else>{{ formData.vehicle }}</p>
                        </div>

                        <!-- Recommending Approval -->
                        <div v-if="getRecommendingApprovalSignatory() && !(isApproverProvincialGovernor() && isProvincialBudgetOfficerRequestingEmployee())" class="mb-6">
                            <p class="text-sm text-gray-900 mb-8">RECOMMENDING APPROVAL:</p>
                            <div class="space-y-8">
                                <div class="w-72">
                                    <p class="font-bold text-sm text-center text-gray-900 w-72 uppercase" :class="{ 'border-b border-gray-400 pb-1': isApproverProvincialGovernor() }">{{ getRecommendingApprovalSignatory().name }}</p>
                                    <p v-if="!isApproverProvincialGovernor()" class="text-sm text-center text-gray-700 border-b border-gray-400 w-72">{{ getRecommendingApprovalSignatory().designation }}</p>
                                    <p class="text-sm text-center text-gray-700 w-72">{{ getRecommendingApprovalDesignation() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="mb-6 flex justify-end pr-8 mb-10">
                            <div class="w-72 text-center">
                                <p class="text-sm text-left text-gray-900 mb-8">APPROVED:</p>
                                <p class="text-sm text-center text-gray-700"></p>
                                <p class="text-sm text-center text-gray-900 border-b border-gray-400 pb-1 font-bold uppercase">{{ getApproverName() }}</p>
                                <p class="text-sm text-center text-gray-700">{{ getApproverRole() }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mb-6" style="border-top: 3px double #050505;">
                            <div class="flex justify-between items-center mt-2">
                                <p><span class="font-semibold text-sm">PBO Telephone No.:</span> <span class="text-sm text-gray-900">(074) 422-1378, ext. 134</span></p>
                                <p><span class="font-semibold text-sm">Website:</span> <span class="text-sm text-gray-900">www.benguet.gov.ph</span></p>
                            </div>
                            <p><span class="font-semibold">Email:</span> <span class="text-sm text-gray-900">benguetpbo@benguet.gov.ph</span></p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800 sticky bottom-0">
                        <button @click="printPreview" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <button
                            @click="confirmPreviewAndSubmit"
                            :disabled="creating || updating"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="creating || updating" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ (creating || updating) ? 'Saving...' : 'Confirm & Save' }}
                        </button>
                        <button
                            @click="closePreviewModal"
                            type="button"
                            :disabled="creating || updating"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
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

interface TravelOrder {
    id: number;
    control_no: string;
    date: string;
    going_to: string;
    from_date: string;
    to_date: string;
    purpose: string[];
    vehicle: string;
    plate_number?: string;
    employees: Employee[];
    driver?: string;
    supervisor_employee_id: number | null;
    supervisor?: Employee;
    approver_employee_id: number | null;
    created_at: string;
    updated_at: string;
}

const travelOrders = ref<TravelOrder[]>([]);
const employees = ref<Employee[]>([]);

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref('id');
const sortOrder = ref<'desc' | 'asc'>('desc');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showPreviewModal = ref(false);

const creating = ref(false);
const updating = ref(false);
const deleting = ref(false);
const loading = ref(false);
const error = ref<string | null>(null);

const travelOrderToEdit = ref<TravelOrder | null>(null);
const travelOrderToDelete = ref<TravelOrder | null>(null);

const editingPurposeIndex = ref<number | null>(null);
const editingPurposeValue = ref('');

const formData = ref({
    control_no: '',
    date: '',
    going_to: '',
    from_date: '',
    to_date: '',
    purpose: [] as string[],
    vehicle: 'RP Vehicle' as 'PUJ' | 'RP Vehicle',
    plate_number: '',
    employee_ids: [] as number[],
    driver: '',
    newPurpose: '',
    supervisor_employee_id: null as number | null,
    approver_employee_id: null as number | null,
});

const formErrors = ref<Record<string, string>>({});
const toastRef = ref();

const hasPermission = (permission: string): boolean => {
    return true;
};

const filteredTravelOrders = computed(() => {
    let filtered = travelOrders.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(order =>
            order.control_no.toLowerCase().includes(query) ||
            order.going_to.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    filtered.sort((a, b) => {
        let aValue: any;
        let bValue: any;

        if (sortBy.value === 'id') {
            aValue = a.id;
            bValue = b.id;
        } else if (sortBy.value === 'date') {
            aValue = a.date;
            bValue = b.date;
        } else if (sortBy.value === 'control_no') {
            aValue = a.control_no;
            bValue = b.control_no;
        } else if (sortBy.value === 'employees') {
            aValue = a.employees.length;
            bValue = b.employees.length;
        } else {
            aValue = a.going_to;
            bValue = b.going_to;
        }

        if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }

        if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return filtered;
});

const sortedEmployees = computed(() => {
    return employees.value.slice().sort((a, b) => {
        const lastNameA = a.name.split(' ').pop()?.toLowerCase() || '';
        const lastNameB = b.name.split(' ').pop()?.toLowerCase() || '';
        return lastNameA.localeCompare(lastNameB);
    });
});

const driverOptions = computed(() => {
    return employees.value
        .filter(emp => emp.office_id === 12)
        .map(emp => emp.name)
        .sort();
});

const getSupervisor = (): Employee | null => {
    if (!formData.value.supervisor_employee_id) {
        return null;
    }
    return employees.value.find(emp => emp.id === formData.value.supervisor_employee_id) || null;
};

const paginatedTravelOrders = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTravelOrders.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredTravelOrders.value.length / itemsPerPage.value);
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

const fetchTravelOrders = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch('/api/travel-orders');
        if (!response.ok) throw new Error('Failed to fetch travel orders');
        travelOrders.value = await response.json();
    } catch (err: any) {
        error.value = err.message;
        showToast('Failed to load travel orders', 'error');
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
    const prefix = 'TO';
    
    // Find the maximum series number for this year to continue sequencing even if items are deleted
    const maxSeries = Math.max(
        ...travelOrders.value
            .filter(order => order.control_no.startsWith(`${prefix}-${year}`))
            .map(order => {
                const parts = order.control_no.split('-');
                return parseInt(parts[parts.length - 1], 10) || 0;
            }),
        0
    );
    
    const series = String(maxSeries + 1).padStart(4, '0');
    return `${prefix}-${year}-${month}-${series}`;
};

const formatDateForDisplay = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatDateForDisplayFull = (dateStr: string): string => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateForInput = (dateStr: string | null | undefined): string => {
    if (!dateStr) return '';
    // Ensure we get YYYY-MM-DD format for HTML date input
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const toggleSort = (field: string) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
    currentPage.value = 1;
};

const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.date.trim()) {
        formErrors.value['date'] = 'Date is required';
    }
    
    if (!formData.value.going_to.trim()) {
        formErrors.value['going_to'] = 'Going to is required';
    }

    if (!formData.value.from_date.trim()) {
        formErrors.value['from_date'] = 'From date is required';
    }

    if (!formData.value.to_date.trim()) {
        formErrors.value['to_date'] = 'To date is required';
    }

    if (formData.value.from_date && formData.value.to_date) {
        if (formData.value.to_date < formData.value.from_date) {
            formErrors.value['to_date'] = 'To date must be after from date';
        }
    }

    if (formData.value.purpose.length === 0) {
        formErrors.value['purpose'] = 'At least one purpose is required';
    }

    if (!formData.value.vehicle) {
        formErrors.value['vehicle'] = 'Vehicle is required';
    }

    if (formData.value.employee_ids.length === 0) {
        formErrors.value['employee_ids'] = 'At least one employee is required';
    }

    if (!formData.value.approver_employee_id) {
        formErrors.value['approver_employee_id'] = 'Approver is required';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    if (type === 'success') {
        toastRef.value?.add('success', 'Success', message, 3000);
    } else if (type === 'error') {
        toastRef.value?.add('error', 'Error', message, 4000);
    } else {
        toastRef.value?.add('info', 'Info', message, 3000);
    }
};

const openCreateModal = async () => {
    const today = new Date().toISOString().split('T')[0];
    formData.value = {
        control_no: generateControlNo(today),
        date: today,
        going_to: '',
        from_date: today,
        to_date: today,
        purpose: [],
        vehicle: 'RP Vehicle',
        plate_number: '',
        employee_ids: [],
        driver: '',
        newPurpose: '',
        supervisor_employee_id: null,
        approver_employee_id: null,
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

// Watch for changes to date and regenerate control_no
watch(() => formData.value.date, (newDate) => {
    if (showCreateModal.value && newDate) {
        // Regenerate control number based on the new date
        formData.value.control_no = generateControlNo(newDate);
    }
});

// Watch for changes to from_date and automatically sync to_date
watch(() => formData.value.from_date, (newFromDate) => {
    if (newFromDate && !travelOrderToEdit.value) {
        // Set to_date to the same as from_date for easy navigation (create mode only)
        formData.value.to_date = newFromDate;
    }
});

const addPurpose = () => {
    if (formData.value.newPurpose.trim()) {
        formData.value.purpose.push(formData.value.newPurpose.trim());
        formData.value.newPurpose = '';
    }
};

const removePurpose = (index: number) => {
    formData.value.purpose.splice(index, 1);
};

const startEditPurpose = (index: number) => {
    editingPurposeIndex.value = index;
    editingPurposeValue.value = formData.value.purpose[index];
};

const saveEditPurpose = (index: number) => {
    if (editingPurposeValue.value.trim()) {
        formData.value.purpose[index] = editingPurposeValue.value.trim();
    }
    editingPurposeIndex.value = null;
    editingPurposeValue.value = '';
};

const cancelEditPurpose = () => {
    editingPurposeIndex.value = null;
    editingPurposeValue.value = '';
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
        const isUpdate = travelOrderToEdit.value !== null;
        if (isUpdate) {
            updating.value = true;
        } else {
            creating.value = true;
        }
        
        const submitData = {
            control_no: formData.value.control_no,
            date: formData.value.date,
            going_to: formData.value.going_to,
            from_date: formData.value.from_date,
            to_date: formData.value.to_date,
            purpose: formData.value.purpose,
            vehicle: formData.value.vehicle,
            plate_number: formData.value.plate_number,
            employee_ids: formData.value.employee_ids,
            driver: formData.value.driver,
            supervisor_employee_id: formData.value.supervisor_employee_id,
            approver_employee_id: formData.value.approver_employee_id,
        };

        const url = isUpdate ? `/api/travel-orders/${travelOrderToEdit.value!.id}` : '/api/travel-orders';
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
            
            if (response.status === 422 && errorData.errors) {
                Object.keys(errorData.errors).forEach(field => {
                    formErrors.value[field] = errorData.errors[field][0];
                });
                const errorMessages = Object.values(errorData.errors)
                    .flat()
                    .join(', ');
                throw new Error(errorMessages);
            }
            
            throw new Error(errorData.error || errorData.message || `Failed to ${isUpdate ? 'update' : 'create'} travel order`);
        }

        const result = await response.json();
        showPreviewModal.value = false;

        if (isUpdate) {
            const index = travelOrders.value.findIndex(t => t.id === travelOrderToEdit.value!.id);
            if (index !== -1) {
                travelOrders.value[index] = result.data;
            }
            closeEditModal();
            toastRef.value?.add(
                'info',
                'Updated',
                `Travel order <strong>${result.data.control_no}</strong> updated successfully!`,
                3000
            );
        } else {
            travelOrders.value.push(result.data);
            closeCreateModal();
            toastRef.value?.add(
                'success',
                'Success',
                `Travel order <strong>${result.data.control_no}</strong> created successfully!`,
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

const handleEditTravelOrder = (order: TravelOrder) => {
    travelOrderToEdit.value = order;
    
    console.log('Edit order data:', order);
    console.log('Date:', order.date, 'From:', order.from_date, 'To:', order.to_date);
    
    formData.value = {
        control_no: order.control_no,
        date: formatDateForInput(order.date),
        going_to: order.going_to,
        from_date: formatDateForInput(order.from_date),
        to_date: formatDateForInput(order.to_date),
        purpose: [...order.purpose],
        vehicle: order.vehicle as 'PUJ' | 'RP Vehicle',
        plate_number: order.plate_number || '',
        employee_ids: order.employees.map(emp => emp.id),
        driver: order.driver || '',
        newPurpose: '',
        supervisor_employee_id: order.supervisor_employee_id || null,
        approver_employee_id: order.approver_employee_id || null,
    };
    
    console.log('Form data after assignment:', formData.value.date, formData.value.from_date, formData.value.to_date);
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    travelOrderToEdit.value = null;
};

const submitEditForm = async () => {
    if (!travelOrderToEdit.value) return;
    
    if (!validateForm()) return;

    // Open preview modal instead of directly submitting
    showPreviewModal.value = true;
};

const openDeleteModal = (order: TravelOrder) => {
    travelOrderToDelete.value = order;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    travelOrderToDelete.value = null;
};

const submitDeleteForm = async () => {
    if (!travelOrderToDelete.value) return;

    try {
        deleting.value = true;
        const response = await fetch(`/api/travel-orders/${travelOrderToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to delete travel order');
        }

        const controlNo = travelOrderToDelete.value.control_no;
        await fetchTravelOrders();
        closeDeleteModal();
        
        toastRef.value?.add(
            'error',
            'Deleted',
            `Travel order <strong>${controlNo}</strong> has been deleted successfully!`,
            3000
        );
    } catch (err: any) {
        const errorMsg = err instanceof Error ? err.message : 'An error occurred';
        toastRef.value?.add('error', 'Error', errorMsg, 4000);
    } finally {
        deleting.value = false;
    }
};

// Helper functions for preview modal
const getRequestingEmployeeNames = (): string => {
    if (!formData.value.employee_ids || formData.value.employee_ids.length === 0) return '';
    const names = formData.value.employee_ids
        .map(id => employees.value.find(e => e.id === id)?.name || '')
        .filter(name => name);
    return names.join('\n');
};

const isRequestingEmployeePBO = (): boolean => {
    if (!formData.value.employee_ids || formData.value.employee_ids.length === 0) return false;
    // Check if any requesting employee is PBO
    return formData.value.employee_ids.some(id => {
        const employee = employees.value.find(e => e.id === id);
        return employee?.designation === 'Provincial Budget Officer';
    });
};

const getApproverRole = (): string => {
    if (!formData.value.approver_employee_id) {
        return '';
    }
    const approver = employees.value.find(e => e.id === formData.value.approver_employee_id);
    return approver?.designation || '';
};

const getApproverName = (): string => {
    if (!formData.value.approver_employee_id) {
        return '';
    }
    const approver = employees.value.find(e => e.id === formData.value.approver_employee_id);
    return approver?.name || '';
};

const isApproverProvincialGovernor = (): boolean => {
    return getApproverRole() === 'Provincial Governor';
};

const isProvincialBudgetOfficerRequestingEmployee = (): boolean => {
    const pboEmployee = employees.value.find(e => e.designation === 'Provincial Budget Officer');
    if (!pboEmployee) return false;
    return formData.value.employee_ids.includes(pboEmployee.id);
};

const getRecommendingApprovalDesignation = (): string => {
    // If Provincial Governor is the approver, Provincial Budget Officer recommends
    if (isApproverProvincialGovernor()) {
        return 'Provincial Budget Officer';
    }
    // If Provincial Budget Officer is the approver, the supervisor recommends
    return 'Immediate Supervisor';
};

const getRecommendingApprovalSignatory = (): Employee | null => {
    // If Provincial Governor is the approver, Provincial Budget Officer recommends
    if (isApproverProvincialGovernor()) {
        return employees.value.find(e => e.designation === 'Provincial Budget Officer') || null;
    }
    // If Provincial Budget Officer is the approver, the supervisor recommends
    return getSupervisor();
};

/**
 * Print the travel order preview
 */
const printPreview = () => {
    window.print();
};

onMounted(() => {
    fetchTravelOrders();
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
    
    .fixed.inset-0.flex.items-center.justify-center {
        align-items: flex-start !important;
        padding-top: 0 !important;
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
