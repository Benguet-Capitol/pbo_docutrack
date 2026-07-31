<template>
    <Toast ref="toastRef" />
    <PageHead />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Locator Chart | <span class="text-blue-700 dark:text-blue-400">Provincial Budget Office</span>
            </h2>
        </template>

        <div
            ref="chartContainerRef"
            class="py-6 px-4 sm:px-6 lg:px-8"
            :class="{ 'bg-gray-50 dark:bg-gray-900 h-screen overflow-y-auto': isFullScreen }"
        >
            <!-- Fullscreen-only heading: AuthenticatedLayout's #header slot isn't
                part of this element, so it disappears in fullscreen — this
                re-shows the same heading, only while fullscreen is active -->
            <div v-if="isFullScreen" class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Locator Chart | <span class="text-blue-700 dark:text-blue-400">Provincial Budget Office</span>
                </h2>
            </div>

            <!-- Locator Chart Section -->
            <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Header Section -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between flex-wrap gap-4">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-map-location-dot text-blue-600 dark:text-blue-400"></i>
                        Date: {{ currentDateDisplay }}
                    </h3>
                    <div class="flex items-center gap-3 flex-wrap print:hidden">
                        <!-- View toggle: grid (compact, no-scroll) vs list (original table) -->
                        <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-xs font-medium">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'px-3 py-2 flex items-center gap-1.5 transition-colors',
                                    viewMode === 'grid'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600'
                                ]"
                                title="Compact grid view — fits more employees on screen"
                            >
                                <i class="fas fa-table-cells"></i>
                                Grid
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'px-3 py-2 flex items-center gap-1.5 transition-colors border-l border-gray-300 dark:border-gray-600',
                                    viewMode === 'list'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600'
                                ]"
                                title="Traditional table view"
                            >
                                <i class="fas fa-list"></i>
                                List
                            </button>
                        </div>

                        <!-- Column count control (grid view only) -->
                        <div v-if="viewMode === 'grid'" class="flex items-center gap-2">
                            <label class="text-xs font-medium text-gray-600 dark:text-white">Columns:</label>
                            <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-xs font-medium">
                                <button
                                    v-for="(col, colIndex) in [2, 3, 4, 6]"
                                    :key="col"
                                    @click="gridColumns = col"
                                    :class="[
                                        'px-2.5 py-2 transition-colors',
                                        gridColumns === col
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600',
                                        colIndex !== 0 ? 'border-l border-gray-300 dark:border-gray-600' : ''
                                    ]"
                                >
                                    {{ col }}
                                </button>
                            </div>
                        </div>

                        <label for="locatorDate" class="text-xs font-medium text-gray-700 dark:text-white">Date:</label>
                        <input
                            type="date"
                            id="locatorDate"
                            v-model="selectedDateInput"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <button
                            v-if="!isSelectedDateToday"
                            @click="resetToToday"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Reset to Today
                        </button>

                        <!-- Auto-refresh controls -->
                        <div class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700">
                            <button
                                @click="toggleAutoRefresh"
                                :title="autoRefreshEnabled ? 'Auto-refresh is on — click to pause' : 'Auto-refresh is paused — click to resume'"
                                class="text-lg leading-none"
                            >
                                <i :class="autoRefreshEnabled ? 'fas fa-toggle-on text-blue-600' : 'fas fa-toggle-off text-gray-400'"></i>
                            </button>
                            <span class="text-xs text-gray-500 dark:text-white font-bold whitespace-nowrap">
                                <span v-if="autoRefreshEnabled" class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse mr-1"></span>
                                Updated {{ lastUpdatedDisplay }}
                            </span>
                            <button @click="refreshAll" title="Refresh now" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                <i class="fas fa-rotate"></i>
                            </button>
                        </div>

                        <!-- Bulk Notice of Meeting -->
                        <div v-if="!isFullScreen" class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700 print:hidden">
                            <button
                                @click="openBulkMeetingModal"
                                class="text-xs px-2.5 py-2 rounded-lg font-bold border border-gray-300 dark:border-gray-600 text-white dark:text-white bg-emerald-600 hover:bg-emerald-700 flex items-center gap-1.5"
                            >
                                <i class="fas fa-users"></i>
                                Bulk Create
                            </button>
                        </div>

                        <!-- Print -->
                        <div v-if="!isFullScreen" class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700">
                            <button
                                @click="printChart"
                                class="text-xs px-2.5 py-2 rounded-lg font-bold border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1.5"
                            >
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>

                        <!-- Full Screen -->
                        <div class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-700">
                            <button
                                @click="toggleFullScreen"
                                :title="isFullScreen ? 'Exit full screen' : 'Full screen view'"
                                class="text-xs px-2.5 py-2 rounded-lg font-bold border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-1.5"
                            >
                                <i :class="isFullScreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                                {{ isFullScreen ? 'Exit' : 'Full Screen' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary strip: quick counts so the user can see totals at a glance -->
                <div v-if="employeesWithStatus.length > 0" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs bg-gray-50/60 dark:bg-gray-800/60 print:hidden">
                    <span class="font-semibold text-gray-600 dark:text-gray-300">{{ employeesWithStatus.length }} total</span>
                    <span v-for="s in statusSummary" :key="s.status" class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                        <span class="w-2 h-2 rounded-full" :class="dotClass(s.status)"></span>
                        {{ s.status }}: <strong>{{ s.count }}</strong>
                    </span>
                </div>

                <!-- Weekend banner: friendlier messaging than a silently all-"Off Day" list -->
                <div
                    v-if="isSelectedDateWeekend"
                    class="mx-6 mt-4 px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2 print:hidden"
                >
                    <i class="fas fa-mug-hot text-gray-400"></i>
                    It's the weekend — listed staff are marked Off Day.
                </div>

                <!-- ============== GRID VIEW (compact, multi-column, minimal scrolling) ============== -->
                <div v-if="viewMode === 'grid'" class="p-4 print:hidden">
                    <div v-if="employeesWithStatus.length > 0" class="flex flex-col gap-6">
                        <div v-for="group in groupedEmployees" :key="group.status">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="dotClass(group.status)"></span>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">{{ group.status }}</h4>
                                <span class="text-xs text-gray-400 dark:text-gray-500">({{ group.employees.length }})</span>
                            </div>
                            <div
                                class="grid gap-3 locator-grid"
                                :style="{ gridTemplateColumns: `repeat(${gridColumns}, minmax(0, 1fr))` }"
                            >
                                <div
                                    v-for="employee in group.employees"
                                    :key="employee.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 hover:shadow-md transition-shadow flex flex-col gap-2 min-w-0"
                                    :class="[recordBgClass(employee.status), (isPresentStatus(employee.status) && !isFullScreen) ? 'cursor-pointer' : '']"
                                    @click="isPresentStatus(employee.status) && !isFullScreen && openCreateMeetingModal({ id: employee.id, name: employee.name })"
                                >
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span
                                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                            :class="statusBadgeClass(employee.status)"
                                        >
                                            {{ getInitials(employee.name) }}
                                        </span>
                                        <div class="flex-1 flex items-start justify-between gap-2 min-w-0">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 dark:text-white text-base truncate" :title="employee.name">
                                                    {{ employee.name }}
                                                </p>
                                                <p v-if="employee.designation" class="text-xs text-gray-500 dark:text-gray-400 truncate" :title="employee.designation">
                                                    {{ employee.designation }}
                                                </p>
                                            </div>
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-bold whitespace-nowrap"
                                                :class="statusBadgeClass(employee.status)"
                                            >
                                                <i :class="statusIconClass(employee.status)"></i>
                                                {{ statusShortLabel(employee.status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            v-if="employee.meetings.length > 0"
                                            @click.stop
                                            class="text-xs px-3 py-2 rounded-lg"
                                            :class="remarksBadgeClass(employee.status)"
                                        >
                                            <p class="font-semibold mb-0.5">Meeting / Activity:</p>
                                            <div class="flex flex-col gap-1 pl-3">
                                                <div v-for="meeting in employee.meetings" :key="meeting.id" class="flex items-center gap-2">
                                                    <span><strong>{{ formatTime12h(meeting.time) }}</strong> - {{ meeting.particulars }}</span>
                                                    <span v-if="!isFullScreen" class="flex items-center gap-1.5 text-gray-400">
                                                        <button
                                                            @click.stop="openEditMeetingModal({ id: employee.id, name: employee.name }, meeting)"
                                                            title="Edit"
                                                            class="hover:text-blue-600 dark:hover:text-blue-400"
                                                        >
                                                            <i class="fas fa-pen text-[10px]"></i>
                                                        </button>
                                                        <button
                                                            @click.stop="openDeleteMeetingModal(employee.name, meeting)"
                                                            title="Delete"
                                                            class="hover:text-red-600"
                                                        >
                                                            <i class="fas fa-trash text-[10px]"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else-if="employee.remarksText" class="text-xs px-3 py-2 rounded-lg" :class="remarksBadgeClass(employee.status)">
                                            <p v-if="employee.remarksLabel" class="font-semibold mb-0.5">{{ employee.remarksLabel }}:</p>
                                            <p :class="[employee.remarksLabel ? 'pl-3' : '', employee.status === 'On Leave' ? 'font-bold' : '']">{{ employee.remarksText }}</p>
                                        </div>
                                        <span v-else class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Data State -->
                    <div v-else-if="!isSelectedDateWeekend" class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-md">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No employees found</p>
                    </div>
                </div>

                <!-- ============== LIST VIEW (original single-column table) ============== -->
                <div v-else class="overflow-x-auto print:hidden">
                    <table class="w-full text-sm table-fixed">
                        <colgroup>
                            <col class="w-1/3">
                            <col class="w-1/6">
                            <col class="w-auto">
                        </colgroup>
                        <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Employee Name</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="employee in employeesWithStatus"
                                :key="employee.id"
                                class="transition-colors hover:brightness-95 dark:hover:brightness-110"
                                :class="[recordBgClass(employee.status), (isPresentStatus(employee.status) && !isFullScreen) ? 'cursor-pointer' : '']"
                                @click="isPresentStatus(employee.status) && !isFullScreen && openCreateMeetingModal({ id: employee.id, name: employee.name })"
                            >
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                            :class="statusBadgeClass(employee.status)"
                                        >
                                            {{ getInitials(employee.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-base font-bold truncate">{{ employee.name }}</p>
                                            <p v-if="employee.designation" class="text-xs font-normal text-gray-500 dark:text-gray-400 truncate">
                                                {{ employee.designation }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300 text-center">
                                    <span v-if="employee.status === 'Present with Meetings'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300">
                                        <i class="fas fa-comments"></i>
                                        Present w/ Meeting
                                    </span>
                                    <span v-else-if="isPresentStatus(employee.status)" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check-circle"></i>
                                        Present
                                    </span>
                                    <span v-else-if="employee.status === 'On Official Business'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-briefcase"></i>
                                        On Official Business
                                    </span>
                                    <span v-else-if="employee.status === 'On Leave'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                        <i class="fas fa-calendar-check"></i>
                                        On Leave
                                    </span>
                                    <span v-else-if="employee.status === 'Undertime'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        <i class="fas fa-hourglass-end"></i>
                                        Undertime
                                    </span>
                                    <span v-else-if="employee.status === 'Off Day'" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-bed"></i>
                                        Off Day
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    <div
                                        v-if="employee.meetings.length > 0"
                                        @click.stop
                                        class="text-sm px-3 py-2 rounded-lg"
                                        :class="remarksBadgeClass(employee.status)"
                                    >
                                        <p class="font-semibold mb-0.5">Meeting / Activity:</p>
                                        <div class="flex flex-col gap-1 pl-3">
                                            <div v-for="meeting in employee.meetings" :key="meeting.id" class="flex items-center gap-2">
                                                <span><strong>{{ formatTime12h(meeting.time) }}</strong> - {{ meeting.particulars }}</span>
                                                <span v-if="!isFullScreen" class="flex items-center gap-1.5 text-gray-400">
                                                    <button
                                                        @click.stop="openEditMeetingModal({ id: employee.id, name: employee.name }, meeting)"
                                                        title="Edit"
                                                        class="hover:text-blue-600 dark:hover:text-blue-400"
                                                    >
                                                        <i class="fas fa-pen text-[10px]"></i>
                                                    </button>
                                                    <button
                                                        @click.stop="openDeleteMeetingModal(employee.name, meeting)"
                                                        title="Delete"
                                                        class="hover:text-red-600"
                                                    >
                                                        <i class="fas fa-trash text-[10px]"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="employee.remarksText" class="text-sm px-3 py-2 rounded-lg" :class="remarksBadgeClass(employee.status)">
                                        <p v-if="employee.remarksLabel" class="font-semibold mb-0.5">{{ employee.remarksLabel }}:</p>
                                        <p :class="[employee.remarksLabel ? 'pl-3' : '', employee.status === 'On Leave' ? 'font-bold' : '']">{{ employee.remarksText }}</p>
                                    </div>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- No Data State -->
                    <div v-if="employeesWithStatus.length === 0 && !isSelectedDateWeekend" class="text-center py-12 bg-gray-50 dark:bg-gray-700">
                        <i class="fas fa-inbox text-gray-400 dark:text-gray-600 text-4xl mb-3 block"></i>
                        <p class="text-gray-600 dark:text-gray-400">No employees found</p>
                    </div>
                </div>

                <!-- ============== PRINT-ONLY VIEW (formal government form layout) ============== -->
                <div class="hidden print:block locator-print-area px-8 py-6 text-black">
                    <!-- Header Section with Logos -->
                    <div class="flex items-center justify-center gap-2 pb-2" style="border-bottom: 3px double #050505;">
                        <div style="width: 85px; flex-shrink: 0;">
                            <img src="/benguetlogo.png" alt="Benguet Logo" style="width: 100%; height: auto;">
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-gray-700 mt-2">Republic of the Philippines</p>
                            <p class="text-sm font-bold text-gray-900">PROVINCE OF BENGUET</p>
                            <p class="text-lg font-bold text-gray-900">PROVINCIAL BUDGET OFFICE</p>
                            <p class="text-sm text-gray-700 mb-1">Poblacion, La Trinidad, Benguet 2601</p>
                        </div>
                        <div style="width: 85px; flex-shrink: 0;">
                            <img src="/bagongpilipinaslogo.png" alt="Bagong Pilipinas Logo" style="width: 100%; height: auto;">
                        </div>
                    </div>
                    <div class="text-center mb-5 leading-snug">
                        <p class="text-lg font-bold uppercase tracking-wide mt-2">Daily Locator Chart</p>
                        <p class="text-sm text-gray-700">{{ currentDateDisplay }}</p>
                    </div>

                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="border border-black px-2 py-1.5 text-left w-12">No.</th>
                                <th class="border border-black px-2 py-1.5 text-left">Employee Name</th>
                                <th class="border border-black px-2 py-1.5 text-left w-40">Status</th>
                                <th class="border border-black px-2 py-1.5 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(employee, idx) in employeesWithStatus" :key="`print-${employee.id}`">
                                <td class="border border-black px-2 py-1 align-top">{{ idx + 1 }}</td>
                                <td class="border border-black px-2 py-1 align-top">
                                    <div class="font-medium">{{ employee.name }}</div>
                                    <div v-if="employee.designation" class="text-xs text-gray-600">{{ employee.designation }}</div>
                                </td>
                                <td class="border border-black px-2 py-1 align-top">{{ printStatusLabel(employee.status) }}</td>
                                <td class="border border-black px-2 py-1 align-top">
                                    <template v-if="employee.meetings.length > 0">
                                        <div v-for="meeting in employee.meetings" :key="`print-meeting-${meeting.id}`">
                                            <strong>{{ formatTime12h(meeting.time) }}</strong> - {{ meeting.particulars }}
                                        </div>
                                    </template>
                                    <template v-else-if="employee.remarksText">
                                        <strong v-if="employee.remarksLabel">{{ employee.remarksLabel }}:</strong>&nbsp; {{ employee.remarksText }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                            </tr>
                            <tr v-if="employeesWithStatus.length === 0">
                                <td colspan="4" class="border border-black px-2 py-3 text-center">No employees found</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="text-xs mt-2">
                        Total: {{ employeesWithStatus.length }}<span v-for="s in statusSummary" :key="`printsum-${s.status}`"> &nbsp;|&nbsp; {{ printStatusLabel(s.status) }}: {{ s.count }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ============== Notice of Meeting Modals (extracted components) ============== -->
        <NoticeOfMeetingCreateModal
            :show="showMeetingModal && !editingMeeting"
            :employee-name="meetingModalEmployee?.name || ''"
            :date-display="currentDateDisplay"
            :form-data="meetingForm"
            :form-errors="meetingFormErrors"
            :creating="meetingFormSubmitting"
            @update:form-data="(data) => (meetingForm = data)"
            @close="closeMeetingModal"
            @submit="submitMeetingForm"
        />

        <NoticeOfMeetingEditModal
            :show="showMeetingModal && !!editingMeeting"
            :meeting-to-edit="editingMeeting"
            :employee-name="meetingModalEmployee?.name || ''"
            :date-display="currentDateDisplay"
            :form-data="meetingForm"
            :form-errors="meetingFormErrors"
            :updating="meetingFormSubmitting"
            @update:form-data="(data) => (meetingForm = data)"
            @close="closeMeetingModal"
            @submit="submitMeetingForm"
        />

        <NoticeOfMeetingDeleteModal
            :show="showDeleteMeetingModal"
            :meeting-to-delete="meetingToDelete"
            :employee-name="deleteMeetingEmployeeName"
            :deleting="deletingMeeting"
            @close="closeDeleteMeetingModal"
            @confirm="confirmDeleteMeeting"
        />
        <NoticeOfMeetingBulkCreateModal
            :show="showBulkMeetingModal"
            :employees="employees"
            :form-data="bulkMeetingForm"
            :form-errors="bulkMeetingFormErrors"
            :creating="bulkMeetingFormSubmitting"
            @update:form-data="(data) => (bulkMeetingForm = data)"
            @close="closeBulkMeetingModal"
            @submit="submitBulkMeetingForm"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Toast from '@/Components/Toast.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import NoticeOfMeetingCreateModal from './Modals/CreateModal.vue';
import NoticeOfMeetingEditModal from './Modals/EditModal.vue';
import NoticeOfMeetingDeleteModal from './Modals/DeleteModal.vue';
import NoticeOfMeetingBulkCreateModal from './Modals/BulkCreateModal.vue';

// ============== Data ==============
const employees = ref<Array<{ id: number; employee_id: string; name: string; office_id: number; designation: string }>>([]);
const leaves = ref<any[]>([]);
const travelOrders = ref<any[]>([]);
const passSlips = ref<any[]>([]);
const tardiness = ref<any[]>([]);
const noticeOfMeetings = ref<Array<{ id: number; employee_id: number; date: string; time: string; particulars: string }>>([]);

// Toast ref
const toastRef = ref<InstanceType<typeof Toast> | null>(null);

// Toast function
const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    if (type === 'success') {
        toastRef.value?.add('success', 'Success', message, 3000);
    } else if (type === 'error') {
        toastRef.value?.add('error', 'Error', message, 4000);
    } else {
        toastRef.value?.add('info', 'Info', message, 3000);
    }
};

// Grid = compact multi-column cards (default, minimizes scrolling for large rosters).
// List = original single-column table.
const viewMode = ref<'grid' | 'list'>('grid');

// Number of columns in the Grid view — user-adjustable.
const gridColumns = ref<number>(4);

// The date the Locator Chart is being viewed for — defaults to today,
// but the user can pick a different date to see historical/future records.
const selectedDate = ref<Date>(new Date());

// True once the user has picked a specific date themselves — while false,
// selectedDate is kept in sync with the real "today" (see the date-rollover
// check below), so leaving the chart open overnight rolls it to the new day.
const isDateManuallySet = ref<boolean>(false);

// ============== Auto-refresh ==============
const autoRefreshEnabled = ref<boolean>(true);
const lastUpdatedAt = ref<Date | null>(null);
const REFRESH_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes
let refreshTimer: ReturnType<typeof setInterval> | null = null;

const startAutoRefresh = () => {
    stopAutoRefresh();
    refreshTimer = setInterval(() => {
        refreshAll();
    }, REFRESH_INTERVAL_MS);
};

const stopAutoRefresh = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

const toggleAutoRefresh = () => {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
};

const lastUpdatedDisplay = computed(() => {
    if (!lastUpdatedAt.value) return '—';
    return lastUpdatedAt.value.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
});

// ============== Full Screen ==============
const chartContainerRef = ref<HTMLElement | null>(null);
const isFullScreen = ref<boolean>(false);

const toggleFullScreen = async () => {
    if (!chartContainerRef.value) return;
    try {
        if (!document.fullscreenElement) {
            await chartContainerRef.value.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    } catch (e) {
        console.error('Fullscreen toggle failed:', e);
    }
};

const handleFullScreenChange = () => {
    isFullScreen.value = !!document.fullscreenElement;
};

// ============== Date rollover ==============

// Checks whether the real-world date has moved past selectedDate and,
// if the user hasn't manually chosen a date, advances selectedDate to
// match — so leaving the chart open across midnight shows the new day
// without needing a manual refresh or navigation.
let dateRolloverTimer: ReturnType<typeof setInterval> | null = null;
const DATE_ROLLOVER_CHECK_MS = 30 * 1000; // 30 seconds

const checkForDateRollover = () => {
    if (isDateManuallySet.value) return;

    const now = new Date();
    const hasDayChanged =
        now.getDate() !== selectedDate.value.getDate() ||
        now.getMonth() !== selectedDate.value.getMonth() ||
        now.getFullYear() !== selectedDate.value.getFullYear();

    if (hasDayChanged) {
        selectedDate.value = now;
    }
};

// Bridges the <input type="date"> (which needs "YYYY-MM-DD" strings)
// to/from the selectedDate Date object.
const selectedDateInput = computed({
    get: () => {
        const y = selectedDate.value.getFullYear();
        const m = (selectedDate.value.getMonth() + 1).toString().padStart(2, '0');
        const d = selectedDate.value.getDate().toString().padStart(2, '0');
        return `${y}-${m}-${d}`;
    },
    set: (value: string) => {
        if (!value) return;
        const [y, m, d] = value.split('-').map(Number);
        selectedDate.value = new Date(y, m - 1, d);
        isDateManuallySet.value = true;
    }
});

const isSelectedDateToday = computed(() => {
    const today = new Date();
    return selectedDate.value.getDate() === today.getDate() &&
           selectedDate.value.getMonth() === today.getMonth() &&
           selectedDate.value.getFullYear() === today.getFullYear();
});

const resetToToday = () => {
    selectedDate.value = new Date();
    isDateManuallySet.value = false;
};

// ============== Computed Properties ==============

/**
 * Display label for the currently selected date (e.g., "July 20, 2026")
 */
const currentDateDisplay = computed(() => {
    return selectedDate.value.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

/**
 * Check if the selected date is a Saturday or Sunday.
 */
const isSelectedDateWeekend = computed(() => {
    const day = selectedDate.value.getDay();
    return day === 0 || day === 6;
});

/**
 * Check if a date matches the selected date
 */
const isDateSelected = (dateStr: string): boolean => {
    const date = new Date(dateStr);
    const target = selectedDate.value;
    return date.getDate() === target.getDate() &&
           date.getMonth() === target.getMonth() &&
           date.getFullYear() === target.getFullYear();
};

/**
 * Strip the time component from a Date, leaving just the calendar date
 * at local midnight — needed so range comparisons aren't affected by
 * what time of day "now" happens to be.
 */
const toDateOnly = (date: Date): Date => {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
};

/**
 * Check if a date range includes the selected date (date-only comparison)
 */
const isDateRangeSelected = (dateRangeStr: string): boolean => {
    if (!dateRangeStr) return false;

    if (dateRangeStr.includes(' - ')) {
        const [startStr, endStr] = dateRangeStr.split(' - ');
        const startDate = toDateOnly(new Date(startStr.trim()));
        const endDate = toDateOnly(new Date(endStr.trim()));
        const target = toDateOnly(selectedDate.value);

        return target >= startDate && target <= endDate;
    } else {
        return isDateSelected(dateRangeStr.trim());
    }
};

/**
 * Check if the selected date falls within inclusive_dates array
 */
const isSelectedDateInInclusiveDates = (inclusiveDates: string[] | undefined): boolean => {
    if (!inclusiveDates || !Array.isArray(inclusiveDates)) return false;

    for (const dateEntry of inclusiveDates) {
        if (isDateRangeSelected(dateEntry)) {
            return true;
        }
    }
    return false;
};

/**
 * Designations that should always be pinned to the top of the roster,
 * ahead of the alphabetical-by-last-name ordering applied to everyone else.
 */
const PRIORITY_DESIGNATIONS = new Set([
    'Provincial Budget Officer',
    'Provincial Government Department Head (Provincial Budget Officer)'
]);

/**
 * Common name suffixes to exclude when identifying the last name.
 */
const NAME_SUFFIXES = new Set(['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v']);

/**
 * Extract the last name from a "Firstname M. LastName [Suffix]" formatted
 * name, ignoring generational suffixes so "Avelino B. Cayat Jr." sorts by
 * "Cayat", not "Jr."
 */
const getLastName = (fullName: string): string => {
    const parts = fullName.trim().split(/\s+/);
    if (parts.length === 0) return fullName;

    let lastIndex = parts.length - 1;
    const lastToken = parts[lastIndex].toLowerCase().replace(/\.$/, '');

    if (NAME_SUFFIXES.has(lastToken) && parts.length > 1) {
        lastIndex -= 1;
    }

    return parts[lastIndex] || fullName;
};

/**
 * Initials for the avatar circle, e.g. "Avelino D. Cayat Jr." -> "AC",
 * skipping generational suffixes so they don't get used as the last initial.
 */
const getInitials = (fullName: string): string => {
    const parts = fullName.trim().split(/\s+/).filter(
        p => !NAME_SUFFIXES.has(p.toLowerCase().replace(/\.$/, ''))
    );
    if (parts.length === 0) return '?';
    const first = parts[0]?.[0] || '';
    const last = parts[parts.length - 1]?.[0] || first;
    return (first + last).toUpperCase();
};

/**
 * Formats a "HH:mm" 24-hour time string as 12-hour with AM/PM, e.g.
 * "14:30" -> "2:30 PM". Shared by both the undertime remarks and the
 * Notice of Meetings remarks.
 */
const formatTime12h = (timeStr: string): string => {
    if (!timeStr) return '';
    const [hoursStr, minutesStr] = timeStr.split(':');
    let hours = parseInt(hoursStr, 10);
    const period = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours.toString().padStart(2, '0')}:${minutesStr} ${period}`;
};

/**
 * "Present" and "Present with Meetings" both mean the employee is at
 * work — used to gate click-to-add-meeting behavior and the List view's
 * Status badge across both variants.
 */
const isPresentStatus = (status: string): boolean =>
    status === 'Present' || status === 'Present with Meetings';

/**
 * Print sheet always says plain "Present" regardless of whether the
 * employee has meetings scheduled — the meeting details already show
 * in the Remarks column, so the Status column stays simple on paper.
 */
const printStatusLabel = (status: string): string =>
    status === 'Present with Meetings' ? 'Present with Meeting' : status;

/**
 * Notice of Meeting records for an employee on the selected date, sorted
 * by time. Kept as objects (not a joined string) so each entry can carry
 * its own edit/delete controls in the interactive views.
 */
const getEmployeeMeetings = (employeeId: number): Array<{ id: number; time: string; particulars: string }> => {
    return noticeOfMeetings.value
        .filter(m => m.employee_id === employeeId && isDateSelected(m.date))
        .sort((a, b) => a.time.localeCompare(b.time))
        .map(m => ({ id: m.id, time: m.time, particulars: m.particulars }));
};

/**
 * Get employee status, remarks, and any Notice of Meetings for the
 * selected date. `meetings` is only ever populated on the "Present with
 * Meetings" branch — every other branch returns an empty array so the
 * template's `employee.meetings` is always safe to read regardless of
 * status.
 */
const getEmployeeStatusAndRemarks = (
    employee: any
): { status: string; remarksLabel: string; remarksText: string; meetings: Array<{ id: number; time: string; particulars: string }> } => {
    // Weekends override everything else — nobody's "Present" or "On Leave"
    // on a non-working day, the day itself is the reason.
    if (isSelectedDateWeekend.value) {
        return { status: 'Off Day', remarksLabel: '', remarksText: '', meetings: [] };
    }
    const employeeId = employee.id;

    // Check for undertime first
    const undertime = tardiness.value.find(t =>
        t.employee_id === employeeId && isDateSelected(t.date_filed)
    );
    if (undertime) {
        return {
            status: 'Undertime',
            remarksLabel: '',
            remarksText: `${undertime.reason} (${formatTime12h(undertime.requested_time)} - ${formatTime12h(undertime.return_time)})`,
            meetings: []
        };
    }

    // Check for leave
    const leave = leaves.value.find(l =>
        l.employee_id === employeeId && isSelectedDateInInclusiveDates(l.inclusive_dates)
    );
    if (leave) {
        return {
            status: 'On Leave',
            remarksLabel: '',
            remarksText: `${leave.type_of_leave}`,
            meetings: []
        };
    }

    // Check for pass slip
    const passSlip = passSlips.value.find(ps => {
        if (!ps.employees || !Array.isArray(ps.employees)) return false;
        const isEmployeeInPS = ps.employees.some((emp: any) => emp.id === employeeId);
        if (!isEmployeeInPS) return false;

        return isSelectedDateInInclusiveDates(ps.inclusive_dates);
    });

    if (passSlip) {
        return {
            status: 'On Official Business',
            remarksLabel: 'Pass Slip',
            remarksText: `${passSlip.purpose} at ${passSlip.location}`,
            meetings: []
        };
    }

    // Check for travel order
    const travelOrder = travelOrders.value.find(to => {
        if (!to.employees || !Array.isArray(to.employees)) return false;
        const isEmployeeInTO = to.employees.some((emp: any) => emp.id === employeeId);
        if (!isEmployeeInTO) return false;

        return isSelectedDateInInclusiveDates(to.inclusive_dates);
    });

    if (travelOrder) {
        return {
            status: 'On Official Business',
            remarksLabel: 'Travel Order',
            remarksText: `${Array.isArray(travelOrder.purpose) ? travelOrder.purpose.join(', ') : travelOrder.purpose} at ${travelOrder.going_to}`,
            meetings: []
        };
    }

    // Notice of Meetings — only reached once nothing above matched.
    const meetings = getEmployeeMeetings(employeeId);
    if (meetings.length === 0) {
        return { status: 'Present', remarksLabel: '', remarksText: '', meetings: [] };
    }
    return {
        status: 'Present with Meetings',
        remarksLabel: '',
        remarksText: meetings.map(m => `${formatTime12h(m.time)}: ${m.particulars}`).join('; '),
        meetings
    };
};

// ============== Notice of Meeting: create/edit modal state ==============
const showMeetingModal = ref(false);
const editingMeeting = ref<{ id: number; time: string; particulars: string } | null>(null);
const meetingModalEmployee = ref<{ id: number; name: string } | null>(null);
const meetingForm = ref({ time: '', particulars: '' });
const meetingFormErrors = ref<{ time?: string; particulars?: string; submit?: string }>({});
const meetingFormSubmitting = ref(false);

const openCreateMeetingModal = (employee: { id: number; name: string }) => {
    editingMeeting.value = null;
    meetingModalEmployee.value = employee;
    meetingForm.value = { time: '', particulars: '' };
    meetingFormErrors.value = {};
    showMeetingModal.value = true;
};

const openEditMeetingModal = (employee: { id: number; name: string }, meeting: { id: number; time: string; particulars: string }) => {
    editingMeeting.value = meeting;
    meetingModalEmployee.value = employee;
    meetingForm.value = { time: meeting.time.slice(0, 5), particulars: meeting.particulars };
    meetingFormErrors.value = {};
    showMeetingModal.value = true;
};

const closeMeetingModal = () => {
    showMeetingModal.value = false;
    editingMeeting.value = null;
    meetingModalEmployee.value = null;
};

const getCsrfToken = (): string =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const submitMeetingForm = async () => {
    if (!meetingModalEmployee.value) return;
    meetingFormSubmitting.value = true;
    meetingFormErrors.value = {};

    const payload = {
        employee_id: meetingModalEmployee.value.id,
        date: selectedDateInput.value,
        time: meetingForm.value.time,
        particulars: meetingForm.value.particulars,
    };

    try {
        const url = editingMeeting.value
            ? `/api/notice-of-meetings/${editingMeeting.value.id}`
            : '/api/notice-of-meetings';
        const response = await fetch(url, {
            method: editingMeeting.value ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (response.status === 422) {
            const err = await response.json();
            meetingFormErrors.value = {
                time: err.errors?.time?.[0],
                particulars: err.errors?.particulars?.[0],
            };
            return;
        }

        if (!response.ok) {
            meetingFormErrors.value = { submit: 'Something went wrong. Please try again.' };
            showToast('Failed to save the meeting. Please try again.', 'error');
            return;
        }

        await fetchNoticeOfMeetings();
        closeMeetingModal();
        showToast(editingMeeting.value ? 'Meeting updated successfully.' : 'Meeting added successfully.', 'success');
    } catch (e) {
        console.error('Error saving notice of meeting:', e);
        meetingFormErrors.value = { submit: 'Something went wrong. Please try again.' };
        showToast('Something went wrong while saving the meeting.', 'error');
    } finally {
        meetingFormSubmitting.value = false;
    }
};

// ============== Notice of Meeting: delete modal state ==============
const showDeleteMeetingModal = ref(false);
const meetingToDelete = ref<{ id: number; time: string; particulars: string } | null>(null);
const deleteMeetingEmployeeName = ref('');
const deletingMeeting = ref(false);

const openDeleteMeetingModal = (employeeName: string, meeting: { id: number; time: string; particulars: string }) => {
    meetingToDelete.value = meeting;
    deleteMeetingEmployeeName.value = employeeName;
    showDeleteMeetingModal.value = true;
};

const closeDeleteMeetingModal = () => {
    showDeleteMeetingModal.value = false;
    meetingToDelete.value = null;
};

const confirmDeleteMeeting = async () => {
    if (!meetingToDelete.value) return;
    deletingMeeting.value = true;
    try {
        const response = await fetch(`api/notice-of-meetings/${meetingToDelete.value.id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        });
        if (response.ok) {
            await fetchNoticeOfMeetings();
            closeDeleteMeetingModal();
            showToast('Meeting deleted successfully.', 'success');
        } else {
            showToast('Failed to delete the meeting.', 'error');
        }
    } catch (e) {
        console.error('Error deleting notice of meeting:', e);
        showToast('Something went wrong while deleting the meeting.', 'error');
    } finally {
        deletingMeeting.value = false;
    }
};

const remarksBadgeClass = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300';
        case 'On Leave':
            return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
        case 'On Official Business':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'Undertime':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        case 'Off Day':
            return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
        case 'Present':
        default:
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
    }
};

// ============== Notice of Meeting: bulk create modal state ==============
const showBulkMeetingModal = ref(false);
const bulkMeetingForm = ref<{ employee_ids: number[]; dates: string[]; time: string; particulars: string }>({
    employee_ids: [],
    dates: [],
    time: '',
    particulars: '',
});
const bulkMeetingFormErrors = ref<{ employee_ids?: string; dates?: string; time?: string; particulars?: string; submit?: string }>({});
const bulkMeetingFormSubmitting = ref(false);

const openBulkMeetingModal = () => {
    // Pre-seed with the date currently being viewed, since that's the most likely intent.
    bulkMeetingForm.value = { employee_ids: [], dates: [selectedDateInput.value], time: '', particulars: '' };
    bulkMeetingFormErrors.value = {};
    showBulkMeetingModal.value = true;
};

const closeBulkMeetingModal = () => {
    showBulkMeetingModal.value = false;
};

const submitBulkMeetingForm = async () => {
    bulkMeetingFormSubmitting.value = true;
    bulkMeetingFormErrors.value = {};

    try {
        const response = await fetch('/api/notice-of-meetings/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(bulkMeetingForm.value),
        });

        if (response.status === 422) {
            const err = await response.json();
            bulkMeetingFormErrors.value = {
                employee_ids: err.errors?.employee_ids?.[0],
                dates: err.errors?.dates?.[0],
                time: err.errors?.time?.[0],
                particulars: err.errors?.particulars?.[0],
            };
            return;
        }

        if (!response.ok) {
            bulkMeetingFormErrors.value = { submit: 'Something went wrong. Please try again.' };
            showToast('Failed to save the meetings. Please try again.', 'error');
            return;
        }

        const result = await response.json();
        await fetchNoticeOfMeetings();
        closeBulkMeetingModal();
        showToast(`${result.created} Notice(s) of Meeting added successfully.`, 'success');
    } catch (e) {
        console.error('Error saving bulk notice of meetings:', e);
        bulkMeetingFormErrors.value = { submit: 'Something went wrong. Please try again.' };
        showToast('Something went wrong while saving the meetings.', 'error');
    } finally {
        bulkMeetingFormSubmitting.value = false;
    }
};

/**
 * Compact badge classes for the grid view cards (same color language as
 * the list view, just reused under a different name for clarity). Also
 * reused for the avatar initials circle.
 */
const statusBadgeClass = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300';
        case 'On Leave':
            return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
        case 'On Official Business':
            return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'Undertime':
            return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
        case 'Off Day':
            return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
        case 'Present':
        default:
            return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
    }
};

const statusIconClass = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'fas fa-comments';
        case 'On Leave':
            return 'fas fa-calendar-check';
        case 'On Official Business':
            return 'fas fa-briefcase';
        case 'Undertime':
            return 'fas fa-hourglass-end';
        case 'Off Day':
            return 'fas fa-bed';
        case 'Present':
        default:
            return 'fas fa-check-circle';
    }
};

/**
 * Shorter labels for the compact grid card badges, so the badge doesn't
 * dominate a narrow card. "On Official Business" -> "On Official Business".
 */
const statusShortLabel = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'Present w/ Meeting';
        case 'On Official Business':
            return 'On Official Business';
        default:
            return status;
    }
};

const dotClass = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'bg-teal-500';
        case 'On Leave':
            return 'bg-orange-500';
        case 'On Official Business':
            return 'bg-blue-500';
        case 'Undertime':
            return 'bg-red-500';
        case 'Off Day':
            return 'bg-gray-500';
        case 'Present':
        default:
            return 'bg-green-500';
    }
};

/**
 * Subtle status-tinted background for a whole record (grid card or table
 * row) — light enough to still read as "white-ish" so it blends with the
 * existing card/table styling rather than competing with the status badge.
 */
const recordBgClass = (status: string): string => {
    switch (status) {
        case 'Present with Meetings':
            return 'bg-teal-50/60 dark:bg-teal-900/10';
        case 'On Leave':
            return 'bg-orange-50/60 dark:bg-orange-900/10';
        case 'On Official Business':
            return 'bg-blue-50/60 dark:bg-blue-900/10';
        case 'Undertime':
            return 'bg-red-50/60 dark:bg-red-900/10';
        case 'Off Day':
            return 'bg-gray-50/80 dark:bg-gray-700/30';
        case 'Present':
        default:
            return 'bg-green-50/40 dark:bg-green-900/10';
    }
};

/**
 * Fixed display order for status groups in the Grid view. Present with
 * Meetings surfaces first so scheduled meetings are the most visible thing
 * on the chart.
 */
const STATUS_ORDER = ['Present with Meetings', 'On Official Business', 'On Leave', 'Undertime', 'Present', 'Off Day'];

/**
 * Set of employee IDs that have an active leave, pass slip, travel order,
 * or tardiness record today — used to filter the full employee roster
 * down to only those who should appear in the Locator Chart.
 */
const employeeIdsWithAnyRecord = computed(() => {
    const ids = new Set<number>();

    tardiness.value.forEach(t => {
        ids.add(t.employee_id);
    });

    leaves.value.forEach(l => {
        ids.add(l.employee_id);
    });

    passSlips.value.forEach(ps => {
        ids.add(ps.employee_id);
    });

    travelOrders.value.forEach(to => {
        if (to.employees && Array.isArray(to.employees)) {
            to.employees.forEach((emp: any) => ids.add(emp.id));
        }
    });

    noticeOfMeetings.value.forEach(m => {
        if (isDateSelected(m.date)) {
            ids.add(m.employee_id);
        }
    });

    return ids;
});

/**
 * Get all employees with their status, remarks, and meetings for the
 * selected date — filtered to only those with an active record in one of
 * the four sources. The Provincial Budget Officer (or department head
 * holding that role) is always pinned first; everyone else follows
 * alphabetically by last name.
 */
const employeesWithStatus = computed(() => {
    return employees.value
        .filter(employee => employeeIdsWithAnyRecord.value.has(employee.id))
        .map(employee => {
            const { status, remarksLabel, remarksText, meetings } = getEmployeeStatusAndRemarks(employee);
            return {
                ...employee,
                status,
                remarksLabel,
                remarksText,
                meetings
            };
        })
        .sort((a, b) => {
            const aIsPriority = PRIORITY_DESIGNATIONS.has(a.designation);
            const bIsPriority = PRIORITY_DESIGNATIONS.has(b.designation);

            if (aIsPriority && !bIsPriority) return -1;
            if (!aIsPriority && bIsPriority) return 1;

            const lastNameCompare = getLastName(a.name).localeCompare(getLastName(b.name));
            if (lastNameCompare !== 0) return lastNameCompare;
            return a.name.localeCompare(b.name); // tiebreaker on full name
        });
});

/**
 * Counts per status, used by the summary strip above the chart.
 */
const statusSummary = computed(() => {
    const counts = new Map<string, number>();
    employeesWithStatus.value.forEach(e => {
        counts.set(e.status, (counts.get(e.status) || 0) + 1);
    });
    return Array.from(counts.entries()).map(([status, count]) => ({ status, count }));
});

/**
 * Employees grouped by status, in a fixed display order, for the Grid
 * view's sectioned headers. Relative order within each group is preserved
 * from employeesWithStatus (priority designation first, then last name).
 */
const groupedEmployees = computed(() => {
    return STATUS_ORDER
        .map(status => ({
            status,
            employees: employeesWithStatus.value.filter(e => e.status === status)
        }))
        .filter(group => group.employees.length > 0);
});

// ============== Print ==============

const printChart = () => {
    window.print();
};

// ============== Fetch Data ==============

/**
 * Fetch employees from API
 */
const fetchEmployees = async () => {
    try {
        const response = await fetch('/api/employees');
        if (response.ok) {
            employees.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching employees:', e);
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

/**
 * Fetch notice of meetings from API
 */
const fetchNoticeOfMeetings = async () => {
    try {
        const response = await fetch('/api/notice-of-meetings');
        if (response.ok) {
            noticeOfMeetings.value = await response.json();
        }
    } catch (e) {
        console.error('Error fetching notice of meetings:', e);
    }
};

/**
 * Fetch all data sources and stamp the last-updated time. Used for the
 * initial load, the manual refresh button, the auto-refresh timer, and
 * whenever the selected date changes.
 */
const refreshAll = async () => {
    await Promise.all([
        fetchEmployees(),
        fetchLeaves(),
        fetchTravelOrders(),
        fetchPassSlips(),
        fetchTardiness(),
        fetchNoticeOfMeetings()
    ]);
    lastUpdatedAt.value = new Date();
};

// Re-fetch everything whenever the viewed date changes — whether the user
// picked a different date or the automatic rollover check above advanced
// it to a new day — so the chart reflects the latest records for that
// date without needing a page reload. Skipped on the very first run since
// onMounted already does the initial fetch.
watch(selectedDate, () => {
    refreshAll();
});

/**
 * Initialize: Fetch all data on mount, start auto-refresh if enabled,
 * start the date-rollover check, and listen for fullscreen changes
 * (covers Esc-to-exit, not just the toolbar button).
 */
onMounted(async () => {
    await refreshAll();
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    }
    dateRolloverTimer = setInterval(checkForDateRollover, DATE_ROLLOVER_CHECK_MS);
    document.addEventListener('fullscreenchange', handleFullScreenChange);
});

onUnmounted(() => {
    stopAutoRefresh();
    if (dateRolloverTimer) {
        clearInterval(dateRolloverTimer);
        dateRolloverTimer = null;
    }
    document.removeEventListener('fullscreenchange', handleFullScreenChange);
});
</script>

<style>
/* Printing must show ONLY the government-form sheet above — nothing else
   on the page, including the app's TopNavigation/Header/Footer that live
   outside this component in AuthenticatedLayout. This must be a global
   (non-scoped) style, since scoped styles can't reach ancestors like
   <body> or sibling layout markup outside this component. Hide everything
   on the page by default and re-reveal just the print sheet and its
   descendants; this works no matter what markup wraps it. */
@media print {
    body * {
        visibility: hidden;
    }
    .locator-print-area,
    .locator-print-area * {
        visibility: visible;
    }
    .locator-print-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
    @page {
        margin: 1.5cm;
    }
    table tr {
        break-inside: avoid;
    }
}
</style>