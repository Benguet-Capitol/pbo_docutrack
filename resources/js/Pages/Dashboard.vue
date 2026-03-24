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

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- HR Summary Panel: Leaves, Travel Orders, Pass Slips for Current Month -->
            <div v-if="canViewHRSummary" class="w-full bg-white dark:bg-gray-800 rounded-lg shadow relative">
                <!-- Loading Animation Overlay -->
                <div v-if="hrLoading" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent dark:via-gray-600/30 rounded-lg hr-loading-shimmer"></div>
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                        <!-- Left side: Title and Filters -->
                        <div class="flex flex-col gap-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-users text-emerald-600 dark:text-emerald-400"></i>
                                Leaves, Travel Orders, Pass Slips and Tardiness/Undertime Summary
                            </h3>

                            <!-- Month and Year Filters -->
                            <div class="flex flex-col sm:flex-row gap-2 items-end">
                                <!-- Year Filter -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Year</label>
                                    <select
                                        v-model.number="selectedHRYear"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-2 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                    >
                                        <option v-for="year in availableHRYears" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Month Filter -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Month</label>
                                    <select
                                        v-model.number="selectedHRMonth"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-2 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer max-w-[120px]"
                                    >
                                        <option v-for="(monthName, index) in ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']" :key="index" :value="index">
                                            {{ monthName }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right side: Generate Report Button -->
                        <button
                            @click="showSummaryModal = true"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors text-xs font-medium whitespace-nowrap"
                        >
                            <i class="fas fa-file-pdf"></i>
                            Summary of Leaves, TOs, PS and Tardiness/Undertimes
                        </button>
                    </div>
                </div>

                <!-- Summary Cards Grid -->
                <div class="w-full p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 auto-rows-max">
                    <!-- Leaves Card -->
                    <div 
                        @click="toggleHRExpanded('leaves')"
                        class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                        :class="expandedHRType === 'leaves' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-blue-400'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400"></i>
                                Leaves
                            </h4>
                            <i :class="['fas', expandedHRType === 'leaves' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                        </div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                            {{ currentMonthLeaves.length }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">{{ Object.keys(currentMonthLeavesByType).length }}</span> types filed
                        </p>
                    </div>

                    <!-- Travel Orders Card -->
                    <div 
                        @click="toggleHRExpanded('travelOrders')"
                        class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                        :class="expandedHRType === 'travelOrders' ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-orange-400'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-map-marked-alt text-orange-600 dark:text-orange-400"></i>
                                Travel Orders
                            </h4>
                            <i :class="['fas', expandedHRType === 'travelOrders' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                        </div>
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400 mb-1">
                            {{ currentMonthTravelOrders.length }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">{{ uniqueEmployeesTravelOrders.size }}</span> employees involved
                        </p>
                    </div>

                    <!-- Pass Slips Card -->
                    <div 
                        @click="toggleHRExpanded('passSlips')"
                        class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                        :class="expandedHRType === 'passSlips' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-emerald-400'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-clipboard-list text-emerald-600 dark:text-emerald-400"></i>
                                Pass Slips
                            </h4>
                            <i :class="['fas', expandedHRType === 'passSlips' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                        </div>
                        <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">
                            {{ currentMonthPassSlips.length }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">{{ uniqueEmployeesPassSlips.size }}</span> employees involved
                        </p>
                    </div>

                    <!-- Tardiness/Undertime Card -->
                    <div 
                        @click="toggleHRExpanded('tardiness')"
                        class="rounded-lg border-2 p-4 cursor-pointer transition-all duration-200"
                        :class="expandedHRType === 'tardiness' ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-red-400'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-hourglass-end text-red-600 dark:text-red-400"></i>
                                Tardiness/Undertime
                            </h4>
                            <i :class="['fas', expandedHRType === 'tardiness' ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-400 text-xs']"></i>
                        </div>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-1">
                            {{ currentMonthTardiness.length }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">{{ uniqueEmployeesTardiness.size }}</span> employees involved
                        </p>
                    </div>
                </div>

                <!-- Expanded Details Section -->
                <div v-if="expandedHRType" class="border-t border-gray-200 dark:border-gray-700 p-6">
                    <!-- Leaves Details -->
                    <div v-if="expandedHRType === 'leaves'">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-blue-600"></i>
                            Leaves by Type
                        </h4>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <div v-for="(data, type) in currentMonthLeavesByTypeWithEmployees" :key="type" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ type }}</p>
                                    <span class="px-2 py-1 bg-blue-200 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">{{ data.count }}</span>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="(entry, idx) in data.entries" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ entry.name }}</p>
                                            <span v-if="entry.isHalfDay" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded text-xs font-medium">
                                                {{ entry.halfDayPeriod }}
                                            </span>
                                        </div>
                                        <p v-if="entry.dates" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                            <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                            {{ entry.dates }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Travel Orders Details -->
                    <div v-else-if="expandedHRType === 'travelOrders'">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marked-alt text-orange-600"></i>
                            Travel Orders by Employee
                        </h4>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="(orders, empName) in currentMonthTravelOrdersByEmp" :key="empName" class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                                <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                                <div class="space-y-2">
                                    <div v-for="(order, idx) in orders" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                                        <p class="text-gray-700 dark:text-gray-300 font-medium">{{ order.control_no }}</p>
                                        <p v-if="order.destination" class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-map-pin text-orange-500 mr-1"></i>
                                            {{ order.destination }}
                                        </p>
                                        <p v-if="formatDateRange(order.from_date, order.to_date)" class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-calendar text-orange-500 mr-1"></i>
                                            {{ formatDateRange(order.from_date, order.to_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pass Slips Details -->
                    <div v-else-if="expandedHRType === 'passSlips'">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-clipboard-list text-emerald-600"></i>
                            Pass Slips by Employee
                        </h4>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="(slips, empName) in currentMonthPassSlipsByEmp" :key="empName" class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                                <div class="space-y-2">
                                    <div v-for="(slip, idx) in slips" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                                        <p class="text-gray-700 dark:text-gray-300 font-medium">{{ slip.control_no }}</p>
                                        <p v-if="slip.date" class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-calendar text-emerald-500 mr-1"></i>
                                            {{ new Date(slip.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                        </p>
                                        <p v-if="slip.requested_time" class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-clock text-emerald-500 mr-1"></i>
                                            Leave: {{ formatTime(slip.requested_time) }}
                                        </p>
                                        <p v-if="slip.expected_return_time" class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-hourglass-end text-emerald-500 mr-1"></i>
                                            Return: {{ formatTime(slip.expected_return_time) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tardiness/Undertime Details -->
                    <div v-else-if="expandedHRType === 'tardiness'">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-hourglass-end text-red-600"></i>
                            Tardiness/Undertime by Employee
                        </h4>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="(records, empName) in currentMonthTardinesssByEmp" :key="empName" class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="font-medium text-gray-900 dark:text-white mb-2">{{ empName }}</p>
                                <div class="space-y-2">
                                    <div v-for="(record, idx) in records" :key="idx" class="bg-white dark:bg-gray-700 p-2 rounded border border-gray-200 dark:border-gray-600 text-xs">
                                        <p class="text-gray-700 dark:text-gray-300 font-medium">{{ record.control_no }}</p>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-calendar text-red-500 mr-1"></i>
                                            Date: {{ new Date(record.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-flag text-red-500 mr-1"></i>
                                            Type: <span class="font-medium">{{ record.type }}</span>
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-clock text-red-500 mr-1"></i>
                                            Time: {{ formatTime(record.requested_time) }}
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                                            <i class="fas fa-note-sticky text-gray-400 mr-1"></i>
                                            {{ record.reason }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Leaves by Type Summary (Yearly) -->
                <div v-if="employeeLeavesSummary.length > 0" class="border-t border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-users text-blue-600"></i>
                        Employee Leaves Summary for {{ selectedHRYear }}
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Employee</th>
                                    <th v-for="leaveType in uniqueLeaveTypes" :key="leaveType" class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200 text-xs">
                                        {{ leaveType }}
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="empSummary in employeeLeavesSummary" :key="empSummary.employeeId" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ empSummary.employeeName }}
                                    </td>
                                    <td v-for="leaveType in uniqueLeaveTypes" :key="`${empSummary.employeeId}-${leaveType}`" class="px-4 py-3 text-center">
                                        <span v-if="empSummary.leaveTypes[leaveType]" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-semibold">
                                            {{ empSummary.leaveTypes[leaveType] }}
                                        </span>
                                        <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center justify-center px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded text-xs font-medium">
                                                {{ empSummary.totalLeaves }}
                                            </span>
                                            <div v-if="getOtherLeaveTypes(empSummary).length > 0" class="text-xs text-gray-600 dark:text-gray-400 mt-2 border-t border-gray-300 dark:border-gray-600 pt-2">
                                                <div v-for="(count, leaveType) in getOtherLeaveTypesMap(empSummary)" :key="leaveType" class="flex justify-between">
                                                    <span>{{ leaveType }}:</span>
                                                    <span class="font-semibold">{{ count }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Documents Processing Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-8">
                    <!-- Header Section: Contains search bar and items-per-page selector -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-clipboard-list text-teal-600 dark:text-teal-400 mr-2"></i>
                                Document Tracking Summary
                            </h3>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-search text-gray-400"></i>
                                <!-- Search Input: v-model binds to searchQuery, triggers filter recomputation -->
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search documents..."
                                    class="border border-gray-300 rounded-lg px-4 py-2 text-xs flex-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-80"
                                />
                                <!-- Items Per Page Selector: Controls number of items displayed per page -->
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

                        <!-- Filter Section -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700 items-end justify-between">
                            <!-- Left side: Filters and Reset -->
                            <div class="flex flex-col sm:flex-row gap-3 items-end">
                                <!-- Year Filter -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Year</label>
                                    <select
                                        v-model.number="selectedYear"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                    >
                                        <option :value="null">All Years</option>
                                        <option v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Semester Filter -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Semester</label>
                                    <select
                                        v-model.number="selectedSemester"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                    >
                                        <option :value="null">All Semesters</option>
                                        <option :value="1">1st Semester (Jan - Jun)</option>
                                        <option :value="2">2nd Semester (Jul - Dec)</option>
                                    </select>
                                </div>

                                <!-- User Filter -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">User</label>
                                    <select
                                        v-model.number="selectedUser"
                                        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-xs text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500 dark:focus:border-emerald-500 transition-colors cursor-pointer"
                                    >
                                        <option :value="null">All Users</option>
                                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Reset Button -->
                                <button
                                    @click="selectedYear = null; selectedSemester = null; selectedUser = null"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-xs font-medium"
                                >
                                    <i class="fas fa-times"></i>
                                    Reset
                                </button>
                            </div>

                            <!-- Right side: Report Buttons -->
                            <div class="flex gap-2 flex-col sm:flex-row">
                                <button
                                    @click="showReportModal = true"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors text-xs font-medium"
                                >
                                    <i class="fas fa-file-pdf"></i>
                                    Budget Proposal Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        <button @click="sortBy = 'tracking_no'; sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                            Tracking No
                                            <span v-if="sortBy === 'tracking_no'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        <button @click="sortBy = 'date'; sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'" class="flex items-center gap-2 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                            Date
                                            <span v-if="sortBy === 'date'" class="text-xs">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Document Type
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Source
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Particulars
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                        Custodian
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Processing Time
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Remaining Duration
                                    </th>
                                    <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                        Transactions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template v-if="paginatedDocuments.length > 0">
                                    <template v-for="document in paginatedDocuments" :key="document.id">
                                        <tr @click="toggleExpanded(document.id)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentId === document.id ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                            <td class="px-4 py-2 text-xs font-medium text-gray-900 dark:text-gray-100">
                                                {{ document.tracking_no }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                                {{ new Date(document.date).toLocaleDateString() }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.document_type">{{ document.document_type }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.source">{{ document.source }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span v-if="document.particulars" :title="document.particulars">{{ document.particulars }}</span>
                                                <span v-else class="text-gray-400 dark:text-gray-600 italic">-</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                                {{ getCustodianName(document) }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center">
                                                <span v-if="document.status === 'created'" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">Created</span>
                                                <span v-else-if="document.status === 'forwarded'" class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded-full text-xs font-medium">Forwarded</span>
                                                <span v-else-if="document.status === 'pending'" class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-medium">Pending</span>
                                                <span v-else-if="document.status === 'finalized'" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-medium">Transaction Ended</span>
                                                <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-full text-xs font-medium">{{ document.status }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center text-gray-600 dark:text-gray-400 font-medium">
                                                {{ calculateProcessingTime(document) }}
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center font-medium">
                                                <div :class="getTimeLeftStyles(document)">
                                                    {{ getTimeLeftText(document) }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-xs text-center">
                                                <button
                                                    @click.stop="toggleExpanded(document.id)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors text-xs font-medium"
                                                >
                                                    <i :class="['fas', expandedDocumentId === document.id ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                    {{ document.transactions?.length || 0 }}
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Expanded Transactions Row -->
                                        <tr v-if="expandedDocumentId === document.id" :key="`transactions-${document.id}`" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="10" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
                                                    Transaction History
                                                </h4>
                                                
                                                <!-- No Transactions State -->
                                                <div v-if="document.transactions.length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No transactions found</p>
                                                </div>

                                                <!-- Transactions Timeline (Grid) -->
                                                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-4">
                                                    <div v-for="(transaction, index) in [...document.transactions].reverse()" :key="transaction.id" class="relative">
                                                        <!-- Timeline Dot -->
                                                        <div class="absolute -top-3 left-3 z-10">
                                                            <div v-if="getActionType(transaction.action) === 'created'" class="w-5 h-5 rounded-full bg-green-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-check text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'forwarded'" class="w-5 h-5 rounded-full bg-cyan-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-arrow-right text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'received'" class="w-5 h-5 rounded-full bg-orange-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-inbox text-white text-xs"></i>
                                                            </div>
                                                            <div v-else-if="getActionType(transaction.action) === 'finalized'" class="w-5 h-5 rounded-full bg-purple-500 ring-4 ring-white dark:ring-gray-700 flex items-center justify-center">
                                                                <i class="fas fa-check-double text-white text-xs"></i>
                                                            </div>
                                                        </div>

                                                        <!-- Transaction Card -->
                                                        <div class="pt-6 p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm h-full">
                                                            <!-- Action Badge -->
                                                            <div class="mb-2">
                                                                <span v-if="getActionType(transaction.action) === 'created'" class="inline-block px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Created</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'forwarded'" class="inline-block px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'received'" class="inline-block px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded text-xs font-medium">Received</span>
                                                                <span v-else-if="getActionType(transaction.action) === 'finalized'" class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium">Transaction Ended</span>
                                                            </div>

                                                            <!-- Date/Time -->
                                                            <div class="flex gap-3 mb-2">
                                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-calendar text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                    {{ new Date(transaction.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                                                </p>
                                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-clock text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                    {{ new Date(transaction.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}
                                                                </p>
                                                            </div>

                                                            <!-- User and Duration -->
                                                            <div class="flex gap-2 mb-2 items-start">
                                                                <p class="text-xs text-gray-700 dark:text-gray-300 break-words font-semibold flex-1">
                                                                    {{ transaction.user?.name || 'Unknown User' }}
                                                                </p>
                                                                <p v-if="transaction.duration_hours !== null && transaction.duration_hours !== undefined" class="text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">
                                                                    <i class="fas fa-hourglass-end mr-1"></i>
                                                                    {{ formatDuration(transaction.duration_hours) }}
                                                                </p>
                                                            </div>

                                                            <!-- Action -->
                                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                                                <i class="fas fa-tasks text-gray-400 dark:text-gray-500 mr-1"></i>
                                                                {{ transaction.action }}
                                                            </p>

                                                            <!-- Recipient (if forwarded) -->
                                                            <p v-if="getActionType(transaction.action) === 'forwarded' && (transaction.forwardedToUser || transaction.forwardedToOffice || transaction.forwardedToMunicipality)" class="text-xs text-gray-600 dark:text-gray-400 mb-2 break-words">
                                                                <i class="fas fa-arrow-right text-cyan-500 mr-1"></i>
                                                                <span v-if="transaction.forwardedToUser">{{ transaction.forwardedToUser.name }}</span>
                                                                <span v-else-if="transaction.forwardedToOffice">{{ transaction.forwardedToOffice.office_name }}</span>
                                                                <span v-else-if="transaction.forwardedToMunicipality">{{ transaction.forwardedToMunicipality.name }}</span>
                                                            </p>

                                                            <!-- Remarks -->
                                                            <p v-if="transaction.remarks" class="text-xs text-gray-600 dark:text-gray-400 italic border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                                                                {{ transaction.remarks }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </template>
                                </template>
                                <tr v-else>
                                    <td :colspan="10" class="px-6 py-8 text-center">
                                        <div v-if="loading" class="text-gray-500 dark:text-gray-400 space-y-2">
                                            <i class="fas fa-spinner fa-spin text-2xl opacity-30"></i>
                                            <p>Loading documents...</p>
                                        </div>
                                        <div v-else-if="error" class="text-red-600 dark:text-red-400">
                                            <i class="fas fa-exclamation-circle text-2xl opacity-30 block mb-2"></i>
                                            <p>{{ error }}</p>
                                        </div>
                                        <div v-else class="text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                            <p>No documents found</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div v-if="!loading && documents.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Pagination Info -->
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredDocuments.length) }}</span> of <span class="font-semibold">{{ filteredDocuments.length }}</span> documents
                        </div>
                        <!-- Pagination Buttons -->
                        <div class="flex items-center gap-1">
                            <!-- First Page Button -->
                            <button
                                @click="changePage(1)"
                                :disabled="currentPage === 1"
                                class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <!-- Previous Page Button -->
                            <button
                                @click="changePage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Prev
                            </button>
                            <!-- Page Number Buttons -->
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
                            <!-- Next Page Button -->
                            <button
                                @click="changePage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                            <!-- Last Page Button -->
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

            <!-- Statistics Panels Grid: Side-by-side layout for Users and Documents statistics -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Users Summary Section: Average Duration per Transaction -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <!-- Header Section -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                            Summary of Users Average Time per Transaction
                        </h3>
                    </div>

                <!-- User Statistics Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                    User Name
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Total Transactions
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Average Duration
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="filteredUserStatistics.length > 0">
                                <template v-for="(user, userIndex) in filteredUserStatistics" :key="`user-${userIndex}`">
                                    <tr 
                                        @click="toggleUserExpanded(user.userId)"
                                        :class="['transition-colors duration-150 cursor-pointer', expandedUserId === user.userId ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']"
                                    >
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <i :class="['fas', expandedUserId === user.userId ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                            {{ user.name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex items-center justify-center px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                                {{ user.count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center font-medium">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                                <i class="fas fa-hourglass-end"></i>
                                                {{ formatHours(user.averageHours) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <!-- Expanded User Details Row -->
                                    <tr v-if="expandedUserId === user.userId" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="3" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                                                    Detailed Breakdown
                                                </h4>
                                                
                                                <!-- No Details State -->
                                                <div v-if="selectedUserDetails.length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No document details found</p>
                                                </div>

                                                <!-- Details Table -->
                                                <div v-else class="overflow-x-auto">
                                                    <table class="w-full text-xs">
                                                        <thead class="bg-gray-200 dark:bg-gray-600">
                                                            <tr>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Document</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Received</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Completed</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Pending Time</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                            <tr v-for="detail in selectedUserDetails" :key="`detail-${detail.documentId}`" class="bg-white dark:bg-gray-800">
                                                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                                    {{ detail.trackingNo }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate" :title="detail.particulars">
                                                                    {{ detail.particulars }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ detail.startDate }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ detail.endDate }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center">
                                                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium whitespace-nowrap">
                                                                        <i class="fas fa-hourglass-end"></i>
                                                                        {{ formatHours(detail.pendingHours) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td :colspan="3" class="px-6 py-8 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                        <p class="text-sm">No user data available</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents Processing Time Section: Average Duration per document type -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-alt text-purple-600 dark:text-purple-400"></i>
                        Average Processing Time for Documents
                    </h3>
                </div>

                <!-- Document Statistics Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-left">
                                    Document Type
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Total Documents
                                </th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-700 dark:text-gray-200 text-center">
                                    Average Duration
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="documentProcessingStatistics.length > 0">
                                <template v-for="docStat in documentProcessingStatistics" :key="`doc-${docStat.documentType}`">
                                    <tr @click="toggleDocumentTypeExpanded(docStat.documentType)" :class="['transition-colors duration-150 cursor-pointer', expandedDocumentType === docStat.documentType ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <i :class="['fas', expandedDocumentType === docStat.documentType ? 'fa-chevron-down' : 'fa-chevron-right', 'text-gray-400 text-xs']"></i>
                                            {{ docStat.documentType }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                            <span class="inline-flex items-center justify-center px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-xs font-medium">
                                                {{ docStat.count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center font-medium">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 rounded-full text-xs font-medium">
                                                <i class="fas fa-stopwatch"></i>
                                                {{ formatHours(docStat.averageHours) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <!-- Expanded Document Type Details Row -->
                                    <tr v-if="expandedDocumentType === docStat.documentType" class="bg-gray-50 dark:bg-gray-700/50">
                                        <td :colspan="3" class="px-6 py-6">
                                            <div class="space-y-4">
                                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                    <i class="fas fa-list text-purple-600 dark:text-purple-400"></i>
                                                    Documents of Type: {{ docStat.documentType }}
                                                </h4>
                                                
                                                <!-- No Documents State -->
                                                <div v-if="getDocumentsForType(docStat.documentType).length === 0" class="py-6 text-center">
                                                    <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-2xl mb-2 block"></i>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400">No documents found</p>
                                                </div>

                                                <!-- Documents Table -->
                                                <div v-else class="overflow-x-auto">
                                                    <table class="w-full text-xs">
                                                        <thead class="bg-gray-200 dark:bg-gray-600">
                                                            <tr>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Tracking No</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Particulars</th>
                                                                <th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Processing Time</th>
                                                                <th class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">Remaining Duration</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                            <tr v-for="doc in getDocumentsForType(docStat.documentType)" :key="`doc-detail-${doc.id}`" class="bg-white dark:bg-gray-800">
                                                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                                    {{ doc.tracking_no }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate" :title="doc.particulars || '-'">
                                                                    {{ doc.particulars || '-' }}
                                                                </td>
                                                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                                                    <span v-if="doc.status === 'created'" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">Created</span>
                                                                    <span v-else-if="doc.status === 'forwarded'" class="px-2 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300 rounded text-xs font-medium">Forwarded</span>
                                                                    <span v-else-if="doc.status === 'pending'" class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded text-xs font-medium">Pending</span>
                                                                    <span v-else-if="doc.status === 'finalized'" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">Ended</span>
                                                                    <span v-else class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded text-xs font-medium">{{ doc.status }}</span>
                                                                </td>
                                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">
                                                                    {{ calculateProcessingTime(doc) }}
                                                                </td>
                                                                <td class="px-4 py-2 text-center">
                                                                    <div :class="getTimeLeftStyles(doc)">
                                                                        {{ getTimeLeftText(doc) }}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <tr v-else>
                                <td :colspan="3" class="px-6 py-8 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                        <p class="text-sm">No document data available</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

        <!-- Report Generation Modal -->
        <Teleport to="body" v-if="showReportModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showReportModal = false">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-file-pdf text-blue-600 dark:text-blue-400"></i>
                            Generate Report
                        </h3>
                        <button @click="showReportModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-4">
                        <!-- As of Date -->
                        <div class="space-y-2">
                            <label for="report_as_of_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                            <input
                                v-model="reportData.asOfDate"
                                id="report_as_of_date"
                                type="date"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <!-- Reviewed By -->
                        <div class="space-y-2">
                            <label for="report_reviewed_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Reviewed By</label>
                            <select
                                v-model.number="reportData.reviewedBy"
                                id="report_reviewed_by"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            >
                                <option :value="null">Select Supervisor</option>
                                <option v-for="user in supervisorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Certified Correct -->
                        <div class="space-y-2">
                            <label for="report_certified_correct" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Certified Correct</label>
                            <select
                                v-model.number="reportData.certifiedCorrect"
                                id="report_certified_correct"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-blue-500"
                            >
                                <option :value="null">Select Administrator</option>
                                <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="reportErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-800 dark:text-red-300 text-xs">{{ reportErrors.submit }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="generateReport"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Generate
                        </button>
                        <button
                            @click="showReportModal = false"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Summary Report Modal -->
        <Teleport to="body" v-if="showSummaryModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showSummaryModal = false">
                <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-chart-bar text-emerald-600 dark:text-emerald-400"></i>
                            Summary Report
                        </h3>
                        <button @click="showSummaryModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-4">
                        <!-- Month Selection -->
                        <div class="space-y-2">
                            <label for="summary_month" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Month</label>
                            <select
                                v-model.number="summaryData.month"
                                id="summary_month"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option v-for="(monthName, index) in months" :key="index" :value="index + 1">{{ monthName }}</option>
                            </select>
                        </div>

                        <!-- Permanent or Casual Selection -->
                        <div class="space-y-2">
                            <label for="summary_employment_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employment Type</label>
                            <select
                                v-model="summaryData.employmentType"
                                id="summary_employment_type"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Select Type</option>
                                <option value="permanent">Permanent</option>
                                <option value="casual">Casual</option>
                            </select>
                        </div>

                        <!-- Casual Period Selection (visible only for casual employment type) -->
                        <div v-if="summaryData.employmentType === 'casual'" class="space-y-2">
                            <label for="summary_casual_period" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Report Period</label>
                            <select
                                v-model="summaryData.casualPeriod"
                                id="summary_casual_period"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Select Period</option>
                                <option value="1-15">As of 1-15</option>
                                <option value="16-last">As of 16-{{ lastDayOfSelectedMonth }}</option>
                            </select>
                        </div>

                        <!-- Prepared By -->
                        <div class="space-y-2">
                            <label for="summary_prepared_by" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Prepared By</label>
                            <select
                                v-model.number="summaryData.preparedBy"
                                id="summary_prepared_by"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option :value="null">Select Prepared By</option>
                                <option v-for="emp in administrativeStaffEmployees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Certified Correct -->
                        <div class="space-y-2">
                            <label for="summary_certified_correct" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Certified Correct</label>
                            <select
                                v-model.number="summaryData.certifiedCorrect"
                                id="summary_certified_correct"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option :value="null">Select Certified Correct</option>
                                <option v-for="user in administratorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Remarks -->
                        <div class="space-y-2">
                            <label for="summary_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                            <textarea
                                v-model="summaryData.remarks"
                                id="summary_remarks"
                                placeholder="Add any notes or remarks for the report"
                                rows="3"
                                class="w-full px-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                            ></textarea>
                        </div>

                        <div v-if="summaryErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-800 dark:text-red-300 text-xs">{{ summaryErrors.submit }}</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button
                            @click="generateSummaryReport"
                            class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        >
                            <i class="fas fa-check"></i>
                            Generate
                        </button>
                        <button
                            @click="showSummaryModal = false"
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
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';

interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id: number | null;
    forwarded_to_office_id: number | null;
    forwarded_to_municipality_id: number | null;
    action: string;
    remarks: string;
    created_at: string;
    duration_hours: number;
    user?: { id: number; name: string; email: string } | null;
    forwardedToUser?: { id: number; name: string; email: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
}

interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string;
    source: string;
    status: string;
    remarks: string;
    user_id: number;
    user?: { id: number; name: string; email: string } | null;
    transactions: DocumentTransaction[];
}

// ============== State ==============
const documents = ref<Document[]>([]);
const searchQuery = ref('');
const sortBy = ref('id');
const sortOrder = ref<'asc' | 'desc'>('desc');
const itemsPerPage = ref(10);
const currentPage = ref(1);
const expandedDocumentId = ref<number | null>(null);
const expandedUserId = ref<number | null>(null);
const expandedDocumentType = ref<string | null>(null);
const expandedHRType = ref<'leaves' | 'travelOrders' | 'passSlips' | 'tardiness' | null>(null);
const selectedHRMonth = ref<number>(new Date().getMonth()); // 0-11
const selectedHRYear = ref<number>(new Date().getFullYear());
const loading = ref(true);
const hrLoading = ref(true);
const error = ref('');
const selectedYear = ref<number | null>(new Date().getFullYear());
const selectedSemester = ref<number | null>(null);
const selectedUser = ref<number | null>(null);

/** Reactive current time for real-time updates of Processing Time and Remaining Duration */
const currentTime = ref(new Date());

/** Stores the interval ID for cleanup on component unmount */
let timeUpdateInterval: NodeJS.Timeout | null = null;

// ============== Report Generation ==============
const showReportModal = ref(false);
const reportData = ref({
    asOfDate: new Date().toISOString().split('T')[0],
    reviewedBy: null as number | null,
    certifiedCorrect: null as number | null,
});
const reportErrors = ref<Record<string, string>>({});

// ============== Summary Report Generation ==============
const showSummaryModal = ref(false);
const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const summaryData = ref({
    month: null as number | null,
    employmentType: '',
    casualPeriod: '' as string,
    preparedBy: null as number | null,
    certifiedCorrect: null as number | null,
    remarks: '' as string,
});
const summaryErrors = ref<Record<string, string>>({});

// ============== Office/Municipality/User Lists ==============
const offices = ref<Array<{id: number; office_name: string}>>([]);
const municipalities = ref<Array<{id: number; name: string}>>([]);
const users = ref<Array<{id: number; name: string; usertype?: string}>>([]);
const employees = ref<Array<{id: number; employee_id: string; name: string; office_id: number; designation: string}>>([]);
const leaves = ref<any[]>([]);
const travelOrders = ref<any[]>([]);
const passSlips = ref<any[]>([]);
const tardiness = ref<any[]>([]);

// ============== User and Permissions ==============
const page = usePage();
const canViewHRSummary = computed(() => {
    const usertype = page.props.auth?.user?.usertype || '';
    return ['Developer', 'Administrator', 'Administrative'].includes(usertype);
});

// ============== Fetch Documents ==============
const fetchDocuments = async () => {
    try {
        loading.value = true;
        error.value = '';
        const response = await fetch('/api/documents');
        if (!response.ok) {
            throw new Error('Failed to fetch documents');
        }
        documents.value = await response.json();
        
        // Fetch offices and municipalities
        const officesResponse = await fetch('/api/offices');
        if (officesResponse.ok) {
            offices.value = await officesResponse.json();
        }
        
        const municipalitiesResponse = await fetch('/api/municipalities');
        if (municipalitiesResponse.ok) {
            municipalities.value = await municipalitiesResponse.json();
        }
        
        // Fetch users for lookup
        const usersResponse = await fetch('/api/users');
        if (usersResponse.ok) {
            users.value = await usersResponse.json();
        }
        
        // Fetch employees for designations
        const employeesResponse = await fetch('/api/employees');
        if (employeesResponse.ok) {
            employees.value = await employeesResponse.json();
        }
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'An error occurred while fetching documents';
        console.error('Error fetching documents:', error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Fetch leaves from API
 */
const fetchLeaves = async () => {
    try {
        const response = await fetch('/api/leaves');
        if (response.ok) {
            leaves.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching leaves:', e);
    }
};

/**
 * Fetch travel orders from API
 */
const fetchTravelOrders = async () => {
    try {
        const response = await fetch('/api/travel-orders');
        if (response.ok) {
            travelOrders.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching travel orders:', e);
    }
};

/**
 * Fetch pass slips from API
 */
const fetchPassSlips = async () => {
    try {
        const response = await fetch('/api/pass-slips');
        if (response.ok) {
            passSlips.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching pass slips:', e);
    }
};

/**
 * Fetch tardiness/undertime from API
 */
const fetchTardiness = async () => {
    try {
        const response = await fetch('/api/tardiness');
        if (response.ok) {
            const result = await response.json();
            tardiness.value = result.data || result;
        }
    } catch (e) {
        console.error('Error fetching tardiness:', e);
    }
};

// ============== Computed Properties ==============

/**
 * Extract unique years from documents
 */
const availableYears = computed(() => {
    const years = new Set<number>();
    documents.value.forEach(doc => {
        const year = new Date(doc.date).getFullYear();
        years.add(year);
    });
    return Array.from(years).sort((a, b) => b - a);
});

/**
 * Extract users who have transactions
 */
const availableUsers = computed(() => {
    const usersMap = new Map<number, string>();
    documents.value.forEach(doc => {
        doc.transactions?.forEach(transaction => {
            if (transaction.user && !usersMap.has(transaction.user.id)) {
                usersMap.set(transaction.user.id, transaction.user.name);
            }
        });
    });
    return Array.from(usersMap, ([id, name]) => ({ id, name }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

/**
 * Get semester from date (1 = Jan-Jun, 2 = Jul-Dec)
 */
const getSemester = (date: string | Date): number => {
    const d = new Date(date);
    const month = d.getMonth() + 1;
    return month <= 6 ? 1 : 2;
};

/**
 * Check if document falls within selected year and semester
 */
const matchesYearAndSemester = (date: string): boolean => {
    if (!selectedYear.value && !selectedSemester.value) {
        return true;
    }
    
    const docDate = new Date(date);
    const docYear = docDate.getFullYear();
    const docSemester = getSemester(date);
    
    if (selectedYear.value && docYear !== selectedYear.value) {
        return false;
    }
    if (selectedSemester.value && docSemester !== selectedSemester.value) {
        return false;
    }
    
    return true;
};

const filteredDocuments = computed(() => {
    let filtered = documents.value.filter(document => {
        // Apply search query filter
        const matchesSearch = document.tracking_no.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.date.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.document_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.source?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            document.particulars?.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        if (!matchesSearch) return false;
        
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return false;
        
        // Apply user filter (filter documents where the selected user has transactions)
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return false;
        }
        
        return true;
    });

    // Sort
    filtered.sort((a, b) => {
        let aValue = a[sortBy.value as keyof Document];
        let bValue = b[sortBy.value as keyof Document];

        if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
        }
        if (typeof bValue === 'string') {
            bValue = bValue.toLowerCase();
        }

        if (aValue < bValue) return sortOrder.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return filtered;
});

const paginatedDocuments = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredDocuments.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredDocuments.value.length / itemsPerPage.value);
});

/**
 * Filter users by role: Supervisor
 */
const supervisorUsers = computed(() => {
    return users.value.filter(user => user.usertype === 'Supervisor');
});

/**
 * Filter users by role: Administrator
 */
const administratorUsers = computed(() => {
    return users.value.filter(user => user.usertype === 'Administrator');
});

/**
 * Get employees with Administrative role
 */
const administrativeStaffEmployees = computed(() => {
    const adminUsers = users.value.filter(user => user.usertype === 'Administrative');
    return adminUsers.map(user => {
        const emp = employees.value.find(e => e.name === user.name);
        return emp || { id: user.id, name: user.name, designation: '' };
    });
});

/**
 * Filter employees excluding those with Administrator role
 */
const nonAdminEmployees = computed(() => {
    return employees.value.filter(emp => {
        const user = users.value.find(u => u.id === emp.id);
        return !user || user.usertype !== 'Administrator';
    });
});

/**
 * Get the last day of the selected month for casual period display
 */
const lastDayOfSelectedMonth = computed(() => {
    if (!summaryData.value.month) return 28; // Default to 28
    const currentYear = new Date().getFullYear();
    return new Date(currentYear, summaryData.value.month, 0).getDate();
});

/**
 * Check if a leave falls within the selected month based on inclusive_dates
 * For date ranges spanning multiple months, include in all overlapping months
 */
const leaveInSelectedMonth = (leave: any): boolean => {
    if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;
    
    const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
    const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);
    
    for (const dateEntry of leave.inclusive_dates) {
        if (!dateEntry) continue;
        
        // Handle date ranges like "2026-01-29 - 2026-02-04"
        if (dateEntry.includes(' - ')) {
            const [startStr, endStr] = dateEntry.split(' - ');
            const rangeStart = new Date(startStr.trim());
            const rangeEnd = new Date(endStr.trim());
            
            // Check if the date range overlaps with the selected month
            if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                return true;
            }
        } else {
            // Handle single dates
            const date = new Date(dateEntry.trim());
            if (date.getMonth() === selectedHRMonth.value && date.getFullYear() === selectedHRYear.value) {
                return true;
            }
        }
    }
    return false;
};

/**
 * Get leaves for current month based on inclusive_dates
 */
const currentMonthLeaves = computed(() => {
    return leaves.value.filter(leave => leaveInSelectedMonth(leave));
});

/**
 * Get available years from HR data based on actual dates of activity
 */
const availableHRYears = computed(() => {
    const yearsSet = new Set<number>();
    
    // Get years from leaves based on inclusive_dates
    leaves.value.forEach(leave => {
        if (leave.inclusive_dates && Array.isArray(leave.inclusive_dates)) {
            leave.inclusive_dates.forEach((dateEntry: string) => {
                if (dateEntry) {
                    const dateStr = dateEntry.includes(' - ') ? dateEntry.split(' - ')[0] : dateEntry;
                    const year = new Date(dateStr.trim()).getFullYear();
                    yearsSet.add(year);
                }
            });
        }
    });
    
    // Get years from travel orders based on from_date and to_date
    travelOrders.value.forEach(to => {
        if (to.from_date) {
            const year = new Date(to.from_date).getFullYear();
            yearsSet.add(year);
        }
        if (to.to_date) {
            const year = new Date(to.to_date).getFullYear();
            yearsSet.add(year);
        }
    });
    
    // Get years from pass slips
    passSlips.value.forEach(ps => {
        const year = new Date(ps.date).getFullYear();
        yearsSet.add(year);
    });
    
    // Get years from tardiness/undertime based on date_filed
    tardiness.value.forEach(tu => {
        if (tu.date_filed) {
            const year = new Date(tu.date_filed).getFullYear();
            yearsSet.add(year);
        }
    });
    
    return Array.from(yearsSet).sort((a, b) => a - b);
});

/**
 * Get leaves grouped by type for current month
 */
const currentMonthLeavesByType = computed(() => {
    const grouped: Record<string, number> = {};
    currentMonthLeaves.value.forEach(leave => {
        const type = leave.type_of_leave;
        grouped[type] = (grouped[type] || 0) + 1;
    });
    return grouped;
});

/**
 * Get leaves grouped by type with employees and dates for current month
 */
const currentMonthLeavesByTypeWithEmployees = computed(() => {
    const grouped: Record<string, { count: number; entries: Array<{ name: string; dates: string; isHalfDay: boolean; halfDayPeriod: string | null }> }> = {};
    currentMonthLeaves.value.forEach(leave => {
        const type = leave.type_of_leave;
        const empName = leave.employee?.name || 'Unknown Employee';
        
        // Format inclusive dates and convert to string if it's an array
        let datesStr = formatInclusiveDates(leave.inclusive_dates);
        
        // If it's a single date array, leave as is. If it's a range array, keep as string format
        if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 2) {
            // Store as string format "YYYY-MM-DD - YYYY-MM-DD"
            datesStr = `${leave.inclusive_dates[0]} - ${leave.inclusive_dates[1]}`;
            // Format for display
            datesStr = formatInclusiveDates(datesStr);
        } else if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length === 1) {
            datesStr = leave.inclusive_dates[0];
            datesStr = formatInclusiveDates(datesStr);
        }
        
        if (!grouped[type]) {
            grouped[type] = { count: 0, entries: [] };
        }
        grouped[type].count += 1;
        grouped[type].entries.push({ 
            name: empName, 
            dates: datesStr,
            isHalfDay: leave.is_half_day || false,
            halfDayPeriod: leave.half_day_period || null
        });
    });
    
    // Sort entries alphabetically by employee name within each type
    Object.keys(grouped).forEach(type => {
        grouped[type].entries.sort((a, b) => a.name.localeCompare(b.name));
    });
    return grouped;
});

/**
 * Check if a travel order falls within the selected month based on from_date and to_date
 */
const travelOrderInSelectedMonth = (to: any): boolean => {
    if (!to.from_date || !to.to_date) return false;
    
    const fromDate = new Date(to.from_date);
    const toDate = new Date(to.to_date);
    // selectedHRMonth is 0-indexed (0 = January, 1 = February, etc.)
    const monthStart = new Date(selectedHRYear.value, selectedHRMonth.value, 1);
    const monthEnd = new Date(selectedHRYear.value, selectedHRMonth.value + 1, 0);
    
    // Check if the travel order date range overlaps with the selected month
    return fromDate <= monthEnd && toDate >= monthStart;
};

/**
 * Get travel orders for current month based on from_date and to_date
 */
const currentMonthTravelOrders = computed(() => {
    return travelOrders.value.filter(to => travelOrderInSelectedMonth(to));
});

/**
 * Get unique employees from travel orders
 */
const uniqueEmployeesTravelOrders = computed(() => {
    const empSet = new Set<string>();
    currentMonthTravelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach(emp => {
                empSet.add(emp.name);
            });
        }
    });
    return empSet;
});

/**
 * Get travel orders grouped by employee
 */
const currentMonthTravelOrdersByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthTravelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach(emp => {
                if (!grouped[emp.name]) {
                    grouped[emp.name] = [];
                }
                grouped[emp.name].push(to);
            });
        }
    });
    return grouped;
});

/**
 * Get pass slips for current month
 */
const currentMonthPassSlips = computed(() => {
    return passSlips.value.filter(ps => {
        const psDate = new Date(ps.date);
        return psDate.getMonth() === selectedHRMonth.value && psDate.getFullYear() === selectedHRYear.value;
    });
});

/**
 * Get unique employees from pass slips
 */
const uniqueEmployeesPassSlips = computed(() => {
    const empSet = new Set<string>();
    currentMonthPassSlips.value.forEach(ps => {
        if (ps.employees && Array.isArray(ps.employees)) {
            ps.employees.forEach(emp => {
                empSet.add(emp.name);
            });
        }
    });
    return empSet;
});

/**
 * Check if a tardiness/undertime record falls within the selected month based on date_filed
 */
const tardinessInSelectedMonth = (tu: any): boolean => {
    if (!tu.date_filed) return false;
    
    const tuDate = new Date(tu.date_filed);
    return tuDate.getMonth() === selectedHRMonth.value && tuDate.getFullYear() === selectedHRYear.value;
};

/**
 * Get tardiness/undertime for current month based on date_filed
 */
const currentMonthTardiness = computed(() => {
    return tardiness.value.filter(tu => tardinessInSelectedMonth(tu));
});

/**
 * Get unique employees from tardiness/undertime
 */
const uniqueEmployeesTardiness = computed(() => {
    const empSet = new Set<string>();
    currentMonthTardiness.value.forEach(tu => {
        if (tu.employee) {
            empSet.add(tu.employee.name);
        }
    });
    return empSet;
});

/**
 * Get tardiness/undertime grouped by employee
 */
const currentMonthTardinesssByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthTardiness.value.forEach(tu => {
        const empName = tu.employee?.name || 'Unknown Employee';
        if (!grouped[empName]) {
            grouped[empName] = [];
        }
        grouped[empName].push(tu);
    });
    return grouped;
});

/**
 * Get pass slips grouped by employee
 */
const currentMonthPassSlipsByEmp = computed(() => {
    const grouped: Record<string, any[]> = {};
    currentMonthPassSlips.value.forEach(ps => {
        if (ps.employees && Array.isArray(ps.employees)) {
            ps.employees.forEach(emp => {
                if (!grouped[emp.name]) {
                    grouped[emp.name] = [];
                }
                grouped[emp.name].push(ps);
            });
        }
    });
    return grouped;
});

/**
 * Get unique leave types from all leaves in the selected year
 * Only display: Sick Leave, Vacation Leave, Mandatory/Forced Leave, Special Privilege Leave, Wellness Leave
 */
const uniqueLeaveTypes = computed(() => {
    const allowedTypes = [
        'Sick Leave',
        'Vacation Leave',
        'Mandatory/Forced Leave',
        'Special Privilege Leave',
        'Wellness Leave'
    ];
    
    // Get only the allowed types that exist in the selected year
    const typesSet = new Set<string>();
    leaves.value.forEach(leave => {
        const leaveYear = new Date(leave.date_of_filing).getFullYear();
        if (leaveYear === selectedHRYear.value && allowedTypes.includes(leave.type_of_leave)) {
            typesSet.add(leave.type_of_leave);
        }
    });
    
    // Return in the order specified
    return allowedTypes.filter(type => typesSet.has(type));
});

/**
 * Calculate business days (excluding weekends) from an array of dates or based on a date range
 * @param leave - Leave object with inclusive_dates or from_date/to_date
 * @returns Number of business days (weekdays only)
 */
const getLeaveBusinessDays = (leave: any): number => {
    // If it's a half-day leave, count as 0.5
    if (leave.is_half_day === true) {
        return 0.5;
    }
    
    // If number_of_working_days_applied_for is available, use it
    if (leave.number_of_working_days_applied_for && leave.number_of_working_days_applied_for > 0) {
        return leave.number_of_working_days_applied_for;
    }
    
    // If inclusive_dates is an array, count business days in it
    if (Array.isArray(leave.inclusive_dates) && leave.inclusive_dates.length > 0) {
        let businessDays = 0;
        leave.inclusive_dates.forEach((dateStr: string) => {
            const date = new Date(dateStr);
            const dayOfWeek = date.getDay();
            // 0 = Sunday, 6 = Saturday - exclude these
            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                businessDays++;
            }
        });
        return businessDays;
    }
    
    // Fallback: return 1 (represents single leave record)
    return 1;
};

/**
 * Get employee leaves summary grouped by employee and leave type for the selected year
 */
const employeeLeavesSummary = computed(() => {
    const empMap = new Map<number, { 
        employeeId: number; 
        employeeName: string; 
        leaveTypes: Record<string, number>; 
        totalLeaves: number 
    }>();
    
    leaves.value.forEach(leave => {
        const leaveYear = new Date(leave.date_of_filing).getFullYear();
        if (leaveYear !== selectedHRYear.value) return;
        
        const empId = leave.employee_id;
        const empName = leave.employee?.name || 'Unknown Employee';
        const leaveType = leave.type_of_leave;
        
        // Calculate business days for this leave
        const businessDays = getLeaveBusinessDays(leave);
        
        if (!empMap.has(empId)) {
            empMap.set(empId, {
                employeeId: empId,
                employeeName: empName,
                leaveTypes: {},
                totalLeaves: 0
            });
        }
        
        const empData = empMap.get(empId)!;
        empData.leaveTypes[leaveType] = (empData.leaveTypes[leaveType] || 0) + businessDays;
        empData.totalLeaves += businessDays;
    });
    
    return Array.from(empMap.values()).sort((a, b) => {
        // Extract last name (surname) from full name
        const getLastName = (name: string) => {
            const parts = name.trim().split(/\s+/);
            return parts[parts.length - 1]; // Get the last word as surname
        };
        
        const lastNameA = getLastName(a.employeeName);
        const lastNameB = getLastName(b.employeeName);
        
        // Compare by last name, then by first name if last names are the same
        const lastNameComparison = lastNameA.localeCompare(lastNameB);
        if (lastNameComparison !== 0) return lastNameComparison;
        
        // If last names are the same, sort by full name
        return a.employeeName.localeCompare(b.employeeName);
    });
});

/**
 * Filter user statistics based on selected user
 */
const filteredUserStatistics = computed(() => {
    let filtered = userStatistics.value;
    
    // If a user is selected in the Users Summary section, only show that user
    if (selectedUser.value) {
        filtered = filtered.filter(user => user.userId === selectedUser.value);
    }
    
    return filtered;
});

/**
 * Calculate user statistics: average pending time per document
 * Timer starts when user receives the document and stops when they forward/finalize it
 */
const userStatistics = computed(() => {
    const stats = new Map<number, { userId: number; name: string; totalHours: number; count: number; averageHours: number }>();
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Apply user filter - only include documents where selected user (if any) has transactions
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return;
        }
        
        // Sort transactions by created_at to process in chronological order
        const sortedTransactions = [...(document.transactions || [])].sort(
            (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
        
        let currentOwner: { userId: number; userName: string; startTime: Date } | null = null;
        
        sortedTransactions.forEach((transaction) => {
            const action = getActionType(transaction.action);
            const transactionTime = new Date(transaction.created_at);
            
            if (action === 'created') {
                // Document created - start timer for creator
                currentOwner = {
                    userId: transaction.user_id,
                    userName: transaction.user?.name || 'Unknown',
                    startTime: transactionTime
                };
            } else if (action === 'forwarded' && currentOwner) {
                // Document forwarded - stop timer for current owner and calculate pending time
                const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);
                
                const existing = stats.get(currentOwner.userId) || {
                    userId: currentOwner.userId,
                    name: currentOwner.userName,
                    totalHours: 0,
                    count: 0,
                    averageHours: 0
                };
                
                existing.totalHours += pendingHours;
                existing.count += 1;
                existing.averageHours = existing.totalHours / existing.count;
                stats.set(currentOwner.userId, existing);
                
                // Transfer ownership to the forwarded recipient
                if (transaction.forwardedToUser) {
                    currentOwner = {
                        userId: transaction.forwardedToUser.id,
                        userName: transaction.forwardedToUser.name,
                        startTime: transactionTime
                    };
                } else {
                    // Forwarded to office or municipality, not tracked as user time
                    currentOwner = null;
                }
            } else if (action === 'received') {
                // Someone received the document - start timer for them
                currentOwner = {
                    userId: transaction.user_id,
                    userName: transaction.user?.name || 'Unknown',
                    startTime: transactionTime
                };
            } else if (action === 'finalized' && currentOwner) {
                // Document finalized - stop timer for current owner and calculate pending time
                const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);
                
                const existing = stats.get(currentOwner.userId) || {
                    userId: currentOwner.userId,
                    name: currentOwner.userName,
                    totalHours: 0,
                    count: 0,
                    averageHours: 0
                };
                
                existing.totalHours += pendingHours;
                existing.count += 1;
                existing.averageHours = existing.totalHours / existing.count;
                stats.set(currentOwner.userId, existing);
                
                currentOwner = null;
            }
        });
    });
    
    return Array.from(stats.values())
        .sort((a, b) => b.averageHours - a.averageHours); // Sort by average hours descending
});

/**
 * Calculate document processing statistics: average processing time per document type
 */
const documentProcessingStatistics = computed(() => {
    const stats = new Map<string, { documentType: string; totalHours: number; count: number; averageHours: number }>();
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Include all documents with transactions (regardless of status)
        if (!document.transactions || document.transactions.length === 0) return;
        
        const docType = document.document_type || 'Other';
        
        // Get the creation timestamp from the first transaction (has the actual time)
        const creationTransaction = document.transactions[document.transactions.length - 1];
        const createdAt = new Date(creationTransaction.created_at);
        // If document is still being processed, use current time; otherwise use latest transaction time
        const endTime = (document.status !== 'finalized' && document.status !== 'ended') 
            ? new Date() 
            : new Date(document.transactions[0].created_at);
        
        // Calculate business hours elapsed (excluding weekends and office/municipality forwarding time)
        const processingHours = calculateElapsedTimeExcluding(document, createdAt, endTime);
        
        const existing = stats.get(docType) || {
            documentType: docType,
            totalHours: 0,
            count: 0,
            averageHours: 0
        };
        
        existing.totalHours += processingHours;
        existing.count += 1;
        existing.averageHours = existing.totalHours / existing.count;
        stats.set(docType, existing);
    });
    
    return Array.from(stats.values())
        .sort((a, b) => b.averageHours - a.averageHours); // Sort by average hours descending
});

/**
 * Get detailed breakdown for selected user
 */
const selectedUserDetails = computed(() => {
    if (!expandedUserId.value && expandedUserId.value !== 0) return [];
    
    const currentSelectedUserStat = userStatistics.value.find(u => u.userId === expandedUserId.value);
    if (!currentSelectedUserStat) return [];
    
    const details: Array<{ documentId: number; trackingNo: string; particulars: string; pendingHours: number; startDate: string; endDate: string }> = [];
    
    documents.value.forEach(document => {
        // Apply year and semester filters
        if (!matchesYearAndSemester(document.date)) return;
        
        // Apply user filter - only include documents where selected user (if any) has transactions
        if (selectedUser.value) {
            const userHasTransaction = document.transactions?.some(t => t.user_id === selectedUser.value);
            if (!userHasTransaction) return;
        }
        
        const sortedTransactions = [...(document.transactions || [])].sort(
            (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
        );
        
        let currentOwner: { userId: number; startTime: Date } | null = null;
        
        sortedTransactions.forEach((transaction) => {
            const action = getActionType(transaction.action);
            const transactionTime = new Date(transaction.created_at);
            
            if (action === 'created') {
                currentOwner = {
                    userId: transaction.user_id,
                    startTime: transactionTime
                };
            } else if (action === 'forwarded' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);
                
                details.push({
                    documentId: document.id,
                    trackingNo: document.tracking_no,
                    particulars: document.particulars || '-',
                    pendingHours,
                    startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }),
                    endDate: new Date(transactionTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })
                });
                
                if (transaction.forwardedToUser) {
                    currentOwner = {
                        userId: transaction.forwardedToUser.id,
                        startTime: transactionTime
                    };
                } else {
                    currentOwner = null;
                }
            } else if (action === 'received') {
                currentOwner = {
                    userId: transaction.user_id,
                    startTime: transactionTime
                };
            } else if (action === 'finalized' && currentOwner && currentOwner.userId === currentSelectedUserStat.userId) {
                const pendingHours = calculateElapsedTimeExcluding(document, currentOwner.startTime, transactionTime);
                
                details.push({
                    documentId: document.id,
                    trackingNo: document.tracking_no,
                    particulars: document.particulars || '-',
                    pendingHours,
                    startDate: new Date(currentOwner.startTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }),
                    endDate: new Date(transactionTime).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })
                });
                
                currentOwner = null;
            }
        });
    });
    
    return details;
});

/**
 * Calculate processing time from first 'created' transaction to latest transaction
 */
const calculateProcessingTime = (document: Document): string => {
    if (!document.transactions || document.transactions.length === 0) {
        return '-';
    }

    // Get the creation timestamp from the first transaction (has the actual time)
    const creationTransaction = document.transactions[document.transactions.length - 1];
    const createdAt = new Date(creationTransaction.created_at);
    const latestTransaction = document.transactions[0];
    
    // If document is still being processed, use current time; otherwise use latest transaction time
    const endTime = (document.status !== 'finalized' && document.status !== 'ended') 
        ? new Date() 
        : new Date(latestTransaction.created_at);

    // Calculate business hours elapsed (excluding weekends and office/municipality forwarding time)
    const businessHoursElapsed = calculateElapsedTimeExcluding(document, createdAt, endTime);
    
    // Format using existing formatter
    return formatHours(businessHoursElapsed);
};

/**
 * Get action type from action string
 */
const getActionType = (action: string): string => {
    if (action.toLowerCase().includes('created')) return 'created';
    if (action.toLowerCase().includes('forwarded')) return 'forwarded';
    if (action.toLowerCase().includes('received')) return 'received';
    if (action.toLowerCase().includes('ended') || action.toLowerCase().includes('finalized')) return 'finalized';
    return action;
};

/**
 * formatDuration: Formats duration in hours to a human-readable string with days, hours, and minutes
 * @param hours - Duration in hours (can be decimal, e.g., 0.583333 for 35 minutes)
 * @returns Formatted string like "1 day 2 hours 30 minutes" or "45 minutes"
 */
const formatDuration = (hours: number): string => {
    if (hours === null || hours === undefined) {
        return '0 minutes';
    }

    const totalMinutes = Math.round(hours * 60);
    const days = Math.floor(totalMinutes / (24 * 60));
    const remainingMinutesAfterDays = totalMinutes % (24 * 60);
    const durationHours = Math.floor(remainingMinutesAfterDays / 60);
    const minutes = remainingMinutesAfterDays % 60;

    const parts = [];
    if (days > 0) {
        parts.push(`${days}d`);
    }
    if (durationHours > 0) {
        parts.push(`${durationHours}h`);
    }
    if (minutes > 0) {
        parts.push(`${minutes}m`);
    }

    return parts.length > 0 ? parts.join(' ') : '0m';
};

/**
 * Get custodian display name: office/municipality/user based on latest transaction
 */
const getCustodianName = (document: Document): string => {
    // For finalized/ended documents, display '-'
    if (document.status === 'finalized') {
        return '-';
    }
    
    if (document.transactions.length === 0) {
        return document.user?.name || 'Unknown';
    }

    const latestTransaction = document.transactions[0];
    
    // Check for office forwarding (by ID field)
    if (latestTransaction.forwarded_to_office_id) {
        const office = offices.value.find(o => o.id === latestTransaction.forwarded_to_office_id);
        if (office) {
            return office.office_name;
        }
    }
    
    // Check for municipality forwarding (by ID field)
    if (latestTransaction.forwarded_to_municipality_id) {
        const municipality = municipalities.value.find(m => m.id === latestTransaction.forwarded_to_municipality_id);
        if (municipality) {
            return municipality.name;
        }
    }
    
    // Check for user-to-user forwarding
    if (latestTransaction.forwarded_to_user_id) {
        // Check if the forwarded-to user has received the document
        const hasReceived = document.transactions.some(t => 
            t.forwarded_to_user_id === latestTransaction.forwarded_to_user_id &&
            t.action.toLowerCase().includes('received')
        );
        
        if (hasReceived) {
            // User has received it, show that user as custodian
            const forwardedUser = users.value.find(u => u.id === latestTransaction.forwarded_to_user_id);
            if (forwardedUser) {
                return forwardedUser.name;
            }
        } else {
            // User hasn't received it yet, show the forwarder as custodian
            return latestTransaction.user?.name || document.user?.name || 'Unknown';
        }
    }
    
    // Fallback to the user who performed the transaction
    return latestTransaction.user?.name || document.user?.name || 'Unknown';
};

/**
 * Get processing time limit in days based on document type
 */
const getProcessingTimeLimit = (docType: string): number => {
    const type = docType.toLowerCase().trim();
    if (type.includes('annual')) return 25; // Annual Budget: 25 days
    if (type.includes('supplemental')) return 12; // Supplemental Budget: 12 days
    if (type.includes('proposal') || type.includes('proposals')) return 12; // Budget Proposals: 12 days
    if (type.includes('referral')) {
        if (type.includes('simple')) return 3; // Referral - Simple: 3 days
        if (type.includes('complex')) return 7; // Referral - Complex: 7 days
        if (type.includes('highly technical')) return 20; // Referral - Highly Technical: 20 days
    }
    return 30; // Default: 30 days for other types
};

/**
 * Calculate business days (excluding weekends) between two dates
 */
const calculateBusinessDays = (startDate: Date, endDate: Date): number => {
    let count = 0;
    const current = new Date(startDate);
    
    while (current <= endDate) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            count++;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return count;
};

/**
 * Calculate business hours elapsed (excluding weekends)
 */
const calculateBusinessHoursElapsed = (startDate: Date, endDate: Date): number => {
    let businessHours = 0;
    let current = new Date(startDate); // Keep actual start time (including hours/minutes)
    const end = new Date(endDate);
    
    // Iterate through each day
    while (current < end) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            const nextDay = new Date(current);
            nextDay.setDate(nextDay.getDate() + 1);
            nextDay.setHours(0, 0, 0, 0); // Next day at midnight
            
            if (nextDay <= end) {
                // Calculate hours from current time to next midnight
                const msToMidnight = nextDay.getTime() - current.getTime();
                businessHours += msToMidnight / (1000 * 60 * 60);
            } else {
                // Partial day - calculate hours from current time to end time
                const msElapsed = end.getTime() - current.getTime();
                businessHours += msElapsed / (1000 * 60 * 60);
            }
        }
        current.setDate(current.getDate() + 1);
        current.setHours(0, 0, 0, 0); // Set to midnight for next iteration
    }
    
    return businessHours;
};

/**
 * Calculate remaining business days and hours (excluding weekends)
 */
const calculateRemainingBusinessTime = (remainingHours: number): { days: number; hours: number } => {
    let remainingBusinessHours = remainingHours;
    let businessDays = 0;
    const now = new Date();
    let current = new Date(now);
    
    // Count how many business hours we can fit in the calendar
    while (remainingBusinessHours > 0) {
        const dayOfWeek = current.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            // This is a business day
            if (remainingBusinessHours >= 24) {
                remainingBusinessHours -= 24;
                businessDays += 1;
            } else {
                // Remaining hours are less than a full day
                break;
            }
        }
        current.setDate(current.getDate() + 1);
    }
    
    return { days: businessDays, hours: Math.round(remainingBusinessHours) };
};

/**
 * Calculate elapsed time excluding periods when document was with office or municipality
 */
const calculateElapsedTimeExcluding = (document: Document, startDate: Date, endDate: Date): number => {
    if (!document.transactions || document.transactions.length === 0) {
        return calculateBusinessHoursElapsed(startDate, endDate);
    }
    
    // Find all office/municipality forward transactions and their corresponding receive/return
    const excludePeriods: Array<{start: Date; end: Date}> = [];
    
    for (let i = 0; i < document.transactions.length; i++) {
        const transaction = document.transactions[i];
        
        // Check if this is a forward to office or municipality
        if ((transaction.forwarded_to_office_id || transaction.forwarded_to_municipality_id) &&
            transaction.action.toLowerCase().includes('forward')) {
            
            const forwardStart = new Date(transaction.created_at);
            
            // Find the corresponding received transaction or next user-to-user forward
            let forwardEnd = endDate; // Default to end date
            for (let j = i - 1; j >= 0; j--) {
                const nextTransaction = document.transactions[j];
                const isReceived = nextTransaction.action.toLowerCase().includes('received');
                const isUserForward = nextTransaction.forwarded_to_user_id && 
                                    !nextTransaction.forwarded_to_office_id && 
                                    !nextTransaction.forwarded_to_municipality_id &&
                                    nextTransaction.action.toLowerCase().includes('forward');
                
                if (isReceived || isUserForward) {
                    forwardEnd = new Date(nextTransaction.created_at);
                    break;
                }
            }
            
            // Only exclude if the period is within our time range
            if (forwardStart < endDate && forwardEnd > startDate) {
                excludePeriods.push({
                    start: new Date(Math.max(forwardStart.getTime(), startDate.getTime())),
                    end: new Date(Math.min(forwardEnd.getTime(), endDate.getTime()))
                });
            }
        }
    }
    
    // Calculate total elapsed time
    let totalElapsed = calculateBusinessHoursElapsed(startDate, endDate);
    
    // Subtract excluded periods
    for (const period of excludePeriods) {
        const excludedHours = calculateBusinessHoursElapsed(period.start, period.end);
        totalElapsed -= excludedHours;
    }
    
    return Math.max(0, totalElapsed);
};

/**
 * Calculate time left for processing and determine color
 */
const getTimeLeftInfo = (document: Document): { daysLeft: number; hoursLeft: number; isLapsed: boolean; percentage: number } => {
    if (!document.transactions || document.transactions.length === 0) {
        return { daysLeft: 0, hoursLeft: 0, isLapsed: false, percentage: 0 };
    }
    
    // Get limit based on document type (in business days)
    const limit = getProcessingTimeLimit(document.document_type);
    
    // Get the creation timestamp from the first transaction (has the actual time)
    // Find the creation transaction (typically the oldest/last in the array)
    const creationTransaction = document.transactions[document.transactions.length - 1];
    const createdDate = new Date(creationTransaction.created_at);
    
    // If document is ended (finalized), freeze the time at the moment of ending
    let now = currentTime.value; // Use currentTime for real-time updates
    if (document.status === 'ended' || document.status === 'finalized') {
        // Use the last transaction date as the reference point (when it was ended)
        const lastTransaction = document.transactions[0];
        now = new Date(lastTransaction.created_at);
    }
    
    // Calculate remaining business days in hours
    const totalHoursLimit = limit * 24; // Convert business days to hours
    
    // Calculate business hours elapsed (only counting weekdays), excluding office/municipality forwarding time
    const hoursElapsed = calculateElapsedTimeExcluding(document, createdDate, now);
    
    // Calculate total hours left
    const totalHoursLeft = Math.max(0, totalHoursLimit - hoursElapsed);
    
    // Calculate remaining business days and hours (excluding weekends)
    const { days: daysLeft, hours: hoursLeft } = calculateRemainingBusinessTime(totalHoursLeft);
    
    const isLapsed = hoursElapsed > totalHoursLimit;
    const percentage = (hoursElapsed / totalHoursLimit) * 100;
    
    return { daysLeft, hoursLeft, isLapsed, percentage };
};

/**
 * Get the text to display for time left
 */
const getTimeLeftText = (document: Document): string => {
    const { daysLeft, hoursLeft, isLapsed } = getTimeLeftInfo(document);
    
    if (isLapsed) {
        return `Overdue`;
    }
    
    if (daysLeft === 0 && hoursLeft === 0) {
        return `No time left`;
    }
    
    if (daysLeft === 0) {
        return `${hoursLeft} hrs`;
    }
    
    if (hoursLeft === 0) {
        return `${daysLeft} days`;
    }
    
    return `${daysLeft} days ${hoursLeft} hrs`;
};

/**
 * Get the style classes for time left cell based on remaining time
 */
const getTimeLeftStyles = (document: Document): object => {
    const { daysLeft, isLapsed, percentage } = getTimeLeftInfo(document);
    
    let bgColor = '';
    let textColor = '';
    let borderColor = '';
    
    if (isLapsed) {
        // Red: lapsed
        bgColor = 'bg-red-100 dark:bg-red-900/30';
        textColor = 'text-red-800 dark:text-red-300';
        borderColor = 'border-red-200 dark:border-red-800';
    } else if (percentage >= 75) {
        // Red: very urgent (75%+ of time used)
        bgColor = 'bg-red-100 dark:bg-red-900/30';
        textColor = 'text-red-800 dark:text-red-300';
        borderColor = 'border-red-200 dark:border-red-800';
    } else if (percentage >= 50) {
        // Orange: urgent (50-75% of time used)
        bgColor = 'bg-orange-100 dark:bg-orange-900/30';
        textColor = 'text-orange-800 dark:text-orange-300';
        borderColor = 'border-orange-200 dark:border-orange-800';
    } else if (percentage >= 25) {
        // Yellow: moderate (25-50% of time used)
        bgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
        textColor = 'text-yellow-800 dark:text-yellow-300';
        borderColor = 'border-yellow-200 dark:border-yellow-800';
    } else {
        // Green: plenty of time (< 25% of time used)
        bgColor = 'bg-green-100 dark:bg-green-900/30';
        textColor = 'text-green-800 dark:text-green-300';
        borderColor = 'border-green-200 dark:border-green-800';
    }
    
    return {
        'inline-flex': true,
        'items-center': true,
        'gap-1': true,
        'px-3': true,
        'py-1': true,
        [bgColor]: true,
        [textColor]: true,
        'rounded-full': true,
        'border': true,
        [borderColor]: true,
        'whitespace-nowrap': true
    };
};

/**
 * Format hours into readable string
 */
const formatHours = (hours: number): string => {
    if (hours < 1) {
        return Math.round(hours * 60) + ' min';
    }
    if (hours < 24) {
        return hours.toFixed(1) + ' hrs';
    }
    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;
    return `${days} day${days > 1 ? 's' : ''} ${remainingHours.toFixed(1)} hrs`;
};

/**
 * Format date range from from_date and to_date
 */
const formatDateRange = (fromDate: any, toDate: any): string => {
    if (!fromDate && !toDate) return '';
    
    try {
        if (fromDate && toDate) {
            // Date range
            const start = new Date(fromDate);
            const end = new Date(toDate);
            if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
                return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            }
        } else if (fromDate) {
            // Single date
            const date = new Date(fromDate);
            if (!isNaN(date.getTime())) {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }
    } catch {
        // Fallback
    }
    
    return fromDate || toDate || '';
};

/**
 * Format inclusive dates from array or string format
 */
const formatInclusiveDates = (dates: any): string => {
    if (!dates) return '';
    
    // If it's a JSON string like '["2026-02-23", "2026-02-27"]', parse it first
    let datesArray = dates;
    if (typeof dates === 'string' && dates.startsWith('[')) {
        try {
            datesArray = JSON.parse(dates);
        } catch {
            // Not a JSON array, treat as regular string
        }
    }
    
    if (Array.isArray(datesArray)) {
        // Process each element, which could be a single date or a date range string
        const formattedParts = datesArray.map((element: any) => {
            const elementStr = String(element).trim();
            
            // Check if this element is a date range (contains " - ")
            if (elementStr.includes(' - ')) {
                const rangeParts = elementStr.split(' - ').map(p => p.trim());
                if (rangeParts.length === 2) {
                    try {
                        const startDate = new Date(rangeParts[0]);
                        const endDate = new Date(rangeParts[1]);
                        if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                            const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            return `${startFormatted} - ${endFormatted}`;
                        }
                    } catch {}
                    // If parsing failed, return as is
                    return elementStr;
                }
            }
            
            // Single date
            try {
                const date = new Date(elementStr);
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }
            } catch {}
            
            return elementStr;
        });
        
        return formattedParts.join(', ');
    } else if (typeof dates === 'string') {
        // String format like "2026-02-21" or "2026-02-21 - 2026-02-22"
        if (dates.includes(' - ')) {
            const parts = dates.split(' - ').map(p => p.trim());
            if (parts.length === 2) {
                try {
                    const startDate = new Date(parts[0]);
                    const endDate = new Date(parts[1]);
                    if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                        const startFormatted = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        const endFormatted = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        return `${startFormatted} - ${endFormatted}`;
                    }
                } catch {
                    return dates;
                }
            } else if (parts.length > 2) {
                // Multiple date ranges separated by " - "
                const formattedParts = [];
                for (let i = 0; i < parts.length; i++) {
                    try {
                        const date = new Date(parts[i]);
                        if (!isNaN(date.getTime())) {
                            formattedParts.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                        } else {
                            formattedParts.push(parts[i]);
                        }
                    } catch {
                        formattedParts.push(parts[i]);
                    }
                }
                return formattedParts.join(', ');
            }
        } else {
            // Single date
            try {
                const date = new Date(dates);
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }
            } catch {
                return dates;
            }
        }
        return dates;
    }
    
    return '';
};

/**
 * Format time from HH:MM:SS to HH:MM AM/PM
 */
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

/**
 * Format inclusive dates array to readable string
 */
const formatInclusiveDatesToString = (dates: any): string => {
    return formatInclusiveDates(dates);
};

/**
 * Get other leave types (not in the main 5 columns) for an employee
 */
const getOtherLeaveTypes = (empSummary: any): string[] => {
    const allowedTypes = [
        'Sick Leave',
        'Vacation Leave',
        'Mandatory/Forced Leave',
        'Special Privilege Leave',
        'Wellness Leave'
    ];
    
    return Object.keys(empSummary.leaveTypes).filter(type => !allowedTypes.includes(type));
};

/**
 * Get other leave types as a map with their counts
 */
const getOtherLeaveTypesMap = (empSummary: any): Record<string, number> => {
    const otherTypes: Record<string, number> = {};
    getOtherLeaveTypes(empSummary).forEach(type => {
        otherTypes[type] = empSummary.leaveTypes[type];
    });
    return otherTypes;
};

/**
 * Toggle document expansion
 */
const toggleExpanded = (documentId: number) => {
    expandedDocumentId.value = expandedDocumentId.value === documentId ? null : documentId;
};

/**
 * Toggle user expansion
 */
const toggleUserExpanded = (userIdOrIndex: number | { userId: number }) => {
    const userId = typeof userIdOrIndex === 'object' ? userIdOrIndex.userId : userIdOrIndex;
    expandedUserId.value = expandedUserId.value === userId ? null : userId;
};

/**
 * Toggle document type expansion
 */
const toggleDocumentTypeExpanded = (documentType: string) => {
    expandedDocumentType.value = expandedDocumentType.value === documentType ? null : documentType;
};

/**
 * Toggle HR summary panel expansion
 */
const toggleHRExpanded = (type: 'leaves' | 'travelOrders' | 'passSlips' | 'tardiness') => {
    expandedHRType.value = expandedHRType.value === type ? null : type;
};

/**
 * Get documents for a specific document type
 */
const getDocumentsForType = (documentType: string): Document[] => {
    return filteredDocuments.value.filter(doc => {
        return doc.document_type === documentType;
    });
};

/**
 * Generate Budget Proposals Report
 */
const generateReport = async () => {
    reportErrors.value = {};
    
    try {
        // Get year from as of date
        const asOfDate = new Date(reportData.value.asOfDate);
        const selectedYear = asOfDate.getFullYear();
        
        // Filter documents for Budget Proposals created in the selected year
        const budgetProposals = documents.value.filter(doc => {
            // Check if document type is Budget Proposal
            if (!doc.document_type.toLowerCase().includes('proposal')) return false;
            
            // Check if document date year matches selected year
            const docDate = new Date(doc.date);
            return docDate.getFullYear() === selectedYear;
        });
        
        // Get created and received transactions for each document
        const reportData_items: Array<{
            dateReceived: string;
            trackingNo: string;
            source: string;
            particulars: string;
        }> = [];
        
        budgetProposals.forEach(doc => {
            if (!doc.transactions || doc.transactions.length === 0) return;
            
            // Find creation transaction only
            const createdTx = doc.transactions[doc.transactions.length - 1];
            
            // Add creation record only
            reportData_items.push({
                dateReceived: formatDateToWords(createdTx.created_at),
                trackingNo: doc.tracking_no,
                source: doc.source || 'N/A',
                particulars: doc.particulars || 'N/A'
            });
        });
        
        // Get signatory names and designations
        const reviewedByUser = users.value.find(u => u.id === reportData.value.reviewedBy);
        const certifiedByUser = users.value.find(u => u.id === reportData.value.certifiedCorrect);
        const reviewedByDesignation = getEmployeeDesignation(reportData.value.reviewedBy);
        const certifiedCorrectDesignation = getEmployeeDesignation(reportData.value.certifiedCorrect);
        
        // Generate HTML report
        const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Budget Proposals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .header h1 { margin: 5px 0; font-size: 16px; font-weight: bold; }
        .as-of { text-center: right; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-around; }
        .signature-box { width: 30%; text-align: center; }
        .signature-title { font-size: 12px; margin-bottom: 50px; text-align: left; }
        .signature-line { border-top: 1px solid #000; margin: 40px 0 5px 0; }
        .signature-name { font-weight: bold; font-size: 12px; margin-top: 5px; }
        .signature-designation { font-size: 11px; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <p style="text-align: center; font-size: 12px; margin: 0;">Republic of the Philippines</p>
        <p style="text-align: center; font-size: 12px; margin: 0; font-weight: bold;">PROVINCIAL GOVERNMENT OF BENGUET</p>
        <p style="text-align: center; font-size: 12px; margin: 0;">La Trinidad, Benguet</p>
        <p style="text-align: center; font-size: 12px; margin: 0; font-weight: bold;">Provincial Budget Office</p>
    </div>
    
    <p style="text-align: center; font-size: 12px; margin-bottom: 0; margin-top: 10px; font-weight: bold;">BUDGET PROPOSALS</p>
    <p style="text-align: center; font-size: 12px; margin-top:0; margin-bottom: 20px;">As of ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
    
    <table>
        <thead>
            <tr>
                <th style="text-align: center;">Date Received</th>
                <th style="text-align: center;">Tracking No</th>
                <th style="text-align: center;">Source</th>
                <th style="text-align: center;">Particulars</th>
            </tr>
        </thead>
        <tbody>
            ${reportData_items.length > 0 ? reportData_items.map(item => `
            <tr>
                <td>${item.dateReceived}</td>
                <td>${item.trackingNo}</td>
                <td>${item.source}</td>
                <td>${item.particulars}</td>
            </tr>
            `).join('') : '<tr><td colspan="4" style="text-align: center;">No data available</td></tr>'}
        </tbody>
    </table>
    
    ${reportData.value.reviewedBy || reportData.value.certifiedCorrect ? `
    <div class="signature-section">
        ${reportData.value.reviewedBy ? `
        <div class="signature-box">
            <div class="signature-title">Reviewed By:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(reviewedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${reviewedByDesignation}</div>
        </div>
        ` : ''}
        
        ${reportData.value.certifiedCorrect ? `
        <div class="signature-box">
            <div class="signature-title">Certified Correct:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(certifiedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${certifiedCorrectDesignation}</div>
        </div>
        ` : ''}
    </div>
    ` : ''}
</body>
</html>
        `;
        
        // Open print dialog
        const reportWindow = window.open('', 'PRINT_REPORT', 'height=900,width=1500');
        if (reportWindow) {
            reportWindow.document.write(htmlContent);
            reportWindow.document.close();
            reportWindow.print();
        }
        
        showReportModal.value = false;
    } catch (e) {
        reportErrors.value.submit = e instanceof Error ? e.message : 'An error occurred';
    }
};

/**
 * Format date and time
 */
const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

/**
 * Format date to words (e.g., February 12, 2026)
 */
const formatDateToWords = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

/**
 * Get employee designation by user ID (match by user name)
 */
const getEmployeeDesignation = (userId: number | null | undefined): string => {
    if (!userId) return 'N/A';
    const user = users.value.find(u => u.id === userId);
    if (!user) return 'N/A';
    const employee = employees.value.find(e => e.name === user.name);
    return employee?.designation || 'N/A';
};

/**
 * Capitalize first letter of each word
 */
const capitalizeWords = (str: string): string => {
    return str.toUpperCase();
};

/**
 * Change page
 */
const changePage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

/**
 * Generate Summary Report for Leaves, Pass Slips, and Travel Orders
 */
const generateSummaryReport = async () => {
    summaryErrors.value = {};
    
    try {
        // Validate inputs
        if (!summaryData.value.month) {
            summaryErrors.value.submit = 'Month is required';
            return;
        }
        if (!summaryData.value.employmentType) {
            summaryErrors.value.submit = 'Employment type is required';
            return;
        }
        if (summaryData.value.employmentType === 'casual' && !summaryData.value.casualPeriod) {
            summaryErrors.value.submit = 'Report period is required for casual employees';
            return;
        }
        if (!summaryData.value.preparedBy) {
            summaryErrors.value.submit = 'Prepared by is required';
            return;
        }
        if (!summaryData.value.certifiedCorrect) {
            summaryErrors.value.submit = 'Certified correct is required';
            return;
        }

        // Fetch data for leaves, pass slips, travel orders, and tardiness
        const [leavesRes, passSlipsRes, travelOrdersRes, tardinessRes] = await Promise.all([
            fetch('/api/leaves'),
            fetch('/api/pass-slips'),
            fetch('/api/travel-orders'),
            fetch('/api/tardiness')
        ]);

        const leaves = leavesRes.ok ? await leavesRes.json() : [];
        const passSlips = passSlipsRes.ok ? await passSlipsRes.json() : [];
        const travelOrders = travelOrdersRes.ok ? await travelOrdersRes.json() : [];
        const tardinessData = tardinessRes.ok ? await tardinessRes.json() : { data: [] };
        const tardiness = tardinessData.data || tardinessData;

        // Get current year
        const currentYear = new Date().getFullYear();
        const monthName = months[summaryData.value.month - 1];

        // Filter by month and employment type, then group by employee
        const employeeData = new Map<number, {
            employee_id: string;
            id: number;
            name: string;
            designation: string;
            leaves: any[];
            passSlips: any[];
            travelOrders: any[];
            tardiness: any[];
        }>();

        // Helper function to check if date falls in specified month
        const isInMonth = (dateString: string) => {
            const date = new Date(dateString);
            return date.getMonth() === (summaryData.value.month! - 1) && 
                   date.getFullYear() === currentYear;
        };

        // Helper function to get employment type based on designation
        const getEmploymentType = (emp: any) => {
            if (!emp || !emp.designation) return 'permanent';
            const designation = emp.designation.toLowerCase();
            return designation.includes('casual') ? 'casual' : 'permanent';
        };

        // Helper function to calculate time difference between two HH:MM time strings
        const calculateTimeDifference = (startTime: string, endTime: string): { hours: number; minutes: number; display: string } => {
            if (!startTime || !endTime) return { hours: 0, minutes: 0, display: 'N/A' };
            
            try {
                // Clean the time strings
                const startClean = String(startTime).trim();
                const endClean = String(endTime).trim();
                
                if (!startClean || !endClean) return { hours: 0, minutes: 0, display: 'N/A' };
                
                const startParts = startClean.split(':');
                const endParts = endClean.split(':');
                
                if (startParts.length < 2 || endParts.length < 2) return { hours: 0, minutes: 0, display: 'N/A' };
                
                const startHour = parseInt(startParts[0], 10);
                const startMin = parseInt(startParts[1], 10);
                const endHour = parseInt(endParts[0], 10);
                const endMin = parseInt(endParts[1], 10);
                
                if (isNaN(startHour) || isNaN(startMin) || isNaN(endHour) || isNaN(endMin)) {
                    return { hours: 0, minutes: 0, display: 'N/A' };
                }
                
                let totalMinutes = (endHour * 60 + endMin) - (startHour * 60 + startMin);
                
                // Handle case where end time is next day (e.g., 11 PM to 1 AM)
                if (totalMinutes < 0) {
                    totalMinutes += 24 * 60;
                }
                
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;
                
                return {
                    hours,
                    minutes,
                    display: hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
                };
            } catch (e) {
                console.error('Error calculating time difference:', e);
                return { hours: 0, minutes: 0, display: 'N/A' };
            }
        };

        // Helper function to sum two time objects
        const sumTimes = (time1: any, time2: any): { hours: number; minutes: number; display: string } => {
            const totalMin = (time1.hours || 0) * 60 + (time1.minutes || 0) + (time2.hours || 0) * 60 + (time2.minutes || 0);
            const hours = Math.floor(totalMin / 60);
            const minutes = totalMin % 60;
            return {
                hours,
                minutes,
                display: hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`
            };
        };

        // Helper function to check if a leave falls in the specified month based on inclusive_dates
        // For date ranges spanning multiple months, include in all overlapping months
        const leaveInMonth = (leave: any): boolean => {
            if (!leave.inclusive_dates || leave.inclusive_dates.length === 0) return false;
            
            const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
            const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
            
            for (const dateEntry of leave.inclusive_dates) {
                if (!dateEntry) continue;
                
                // Handle date ranges like "2026-01-29 - 2026-02-04"
                if (dateEntry.includes(' - ')) {
                    const [startStr, endStr] = dateEntry.split(' - ');
                    const rangeStart = new Date(startStr.trim());
                    const rangeEnd = new Date(endStr.trim());
                    
                    // Check if the date range overlaps with the specified month
                    if (rangeStart <= monthEnd && rangeEnd >= monthStart) {
                        // For casual employees, also check if it overlaps with the casual period
                        if (summaryData.value.employmentType === 'casual') {
                            if (isDateRangeInCasualPeriod(startStr.trim(), endStr.trim())) {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    }
                } else {
                    // Handle single dates
                    const date = new Date(dateEntry.trim());
                    if (date.getMonth() === (summaryData.value.month! - 1) && date.getFullYear() === currentYear) {
                        // For casual employees, also check if it falls within the casual period
                        if (summaryData.value.employmentType === 'casual') {
                            if (isDateInCasualPeriod(dateEntry.trim())) {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    }
                }
            }
            return false;
        };

        // Helper function to check if a date falls within the casual period
        const isDateInCasualPeriod = (dateString: string): boolean => {
            if (!summaryData.value.casualPeriod) return true; // If not casual or no period selected, include all
            
            const date = new Date(dateString);
            const day = date.getDate();
            const lastDay = new Date(currentYear, summaryData.value.month!, 0).getDate();
            
            if (summaryData.value.casualPeriod === '1-15') {
                return day >= 1 && day <= 15;
            } else if (summaryData.value.casualPeriod === '16-last') {
                return day >= 16 && day <= lastDay;
            }
            return true;
        };

        // Helper function to check if a date range overlaps with the casual period
        const isDateRangeInCasualPeriod = (startDateString: string, endDateString: string): boolean => {
            if (!summaryData.value.casualPeriod) return true; // If not casual or no period selected, include all
            
            const startDate = new Date(startDateString);
            const endDate = new Date(endDateString);
            const lastDay = new Date(currentYear, summaryData.value.month!, 0).getDate();
            
            // Get the portion of the date range that falls within the selected month
            const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
            const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
            
            // Clamp the range to the month boundaries
            const effectiveStart = startDate > monthStart ? startDate : monthStart;
            const effectiveEnd = endDate < monthEnd ? endDate : monthEnd;
            
            const effectiveStartDay = effectiveStart.getDate();
            const effectiveEndDay = effectiveEnd.getDate();
            
            if (summaryData.value.casualPeriod === '1-15') {
                // Include if the range overlaps with days 1-15
                return effectiveStartDay <= 15 && effectiveEndDay >= 1;
            } else if (summaryData.value.casualPeriod === '16-last') {
                // Include if the range overlaps with days 16 to last day
                return effectiveStartDay <= lastDay && effectiveEndDay >= 16;
            }
            return true;
        };

        // Process leaves
        leaves.forEach((leave: any) => {
            if (!leaveInMonth(leave)) return;
            
            // Find employee by ID
            const empId = leave.employee_id;
            let employee = leave.employee;
            if (!employee && empId) {
                employee = employees.value.find(e => e.id === empId);
            }
            if (!employee) return;
            if (getEmploymentType(employee) !== summaryData.value.employmentType) return;

            if (!employeeData.has(empId)) {
                employeeData.set(empId, {
                    employee_id: employee?.employee_id || '',
                    id: empId,
                    name: employee?.name || 'N/A',
                    designation: employee?.designation || '',
                    leaves: [],
                    passSlips: [],
                    travelOrders: [],
                    tardiness: []
                });
            }
            employeeData.get(empId)!.leaves.push(leave);
        });

        // Process pass slips
        passSlips.forEach((ps: any) => {
            if (!isInMonth(ps.date)) return;
            
            // For casual employees, also check if it falls within the casual period
            if (summaryData.value.employmentType === 'casual' && !isDateInCasualPeriod(ps.date)) return;
            
            // Pass slips can have multiple employees
            const empsInPassSlip = ps.employees && Array.isArray(ps.employees) ? ps.employees : [];
            
            empsInPassSlip.forEach((emp: any) => {
                if (!emp || !emp.id) return;
                if (getEmploymentType(emp) !== summaryData.value.employmentType) return;
                
                const empId = emp.id;
                if (!employeeData.has(empId)) {
                    employeeData.set(empId, {
                        employee_id: emp?.employee_id || '',
                        id: empId,
                        name: emp?.name || 'N/A',
                        designation: emp?.designation || '',
                        leaves: [],
                        passSlips: [],
                        travelOrders: [],
                        tardiness: []
                    });
                }
                employeeData.get(empId)!.passSlips.push(ps);
            });
        });

        // Helper function to check if travel order falls in the specified month based on from_date and to_date
        // For travel orders spanning multiple months, include in all overlapping months
        const travelOrderInMonth = (to: any): boolean => {
            if (!to.from_date || !to.to_date) return false;
            
            const fromDate = new Date(to.from_date);
            const toDate = new Date(to.to_date);
            const monthStart = new Date(currentYear, (summaryData.value.month! - 1), 1);
            const monthEnd = new Date(currentYear, summaryData.value.month!, 0);
            
            // Check if the travel order date range overlaps with the specified month
            if (fromDate <= monthEnd && toDate >= monthStart) {
                // For casual employees, also check if it overlaps with the casual period
                if (summaryData.value.employmentType === 'casual') {
                    return isDateRangeInCasualPeriod(to.from_date, to.to_date);
                }
                return true;
            }
            return false;
        };

        // Process travel orders
        travelOrders.forEach((to: any) => {
            if (!travelOrderInMonth(to)) return;
            
            // Travel orders might have multiple employees, or a single employee relationship
            let empsToProcess = [];
            
            if (to.employees && Array.isArray(to.employees)) {
                empsToProcess = to.employees;
            } else if (to.employee) {
                empsToProcess = [to.employee];
            } else if (to.employee_id) {
                // Try to find employee by ID
                const emp = employees.value.find(e => e.id === to.employee_id);
                if (emp) {
                    empsToProcess = [emp];
                }
            }
            
            empsToProcess.forEach((emp: any) => {
                if (!emp || !emp.id) return;
                if (getEmploymentType(emp) !== summaryData.value.employmentType) return;
                
                const empId = emp.id;
                if (!employeeData.has(empId)) {
                    employeeData.set(empId, {
                        employee_id: emp?.employee_id || '',
                        id: empId,
                        name: emp.name || 'N/A',
                        designation: emp?.designation || '',
                        leaves: [],
                        passSlips: [],
                        travelOrders: [],
                        tardiness: []
                    });
                }
                employeeData.get(empId)!.travelOrders.push(to);
            });
        });

        // Process tardiness/undertime
        tardiness.forEach((tu: any) => {
            if (!isInMonth(tu.date_filed)) return;
            
            // For casual employees, also check if it falls within the casual period using requested_date
            if (summaryData.value.employmentType === 'casual' && tu.requested_date && !isDateInCasualPeriod(tu.requested_date)) return;
            
            const empId = tu.employee_id;
            let employee = tu.employee;
            if (!employee && empId) {
                employee = employees.value.find(e => e.id === empId);
            }
            if (!employee) return;
            if (getEmploymentType(employee) !== summaryData.value.employmentType) return;

            if (!employeeData.has(empId)) {
                employeeData.set(empId, {
                    employee_id: employee?.employee_id || '',
                    id: empId,
                    name: employee?.name || 'N/A',
                    designation: employee?.designation || '',
                    leaves: [],
                    passSlips: [],
                    travelOrders: [],
                    tardiness: []
                });
            }
            employeeData.get(empId)!.tardiness.push(tu);
        });

        // Get signatory names
        const preparedByData = administrativeStaffEmployees.value.find(e => e.id === summaryData.value.preparedBy);
        const preparedByEmployee = preparedByData || employees.value.find(e => e.id === summaryData.value.preparedBy);
        const certifiedByUser = users.value.find(u => u.id === summaryData.value.certifiedCorrect);
        const certifiedByEmployee = certifiedByUser ? employees.value.find(e => e.name === certifiedByUser.name) : null;

        // Generate HTML report
        const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary of Leaves, Pass Slips and Travel Orders</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 20px; }
        .header p { margin: 5px 0; font-size: 12px; }
        .header h1 { margin: 5px 0; font-size: 14px; font-weight: bold; }
        .as-of { text-align: center; margin-bottom: 20px; font-size: 12px; }
        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 4px 3px; text-align: left; font-size: 11px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature-section { margin-top: 20px; display: flex; justify-content: space-around; }
        .signature-box { width: 30%; text-align: center; }
        .signature-title { font-size: 11px; margin-bottom: 50px; text-align: left; font-weight: bold; }
        .signature-line { border-top: 1px solid #000; margin: 40px 0 5px 0; }
        .signature-name { font-weight: bold; font-size: 11px; margin-top: 5px; }
        .signature-designation { font-size: 11px; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="header" style="display: flex; align-items: center; justify-content: center; gap: 20px;">
        <div style="width: 90px; flex-shrink: 0;">
            <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
        </div>
        <div style="text-align: center;">
            <p style="margin-bottom: 0;">Republic of the Philippines</p>
            <p style="font-weight: bold; margin-top: 0; margin-bottom: 0;">PROVINCIAL GOVERNMENT OF BENGUET</p>
            <p style="margin-top: 0; margin-bottom: 0;">Poblacion, La Trinidad 2601</p>
            <p style="font-weight: bold; margin-top: 0;">Provincial Budget Office</p>
        </div>
        <div style="width: 90px; flex-shrink: 0;">
            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
        </div>
    </div>
    
    <p style="text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 0;">SUMMARY OF LEAVES, TRAVEL ORDERS, PASS SLIPS AND TARDINESS/UNDERTIME</p>
    <p style="text-align: center; font-size: 12px; margin-top: 0; margin-bottom: 20px;">For the month of ${summaryData.value.employmentType === 'casual' ? (summaryData.value.casualPeriod === '1-15' ? `${monthName} 1-15, ${currentYear}` : `${monthName} 16-${new Date(currentYear, summaryData.value.month!, 0).getDate()}, ${currentYear}`) : `${monthName} ${currentYear}`} (${summaryData.value.employmentType.charAt(0).toUpperCase() + summaryData.value.employmentType.slice(1).toLowerCase()})</p>
    
    ${Array.from(employeeData.values()).length > 0 ? `
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="text-align: center; font-weight: bold; vertical-align: middle; width: 8%;">ID NO</th>
                <th rowspan="2" style="text-align: center; font-weight: bold; vertical-align: middle; width: 12%;">NAME</th>
                <th colspan="2" style="text-align: center; font-weight: bold;">LEAVES</th>
                <th colspan="1" style="text-align: center; font-weight: bold;">TRAVEL ORDERS</th>
                <th colspan="3" style="text-align: center; font-weight: bold;">PASS SLIPS</th>
                <th colspan="7" style="text-align: center; font-weight: bold;">TARDINESS/UNDERTIME</th>
            </tr>
            <tr>
                <th style="text-align: center; font-weight: bold; width: 10%;">Type of Leave</th>
                <th style="text-align: center; font-weight: bold; width: 8%;">Inclusive Dates</th>
                <th style="text-align: center; font-weight: bold; width: 8%;">Inclusive Dates</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Date</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Time of Departure</th>
                <th style="text-align: center; font-weight: bold; width: 6%;">Return Time</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Tard. Date</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Tard. Hrs/Mins</th>
                <th style="text-align: center; font-weight: bold; width: 4%;">Tard. Count</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Undert. Date</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Undert. Hrs/Mins</th>
                <th style="text-align: center; font-weight: bold; width: 4%;">Undert. Count</th>
                <th style="text-align: center; font-weight: bold; width: 5%;">Total Hrs/Mins</th>
            </tr>
        </thead>
        <tbody>
            ${(() => {
                const sortedEntries = Array.from(employeeData.entries()).sort((a, b) => {
                    // Check if either employee has "Provincial Budget Officer" designation
                    const aIsProvincialBudgetOfficer = a[1].designation?.toLowerCase().includes('provincial budget officer') || false;
                    const bIsProvincialBudgetOfficer = b[1].designation?.toLowerCase().includes('provincial budget officer') || false;
                    
                    // If one is Provincial Budget Officer and the other is not, Provincial Budget Officer comes first
                    if (aIsProvincialBudgetOfficer && !bIsProvincialBudgetOfficer) return -1;
                    if (!aIsProvincialBudgetOfficer && bIsProvincialBudgetOfficer) return 1;
                    
                    // Otherwise, sort alphabetically by last name
                    const aName = a[1].name.split(' ').pop() || '';
                    const bName = b[1].name.split(' ').pop() || '';
                    return aName.localeCompare(bName);
                });
                
                return sortedEntries.map(([empId, emp]) => {
                    // Consolidate tardiness and undertime records
                    const tardinessRecords = emp.tardiness.filter((r: any) => r.type === 'Tardiness');
                    const undertimeRecords = emp.tardiness.filter((r: any) => r.type === 'Undertime');
                    
                    // Calculate tardiness totals
                    let tardinessDates: string[] = [];
                    let tardinessHoursTotal = 0;
                    let tardinessMinutesTotal = 0;
                    tardinessRecords.forEach((tu: any) => {
                        const tuDate = new Date(tu.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        if (!tardinessDates.includes(tuDate)) {
                            tardinessDates.push(tuDate);
                        }
                        if (tu.requested_time) {
                            // Use 5:00 PM (17:00) for NWD, otherwise use the specific return time
                            const returnTime = tu.return_time === 'nwd' ? '17:00' : tu.return_time;
                            if (returnTime) {
                                const timeDiff = calculateTimeDifference(tu.requested_time, returnTime);
                                tardinessHoursTotal += timeDiff.hours;
                                tardinessMinutesTotal += timeDiff.minutes;
                            }
                        }
                    });
                    // Normalize minutes
                    tardinessHoursTotal += Math.floor(tardinessMinutesTotal / 60);
                    tardinessMinutesTotal = tardinessMinutesTotal % 60;
                    
                    // Calculate undertime totals
                    let undertimeDates: string[] = [];
                    let undertimeHoursTotal = 0;
                    let undertimeMinutesTotal = 0;
                    undertimeRecords.forEach((tu: any) => {
                        const tuDate = new Date(tu.requested_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        if (!undertimeDates.includes(tuDate)) {
                            undertimeDates.push(tuDate);
                        }
                        if (tu.requested_time) {
                            // Use 5:00 PM (17:00) for NWD, otherwise use the specific return time
                            const returnTime = tu.return_time === 'nwd' ? '17:00' : tu.return_time;
                            if (returnTime) {
                                const timeDiff = calculateTimeDifference(tu.requested_time, returnTime);
                                undertimeHoursTotal += timeDiff.hours;
                                undertimeMinutesTotal += timeDiff.minutes;
                            }
                        }
                    });
                    // Normalize minutes
                    undertimeHoursTotal += Math.floor(undertimeMinutesTotal / 60);
                    undertimeMinutesTotal = undertimeMinutesTotal % 60;
                    
                    // Calculate total
                    let totalHours = tardinessHoursTotal + undertimeHoursTotal;
                    let totalMinutes = tardinessMinutesTotal + undertimeMinutesTotal;
                    totalHours += Math.floor(totalMinutes / 60);
                    totalMinutes = totalMinutes % 60;
                    
                    // Format displays
                    const tardinessDisplay = tardinessRecords.length > 0 
                        ? (tardinessHoursTotal > 0 ? `${tardinessHoursTotal}h ${tardinessMinutesTotal}m` : `${tardinessMinutesTotal}m`)
                        : '';
                    const undertimeDisplay = undertimeRecords.length > 0 
                        ? (undertimeHoursTotal > 0 ? `${undertimeHoursTotal}h ${undertimeMinutesTotal}m` : `${undertimeMinutesTotal}m`)
                        : '';
                    const totalDisplay = (tardinessRecords.length > 0 || undertimeRecords.length > 0)
                        ? (totalHours > 0 ? `${totalHours}h ${totalMinutes}m` : `${totalMinutes}m`)
                        : '';
                    
                    const maxRows = Math.max(emp.leaves.length, emp.passSlips.length, emp.travelOrders.length, 1);
                    return Array.from({length: maxRows}).map((_, rowIdx) => {
                        const leave = emp.leaves[rowIdx];
                        const ps = emp.passSlips[rowIdx];
                        const to = emp.travelOrders[rowIdx];
                        
                        let html = '<tr>';
                        
                        // Add employee ID and name for first row only
                        if (rowIdx === 0) {
                            html += '<td rowspan="' + maxRows + '" style="text-align: center; font-weight: bold; vertical-align: top; padding: 4px 3px; width: 8%;">' + emp.employee_id + '</td>';
                            html += '<td rowspan="' + maxRows + '" style="font-weight: bold; vertical-align: top; padding: 4px 3px; width: 12%;">' + emp.name + '</td>';
                        }
                        
                        // Leave columns
                        html += '<td style="text-align: center; padding: 4px 3px; width: 10%; font-size: 11px;">' + (leave ? leave.type_of_leave : '') + '</td>';
                        let inclusiveDates = leave && leave.inclusive_dates ? formatInclusiveDatesToString(leave.inclusive_dates) : '';
                        // Append AM/PM for half-day leaves
                        if (leave && leave.is_half_day && leave.half_day_period) {
                            inclusiveDates += ' (' + leave.half_day_period + ')';
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 8%; font-size: 11px;">' + inclusiveDates + '</td>';
                        
                        // Travel order columns
                        let toDates = '';
                        if (to) {
                            if (to.inclusive_dates) {
                                toDates = formatInclusiveDatesToString(to.inclusive_dates);
                            } else if (to.from_date && to.to_date) {
                                const fromDate = new Date(to.from_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                const toDate = new Date(to.to_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                toDates = fromDate === toDate ? fromDate : `${fromDate} - ${toDate}`;
                            } else if (to.date) {
                                toDates = new Date(to.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            }
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 8%; font-size: 11px;">' + toDates + '</td>';
                        
                        // Pass slip columns
                        const psDate = ps ? new Date(ps.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + psDate + '</td>';
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + (ps ? formatTime(ps.requested_time || '') : '') + '</td>';
                        
                        // Format return time with remarks
                        let returnTimeDisplay = '';
                        if (ps) {
                            let returnTypeLabel = ps.expected_return_time || '';
                            
                            // Add remarks if they exist
                            if (ps.remarks) {
                                returnTimeDisplay = returnTypeLabel + ' (' + ps.remarks + ')';
                            } else {
                                returnTimeDisplay = returnTypeLabel;
                            }
                        }
                        html += '<td style="text-align: center; padding: 4px 3px; width: 6%; font-size: 11px;">' + returnTimeDisplay + '</td>';
                        
                        // Tardiness/Undertime columns - show only in first row
                        if (rowIdx === 0) {
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + tardinessDates.join(', ') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + tardinessDisplay + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;">' + (tardinessRecords.length > 0 ? tardinessRecords.length : '') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + undertimeDates.join(', ') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + undertimeDisplay + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;">' + (undertimeRecords.length > 0 ? undertimeRecords.length : '') + '</td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;">' + totalDisplay + '</td>';
                        } else {
                            // Empty tardiness/undertime columns for non-first rows
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 4%; font-size: 11px;"></td>';
                            html += '<td style="text-align: center; padding: 4px 3px; width: 5%; font-size: 11px;"></td>';
                        }
                        
                        html += '</tr>';
                        return html;
                    }).join('');
                }).join('');
            })()}
        </tbody>
    </table>
    ` : `
    <table>
        <tbody>
            <tr>
                <td style="text-align: center; padding: 20px;">No data available for the selected criteria</td>
            </tr>
        </tbody>
    </table>
    `}
    
    ${summaryData.value.remarks ? `<p style="margin: 0; font-weight: bold; font-size: 11px;"><strong>*</strong> <em>${summaryData.value.remarks.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</em></p>` : ''}
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Prepared By:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(preparedByEmployee?.name || 'N/A')}</div>
            <div class="signature-designation">${preparedByEmployee?.designation || ''}</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-title">Reviewed/Certified Correct:</div>
            <div class="signature-line"></div>
            <div class="signature-name">${capitalizeWords(certifiedByUser?.name || 'N/A')}</div>
            <div class="signature-designation">${certifiedByEmployee?.designation || ''}</div>
        </div>
    </div>
</body>
</html>
        `;

        // Open print dialog
        const reportWindow = window.open('', 'PRINT_SUMMARY', 'height=900,width=1500');
        if (reportWindow) {
            reportWindow.document.write(htmlContent);
            reportWindow.document.close();
            reportWindow.print();
        }

        showSummaryModal.value = false;
        // Reset form
        summaryData.value = {
            month: null,
            employmentType: '',
            preparedBy: null,
            certifiedCorrect: null,
            remarks: '',
        };
    } catch (e) {
        summaryErrors.value.submit = e instanceof Error ? e.message : 'An error occurred while generating the report';
    }
};

// ============== Watchers ==============
watch(() => showSummaryModal.value, (newVal) => {
    if (newVal) {
        // Set summary month and year to match HR Summary Panel when modal opens
        summaryData.value.month = selectedHRMonth.value + 1; // Convert 0-11 to 1-12
    }
});

/**
 * Watch for HR data loading completion
 * Set hrLoading to false when all three data arrays have been populated
 */
watch(
    [() => leaves.value.length, () => travelOrders.value.length, () => passSlips.value.length],
    ([leavesLen, toLen, psLen]) => {
        if (leavesLen > 0 || toLen > 0 || psLen > 0) {
            // At least one HR data type has loaded, disable loading animation
            hrLoading.value = false;
        }
    }
);

// ============== Lifecycle ==============
onMounted(async () => {
    // Set up real-time updates for Processing Time and Remaining Duration
    timeUpdateInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000); // Update every second
    
    await fetchDocuments();
    await Promise.all([
        fetchLeaves(),
        fetchTravelOrders(),
        fetchPassSlips(),
        fetchTardiness()
    ]);
});

/**
 * onUnmounted: Clean up the time update interval when component is destroyed
 */
onUnmounted(() => {
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
        timeUpdateInterval = null;
    }
});
</script>

<!-- Scoped Styles: Modal animations and transitions -->
<style scoped>
@keyframes scaleInUp {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.animate-scaleInUp {
    animation: scaleInUp 0.3s ease-out;
}

/* HR Loading Animation */
.hr-loading-shimmer {
    animation: shimmer 2s infinite;
    background-size: 1000px 100%;
}
</style>