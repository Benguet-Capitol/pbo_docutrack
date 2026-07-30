<template>
    <Teleport to="body" v-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
            <div class="relative w-full max-w-md mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-calendar-plus text-emerald-600 dark:text-emerald-400"></i>
                        Create Notice of Meeting
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Employee Field (read-only — set by which card/row was clicked) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-user absolute left-3 text-gray-400 text-sm"></i>
                                <input :value="employeeName" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                            </div>
                        </div>

                        <!-- Date Field (read-only — tied to the Locator Chart's selected date) -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-calendar-day absolute left-3 text-gray-400 text-sm"></i>
                                <input :value="dateDisplay" type="text" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                            </div>
                        </div>

                        <!-- Time Field -->
                        <div class="space-y-2">
                            <label for="create_meeting_time" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Time <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-clock absolute left-3 text-gray-400 text-sm"></i>
                                <input
                                    :value="formData.time"
                                    @input="(e) => $emit('update:form-data', { ...formData, time: (e.target as HTMLInputElement).value })"
                                    id="create_meeting_time"
                                    type="time"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.time ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                />
                            </div>
                            <span v-if="formErrors.time" class="text-red-500 text-xs">{{ formErrors.time }}</span>
                        </div>

                        <!-- Particulars Field -->
                        <div class="space-y-2">
                            <label for="create_meeting_particulars" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Particulars <span class="text-red-600">*</span></label>
                            <div class="relative flex items-start">
                                <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea
                                    :value="formData.particulars"
                                    @input="(e) => $emit('update:form-data', { ...formData, particulars: (e.target as HTMLTextAreaElement).value })"
                                    id="create_meeting_particulars"
                                    placeholder="e.g. BAC Meeting, Budget Hearing with Governor's Office, etc."
                                    rows="3"
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none"
                                    :class="[formErrors.particulars ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                ></textarea>
                            </div>
                            <span v-if="formErrors.particulars" class="text-red-500 text-xs">{{ formErrors.particulars }}</span>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                    <button type="submit" @click="$emit('submit')" :disabled="creating" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i v-if="creating" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ creating ? 'Saving...' : 'Create' }}
                    </button>
                    <button @click="$emit('close')" type="button" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
export interface NoticeOfMeetingFormData {
    time: string;
    particulars: string;
}

defineProps<{
    show: boolean;
    employeeName: string;
    dateDisplay: string;
    formData: NoticeOfMeetingFormData;
    formErrors: Record<string, string>;
    creating: boolean;
}>();

defineEmits<{
    'update:form-data': [data: NoticeOfMeetingFormData];
    'close': [];
    'submit': [];
}>();
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