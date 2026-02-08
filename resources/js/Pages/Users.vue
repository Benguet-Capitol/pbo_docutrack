<template>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Users
            </h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus"></i>
                            Create User
                        </button>
                        <div class="flex items-center gap-3">
                                <i class="fas fa-search text-gray-400"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search users..."
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
                    <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-400">Loading users...</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Please wait</p>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="px-6 py-6 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/40 rounded-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-900 dark:text-red-200 text-sm">Error Loading Users</h3>
                            <p class="text-red-700 dark:text-red-300 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="users.length === 0" class="px-6 py-12 text-center">
                    <div class="inline-block mb-4">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No users found</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Get started by creating a new user</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <colgroup>
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-20">
                            <col class="w-10">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('name')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Name
                                        <span v-if="sortBy === 'name'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('username')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Username
                                        <span v-if="sortBy === 'username'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200">
                                    <button @click="toggleSort('usertype')" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                        Role
                                        <span v-if="sortBy === 'usertype'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                    </button>
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="user in paginatedUsers"
                                :key="user.id"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                            >
                                <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ user.name }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ user.username }}
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ user.usertype }}
                                </td>
                                <td class="px-4 py-2 text-xs text-center">
                                    <div class="relative inline-block">
                                        <button 
                                            @click="toggleDropdown(user.id)" 
                                            class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                                        >
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div 
                                            v-if="activeDropdown === user.id" 
                                            class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-600 overflow-hidden"
                                        >
                                            <button 
                                                @click="handleEditUser(user); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 flex items-center gap-2"
                                            >
                                                <i class="fas fa-pencil-alt"></i>Edit
                                            </button>
                                            <button 
                                                @click="handleDeleteUser(user); activeDropdown = null" 
                                                class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150 border-t border-gray-200 dark:border-gray-600 flex items-center gap-2"
                                            >
                                                <i class="fas fa-trash-alt"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="!loading && users.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }}</span> of <span class="font-semibold">{{ filteredUsers.length }}</span> users
                    </div>
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
                                v-for="page in totalPages"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-2 py-1 text-xs rounded transition-colors',
                                    currentPage === page
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

        <!-- Create User Modal -->
        <Teleport to="body" v-if="showCreateModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeCreateModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-user-plus text-emerald-600 dark:text-emerald-400"></i>
                            Create User
                        </h3>
                        <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Employee Selection -->
                            <div class="space-y-2">
                                <label for="employee_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-id-card absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model.number="formData.employee_id"
                                        id="employee_id"
                                        @change="() => { const emp = employees.find(e => e.id === Number(formData.employee_id)); if (emp) { formData.name = emp.name; formData.office_id = emp.office.toString(); } }"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Employee</option>
                                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                            {{ emp.name }} ({{ emp.employee_id }})
                                        </option>
                                    </select>
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
                                    <i class="fas fa-envelope absolute left-3 text-gray-400 text-sm"></i>
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
                                <label for="office_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office (Auto-filled)</label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-building absolute left-3 text-gray-400 text-sm"></i>
                                    <select
                                        v-model.number="formData.office_id"
                                        id="office_id"
                                        class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 appearance-none"
                                    >
                                        <option value="">Select Office</option>
                                        <option v-for="office in offices" :key="office.id" :value="office.id">
                                            {{ office.office_name }}
                                        </option>
                                    </select>
                                </div>
                                <span v-if="formErrors.office_id" class="text-red-500 text-xs">{{ formErrors.office_id }}</span>
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
                                        <option value="Administrator">Administrator</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Supervisor">Supervisor</option>
                                        <option value="Reviewer">Reviewer</option>
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

                    <!-- Modal footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="handleCreateUser"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-save"></i>
                            Save
                        </button>
                        <button
                            @click="closeCreateModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit User Modal -->
        <Teleport to="body" v-if="showEditModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeEditModal">
                <div class="relative w-full max-w-4xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                            Edit User
                        </h3>
                        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
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
                                        disabled
                                    />
                                </div>
                            </div>

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
                                        <option value="Administrator">Administrator</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Supervisor">Supervisor</option>
                                        <option value="Reviewer">Reviewer</option>
                                    </select>
                                </div>
                                <span v-if="formErrors.usertype" class="text-red-500 text-xs">{{ formErrors.usertype }}</span>
                            </div>

                            <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="handleUpdateUser"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-save"></i>
                            Update
                        </button>
                        <button
                            @click="closeEditModal"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete User Modal -->
        <Teleport to="body" v-if="showDeleteModal && userToDelete">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="closeDeleteModal">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-red-50 to-red-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-trash-alt text-red-600 dark:text-red-400"></i>
                            Delete User
                        </h3>
                        <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    Are you sure you want to delete <span class="font-semibold">{{ userToDelete.username }}</span>?
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                                    This action cannot be undone. All associated data will be permanently deleted.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="confirmDeleteUser"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-trash-alt"></i>
                            Delete
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
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

interface User {
    id: number;
    name: string;
    username: string;
    usertype: string;
}

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    office: number;
    designation: string;
}

interface Office {
    id: number;
    office_name: string;
}

const users = ref<User[]>([]);
const employees = ref<Employee[]>([]);
const offices = ref<Office[]>([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const sortBy = ref<'id' | 'name' | 'username' | 'usertype'>('id');
const sortOrder = ref<'asc' | 'desc'>('asc');
const activeDropdown = ref<number | null>(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingUser = ref<User | null>(null);
const userToDelete = ref<User | null>(null);

const formData = ref({
    employee_id: '',
    name: '',
    username: '',
    usertype: '',
    office_id: '' as string | number,
    password: '',
    password_confirmation: ''
});

const formErrors = ref<Record<string, string>>({});

const filteredUsers = computed(() => {
    let result = users.value.filter((user) => {
        const query = searchQuery.value.toLowerCase();
        return (
            user.name.toLowerCase().includes(query) ||
            user.username.toLowerCase().includes(query) ||
            user.usertype.toLowerCase().includes(query)
        );
    });

    result.sort((a, b) => {
        let aVal: any = a[sortBy.value];
        let bVal: any = b[sortBy.value];

        if (aVal === null || aVal === undefined) aVal = '';
        if (bVal === null || bVal === undefined) bVal = '';

        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage.value));

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredUsers.value.slice(start, end);
});

onMounted(async () => {
    try {
        const [usersResponse, employeesResponse, officesResponse] = await Promise.all([
            fetch('/api/users', {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('token')}`,
                },
            }),
            fetch('/api/employees', {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('token')}`,
                },
            }),
            fetch('/api/offices', {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('token')}`,
                },
            })
        ]);
        
        if (!usersResponse.ok) throw new Error('Failed to fetch users');
        const userData = await usersResponse.json();
        users.value = userData.data || userData;
        
        if (!employeesResponse.ok) throw new Error('Failed to fetch employees');
        const employeeData = await employeesResponse.json();
        employees.value = employeeData.data || employeeData;
        
        if (!officesResponse.ok) throw new Error('Failed to fetch offices');
        const officeData = await officesResponse.json();
        offices.value = officeData.data || officeData;
    } catch (err: any) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
});

const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const toggleSort = (field: 'id' | 'name' | 'username' | 'usertype') => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'asc';
    }
};

const toggleDropdown = (userId: number) => {
    activeDropdown.value = activeDropdown.value === userId ? null : userId;
};

const handleEditUser = (user: User) => {
    editingUser.value = user;
    formData.value = {
        employee_id: '',
        name: user.name,
        username: user.username,
        usertype: user.usertype,
        office_id: '',
        password: '',
        password_confirmation: ''
    };
    formErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = null;
};

const handleUpdateUser = async () => {
    if (!editingUser.value) return;
    
    formErrors.value = {};
    
    if (!formData.value.username.trim()) {
        formErrors.value['username'] = 'Username is required';
    }
    
    if (!formData.value.usertype.trim()) {
        formErrors.value['usertype'] = 'Role is required';
    }
    
    if (Object.keys(formErrors.value).length > 0) return;
    
    try {
        const response = await fetch(`/api/users/${editingUser.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                Authorization: `Bearer ${localStorage.getItem('token')}`,
            },
            body: JSON.stringify({
                name: formData.value.name,
                username: formData.value.username,
                usertype: formData.value.usertype
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to update user');
        }
        
        const updatedUser = await response.json();
        const index = users.value.findIndex(u => u.id === editingUser.value!.id);
        if (index !== -1) {
            users.value[index] = updatedUser;
        }
        closeEditModal();
    } catch (e) {
        formErrors.value['submit'] = e instanceof Error ? e.message : 'An error occurred';
    }
};

const handleDeleteUser = (user: User) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    userToDelete.value = null;
};

const confirmDeleteUser = async () => {
    if (!userToDelete.value) return;
    
    try {
        const response = await fetch(`/api/users/${userToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                Authorization: `Bearer ${localStorage.getItem('token')}`,
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to delete user');
        }
        
        users.value = users.value.filter(u => u.id !== userToDelete.value!.id);
        closeDeleteModal();
    } catch (e) {
        alert(e instanceof Error ? e.message : 'An error occurred');
    }
};

const openCreateModal = () => {
    formData.value = {
        employee_id: '',
        name: '',
        username: '',
        usertype: '',
        office_id: '',
        password: '',
        password_confirmation: ''
    };
    formErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const validateForm = (): boolean => {
    formErrors.value = {};
    
    if (!formData.value.employee_id.toString().trim()) {
        formErrors.value['employee_id'] = 'Employee is required';
    }
    
    if (!formData.value.username.trim()) {
        formErrors.value['username'] = 'Username is required';
    }
    
    if (!formData.value.usertype.trim()) {
        formErrors.value['usertype'] = 'Role is required';
    }
    
    if (!formData.value.office_id.toString().trim()) {
        formErrors.value['office_id'] = 'Office is required';
    }
    
    if (!formData.value.password.trim()) {
        formErrors.value['password'] = 'Password is required';
    }
    
    if (!formData.value.password_confirmation.trim()) {
        formErrors.value['password_confirmation'] = 'Password confirmation is required';
    }
    
    if (formData.value.password !== formData.value.password_confirmation) {
        formErrors.value['password_confirmation'] = 'Passwords do not match';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

const handleCreateUser = async () => {
    if (!validateForm()) return;
    
    try {
        const response = await fetch('/api/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                Authorization: `Bearer ${localStorage.getItem('token')}`,
            },
            body: JSON.stringify(formData.value)
        });
        
        if (!response.ok) {
            throw new Error('Failed to create user');
        }
        
        const newUser = await response.json();
        users.value.push(newUser);
        closeCreateModal();
    } catch (e) {
        formErrors.value['submit'] = e instanceof Error ? e.message : 'An error occurred';
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
