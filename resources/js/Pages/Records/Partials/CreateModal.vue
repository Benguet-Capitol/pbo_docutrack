<template>
    <Teleport to="body" v-if="show">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="$emit('close')">
            <div class="relative w-full max-w-2xl mx-4 bg-white rounded-lg shadow-lg dark:bg-gray-800 animate-scaleInUp">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-gray-700 dark:to-gray-600 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-archive text-emerald-600 dark:text-emerald-400"></i>
                        Create Record
                    </h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="grid gap-4">
                        <!-- Record No Field -->
                        <div class="space-y-2">
                            <label for="create_record_no" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record No. <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-hashtag absolute left-3 text-gray-400 text-sm"></i>
                                <input v-model="formData.record_no" id="create_record_no" type="text" placeholder="Auto-generated" disabled class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors bg-gray-100 dark:bg-gray-600 cursor-not-allowed opacity-75 border-gray-300" />
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generated based on Record Type</p>
                            <span v-if="formErrors.record_no" class="text-red-500 text-xs">{{ formErrors.record_no }}</span>
                        </div>

                        <!-- Record Type Field -->
                        <div class="space-y-2">
                            <label for="create_record_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Type <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-tags absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <select 
                                    :value="formData.record_type"
                                    @change="(e) => {
                                        $emit('update:form-data', { ...formData, record_type: (e.target as HTMLSelectElement).value });
                                    }"
                                    id="create_record_type" 
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors appearance-none"
                                    :class="[formErrors.record_type ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                >
                                    <option value="" disabled>Select a record type</option>
                                    <option v-for="type in recordTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </div>
                            <span v-if="formErrors.record_type" class="text-red-500 text-xs">{{ formErrors.record_type }}</span>
                        </div>

                        <!-- Record Subtype Field -->
                        <div v-if="recordTypesHierarchy[formData.record_type]?.length > 0" class="space-y-2">
                            <label for="create_record_subtype" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Record Subtype</label>
                            <div class="relative flex items-center">
                                <i class="fas fa-layer-group absolute left-3 text-gray-400 text-sm pointer-events-none"></i>
                                <select 
                                    :value="formData.record_subtype"
                                    @change="(e) => {
                                        $emit('update:form-data', { ...formData, record_subtype: (e.target as HTMLSelectElement).value });
                                    }"
                                    id="create_record_subtype" 
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 transition-colors appearance-none"
                                >
                                    <option value="" disabled>Select a subtype</option>
                                    <option v-for="subtype in recordTypesHierarchy[formData.record_type]" :key="subtype" :value="subtype">{{ subtype }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Title Field -->
                        <div class="space-y-2">
                            <label for="create_title" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-600">*</span></label>
                            <div class="relative flex items-center">
                                <i class="fas fa-heading absolute left-3 text-gray-400 text-sm"></i>
                                <input 
                                    :value="formData.title"
                                    @input="(e) => $emit('update:form-data', { ...formData, title: (e.target as HTMLInputElement).value })"
                                    id="create_title" 
                                    type="text" 
                                    placeholder="Enter record title" 
                                    class="block w-full pl-10 pr-4 py-2 text-xs border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none transition-colors"
                                    :class="[formErrors.title ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-emerald-500']"
                                />
                            </div>
                            <span v-if="formErrors.title" class="text-red-500 text-xs">{{ formErrors.title }}</span>
                        </div>

                        <!-- Remarks Field -->
                        <div class="space-y-2">
                            <label for="create_remarks" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                            <div class="relative flex items-start">
                                <i class="fas fa-sticky-note absolute left-3 text-gray-400 text-sm top-3"></i>
                                <textarea 
                                    :value="formData.remarks"
                                    @input="(e) => $emit('update:form-data', { ...formData, remarks: (e.target as HTMLTextAreaElement).value })"
                                    id="create_remarks" 
                                    placeholder="Additional remarks..." 
                                    rows="3" 
                                    class="block w-full pl-10 pr-4 py-2 text-xs border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:border-emerald-500 resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Submit error -->
                        <div v-if="formErrors.submit" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-xs text-red-600 dark:text-red-400">{{ formErrors.submit }}</p>
                        </div>

                        <!-- File Upload Field -->
                        <div class="space-y-2">
                            <label for="create_file" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Scanned Image / File <span class="text-red-600">*</span></label>
                            <div 
                                :class="['border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors', formErrors.file ? 'border-red-500 dark:border-red-600 hover:border-red-600 dark:hover:border-red-500' : 'border-gray-300 dark:border-gray-600 hover:border-emerald-500 dark:hover:border-emerald-400']" 
                                @click="($refs.fileInputCreate as HTMLInputElement)?.click()"
                            >
                                <input 
                                    ref="fileInputCreate"
                                    id="create_file"
                                    type="file" 
                                    @change="$emit('file-upload', $event)" 
                                    accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx"
                                    class="hidden"
                                />
                                <div v-if="!formData.selectedFileName">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 dark:text-gray-600 mb-2"></i>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, Images, or Documents (max 200MB)</p>
                                </div>
                                <div v-else class="text-sm">
                                    <i class="fas fa-file text-emerald-600 dark:text-emerald-400 text-2xl mb-2"></i>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ formData.selectedFileName }}</p>
                                </div>
                            </div>
                            <span v-if="formErrors.file" class="text-red-500 text-xs">{{ formErrors.file }}</span>
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
import type { FormData } from '../Composables/useRecordsForm';

defineProps<{
    show: boolean;
    formData: FormData;
    formErrors: Record<string, string>;
    creating: boolean;
    recordTypes: string[];
    recordTypesHierarchy: Record<string, string[]>;
}>();

defineEmits<{
    'update:form-data': [data: FormData];
    'close': [];
    'submit': [];
    'file-upload': [event: Event];
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
