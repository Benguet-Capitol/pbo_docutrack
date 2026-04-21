import { ref, computed } from 'vue';
import type { Record } from './useRecordsData';

export interface FormData {
    record_no: string;
    record_type: string;
    record_subtype: string;
    title: string;
    remarks: string;
    selectedFile: File | null;
    selectedFileName: string;
}

const recordTypeAbbreviations = {
    'Provincial Budget': 'PB',
    'Municipal Budget': 'MB',
    'Issuances / Circulars / Other References and Documents': 'ISO',
};

export function useRecordsForm(records: { value: Record[] }) {
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const showPreviewModal = ref(false);

    const creating = ref(false);
    const updating = ref(false);
    const deleting = ref(false);

    const recordToEdit = ref<Record | null>(null);
    const recordToDelete = ref<Record | null>(null);

    const formData = ref<FormData>({
        record_no: '',
        record_type: '',
        record_subtype: '',
        title: '',
        remarks: '',
        selectedFile: null,
        selectedFileName: '',
    });

    const formErrors = ref<Record<string, string>>({});

    const getCsrfToken = (): string => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        return token;
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    };

    const validateForm = (): boolean => {
        formErrors.value = {};
        
        if (!formData.value.record_type.trim()) {
            formErrors.value['record_type'] = 'Record Type is required';
        }
        
        if (!formData.value.title.trim()) {
            formErrors.value['title'] = 'Title is required';
        }

        if (showCreateModal.value && !formData.value.selectedFile) {
            formErrors.value['file'] = 'File upload is required';
        }
        
        return Object.keys(formErrors.value).length === 0;
    };

    const generateRecordNo = async (recordType: string): Promise<string> => {
        try {
            const response = await fetch(`/api/records/generate/record-no?record_type=${encodeURIComponent(recordType)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error('Failed to generate record number');
            }

            const data = await response.json();
            return data.record_no;
        } catch (error) {
            console.error('Error generating record number:', error);
            const abbr = recordTypeAbbreviations[recordType as keyof typeof recordTypeAbbreviations];
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const yearPrefix = `${abbr}-${year}-`;
            
            const matchingNumbers = records.value
                .filter(r => r.record_type === recordType && r.record_no.startsWith(yearPrefix))
                .map(r => {
                    const parts = r.record_no.split('-');
                    return parseInt(parts[parts.length - 1], 10);
                });
            
            const maxNumber = matchingNumbers.length > 0 ? Math.max(...matchingNumbers) : 0;
            const series = String(maxNumber + 1).padStart(3, '0');
            return `${abbr}-${year}-${month}-${series}`;
        }
    };

    const openCreateModal = async (recordTypesHierarchy: any, activeTab: string) => {
        const subtypes = recordTypesHierarchy[activeTab] || [];
        const initialSubtype = subtypes.length > 0 ? subtypes[0] : null;
        
        formData.value = { 
            record_no: '', 
            record_type: activeTab, 
            record_subtype: initialSubtype || '',
            title: '', 
            remarks: '', 
            selectedFile: null, 
            selectedFileName: '' 
        };
        formErrors.value = {};
        await updateRecordNo();
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
    };

    const submitCreateForm = async (toastRef: any, fetchRecords: () => Promise<void>) => {
        if (!validateForm()) return;
        
        try {
            creating.value = true;
            
            const formDataObj = new FormData();
            formDataObj.append('record_no', formData.value.record_no);
            formDataObj.append('record_type', formData.value.record_type);
            formDataObj.append('record_subtype', formData.value.record_subtype);
            formDataObj.append('title', formData.value.title);
            formDataObj.append('remarks', formData.value.remarks);
            if (formData.value.selectedFile) {
                formDataObj.append('file', formData.value.selectedFile);
            }

            const response = await fetch('/api/records', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formDataObj,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Failed to create record');
            }

            const newRecord = await response.json();
            await fetchRecords();
            closeCreateModal();
            
            toastRef?.value?.add(
                'success',
                'Success',
                `<strong>${newRecord.record_type}</strong> ${newRecord.record_subtype ? `(${newRecord.record_subtype}): ` : ': '}<strong>${newRecord.record_no}</strong> has been saved successfully!`,
                3000
            );
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            
            toastRef?.value?.add('error', 'Error', errorMsg, 4000);
        } finally {
            creating.value = false;
        }
    };

    const handleEditRecord = (record: Record) => {
        recordToEdit.value = record;
        formData.value = { 
            record_no: record.record_no,
            record_type: record.record_type,
            record_subtype: record.record_subtype || '',
            title: record.title,
            remarks: record.remarks || '',
            selectedFile: null,
            selectedFileName: ''
        };
        formErrors.value = {};
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        recordToEdit.value = null;
    };

    const submitEditForm = async (toastRef: any, fetchRecords: () => Promise<void>) => {
        if (!recordToEdit.value) return;
        
        if (!validateForm()) return;

        try {
            updating.value = true;
            
            const formDataObj = new FormData();
            formDataObj.append('record_no', formData.value.record_no);
            formDataObj.append('record_type', formData.value.record_type);
            formDataObj.append('record_subtype', formData.value.record_subtype);
            formDataObj.append('title', formData.value.title);
            formDataObj.append('remarks', formData.value.remarks);
            if (formData.value.selectedFile) {
                formDataObj.append('file', formData.value.selectedFile);
            }

            const response = await fetch(`/api/records/${recordToEdit.value.id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-HTTP-Method-Override': 'PUT',
                },
                body: formDataObj,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Failed to update record');
            }

            const updatedRecord = await response.json();
            await fetchRecords();
            closeEditModal();
            
            toastRef?.value?.add(
                'info',
                'Updated',
                `<strong>${updatedRecord.record_type}</strong> ${updatedRecord.record_subtype ? `(${updatedRecord.record_subtype}): ` : ': '}<strong>${updatedRecord.record_no}</strong> has been updated successfully!`,
                3000
            );
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            
            toastRef?.value?.add('error', 'Error', errorMsg, 4000);
        } finally {
            updating.value = false;
        }
    };

    const openDeleteModal = (record: Record) => {
        recordToDelete.value = record;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        recordToDelete.value = null;
    };

    const confirmDelete = async (toastRef: any, fetchRecords: () => Promise<void>) => {
        if (!recordToDelete.value) return;

        const deletingRecord = recordToDelete.value;

        try {
            deleting.value = true;
            const response = await fetch(`/api/records/${deletingRecord.id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || errorData.message || 'Failed to delete record');
            }

            await fetchRecords();
            closeDeleteModal();
            
            toastRef?.value?.add(
                'error',
                'Deleted',
                `<strong>${deletingRecord.record_type}:</strong> ${deletingRecord.record_no} (<strong>${deletingRecord.title}</strong>) has been deleted successfully!`,
                3000
            );
        } catch (err: any) {
            const errorMsg = err instanceof Error ? err.message : 'An error occurred';
            toastRef?.value?.add('error', 'Error', errorMsg, 4000);
        } finally {
            deleting.value = false;
        }
    };

    const handleFileUpload = (event: Event) => {
        const file = (event.target as HTMLInputElement).files?.[0];
        if (file) {
            formData.value.selectedFile = file;
            formData.value.selectedFileName = file.name;
        }
    };

    const updateRecordNo = async () => {
        if (formData.value.record_type) {
            formData.value.record_no = await generateRecordNo(formData.value.record_type);
        }
    };

    const viewFile = (record: Record) => {
        if (record.image_path) {
            window.open(`/api/records/${record.id}/view`, '_blank');
        }
    };

    const downloadFile = (record: Record) => {
        if (record.image_path) {
            const link = document.createElement('a');
            link.href = `/api/records/${record.id}/download`;
            link.download = record.image_path.split('/').pop() || 'document';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    return {
        showCreateModal,
        showEditModal,
        showDeleteModal,
        showPreviewModal,
        creating,
        updating,
        deleting,
        recordToEdit,
        recordToDelete,
        formData,
        formErrors,
        openCreateModal,
        closeCreateModal,
        submitCreateForm,
        handleEditRecord,
        closeEditModal,
        submitEditForm,
        openDeleteModal,
        closeDeleteModal,
        confirmDelete,
        handleFileUpload,
        updateRecordNo,
        validateForm,
        formatFileSize,
        viewFile,
        downloadFile,
    };
}
