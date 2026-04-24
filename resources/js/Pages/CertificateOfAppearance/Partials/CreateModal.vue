<template>
    <Teleport to="body" v-if="show">
        <Transition>
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="$emit('close')">
                <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-location-dot text-emerald-600 dark:text-emerald-400"></i>
                            Create Certificate of Appearance
                        </h3>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                        <div class="grid gap-4">
                            <!-- Control No Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Control No</label>
                                <input :value="formData.control_no" type="text" disabled class="block w-full px-4 py-2 text-xs border rounded-lg bg-gray-100 dark:bg-gray-600 border-gray-300 dark:border-gray-600 dark:text-white cursor-not-allowed opacity-75" />
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-calendar absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.date" @input="$emit('update:formData', { ...formData, date: ($event.target as HTMLInputElement).value }); $emit('generateControlNo')" type="date" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.date ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" :max="todayDate" />
                                </div>
                                <span v-if="formErrors.date" class="text-red-500 text-xs">{{ formErrors.date }}</span>
                            </div>

                            <!-- Name Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-user absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.name" @input="$emit('update:formData', { ...formData, name: ($event.target as HTMLInputElement).value })" type="text" placeholder="Name" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.name ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.name" class="text-red-500 text-xs">{{ formErrors.name }}</span>
                            </div>

                            <!-- Office Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Office <span class="text-red-600">*</span></label>
                                <div class="relative flex items-center">
                                    <i class="fas fa-building absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                    <input :value="formData.office" @input="$emit('update:formData', { ...formData, office: ($event.target as HTMLInputElement).value })" type="text" placeholder="Office" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors" :class="[formErrors.office ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']" />
                                </div>
                                <span v-if="formErrors.office" class="text-red-500 text-xs">{{ formErrors.office }}</span>
                            </div>

                            <!-- Purpose Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purpose <span class="text-red-600">*</span></label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-list absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea :value="formData.purpose" @input="$emit('update:formData', { ...formData, purpose: ($event.target as HTMLTextAreaElement).value })" placeholder="Purpose" rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.purpose ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"></textarea>
                                </div>
                                <span v-if="formErrors.purpose" class="text-red-500 text-xs">{{ formErrors.purpose }}</span>
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <div class="relative flex items-start">
                                    <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                    <textarea :value="formData.remarks" @input="$emit('update:formData', { ...formData, remarks: ($event.target as HTMLTextAreaElement).value })" placeholder="Additional remarks..." rows="3" class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors resize-none" :class="[formErrors.remarks ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"></textarea>
                                </div>
                                <span v-if="formErrors.remarks" class="text-red-500 text-xs">{{ formErrors.remarks }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-center gap-3 p-6 border-t-2 border-gray-200 rounded-b-lg dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <button @click="$emit('submit')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-eye"></i>
                            Preview & Continue
                        </button>
                        <button @click="$emit('close')" class="inline-flex items-center gap-2 px-5 py-3 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-600 dark:border-gray-500 hover:text-white hover:bg-gray-600 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
defineProps<{
    show: boolean;
    formData: {
        control_no: string;
        name: string;
        office: string;
        purpose: string;
        date: string;
        remarks: string;
    };
    formErrors: Record<string, string>;
    loading: boolean;
    todayDate: string;
}>();

defineEmits<{
    'update:show': [value: boolean];
    'update:formData': [value: any];
    submit: [];
    close: [];
    generateControlNo: [];
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
