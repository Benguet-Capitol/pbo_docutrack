import { ref, watch, computed } from 'vue';
import type { CertificateOfAppearance } from './useCoaData';

export function useCoaForm(certificates: any) {
    const formData = ref({
        control_no: '',
        name: '',
        office: '',
        purpose: '',
        date: '',
        remarks: '',
    });

    const formErrors = ref<Record<string, string>>({});
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const showPreviewModal = ref(false);

    const creating = ref(false);
    const updating = ref(false);
    const deleting = ref(false);

    const certificateToEdit = ref<CertificateOfAppearance | null>(null);
    const certificateToDelete = ref<CertificateOfAppearance | null>(null);

    const todayDate = computed(() => {
        const today = new Date();
        return today.toISOString().split('T')[0];
    });

    // Watch for date changes to regenerate control number (only in CREATE mode)
    watch(
        () => formData.value.date,
        (newDate) => {
            // Only auto-generate control number when creating, not editing
            if (newDate && certificateToEdit.value === null) {
                formData.value.control_no = generateControlNo(newDate);
            }
        }
    );

    const generateControlNo = (dateString?: string): string => {
        const dateToUse = dateString ? new Date(dateString) : new Date();
        const year = dateToUse.getFullYear();
        const month = String(dateToUse.getMonth() + 1).padStart(2, '0');
        const prefix = 'COA';

        // Find the maximum series number from all records in the same year
        let maxSeries = 0;
        certificates.value.forEach((cert: CertificateOfAppearance) => {
            if (cert.control_no && cert.control_no.startsWith(`${prefix}-${year}`)) {
                const parts = cert.control_no.split('-');
                if (parts.length === 4) {
                    const series = parseInt(parts[3]);
                    if (!isNaN(series) && series > maxSeries) {
                        maxSeries = series;
                    }
                }
            }
        });

        const series = String(maxSeries + 1).padStart(4, '0');
        return `${prefix}-${year}-${month}-${series}`;
    };

    const resetForm = () => {
        formData.value = {
            control_no: '',
            name: '',
            office: '',
            purpose: '',
            date: '',
            remarks: '',
        };
        formErrors.value = {};
    };

    const openCreateModal = () => {
        const today = new Date().toISOString().split('T')[0];
        formData.value = {
            control_no: generateControlNo(today),
            name: '',
            office: '',
            purpose: '',
            date: today,
            remarks: '',
        };
        formErrors.value = {};
        certificateToEdit.value = null;
        showCreateModal.value = true;
    };

    const closeCreateModal = () => {
        showCreateModal.value = false;
        resetForm();
    };

    const openEditModal = (certificate: CertificateOfAppearance) => {
        certificateToEdit.value = certificate;
        formData.value = {
            control_no: certificate.control_no,
            name: certificate.name,
            office: certificate.office,
            purpose: certificate.purpose,
            date: certificate.date,
            remarks: certificate.remarks || '',
        };
        showEditModal.value = true;
    };

    const closeEditModal = () => {
        showEditModal.value = false;
        certificateToEdit.value = null;
        resetForm();
    };

    const openDeleteModal = (certificate: CertificateOfAppearance) => {
        certificateToDelete.value = certificate;
        showDeleteModal.value = true;
    };

    const closeDeleteModal = () => {
        showDeleteModal.value = false;
        certificateToDelete.value = null;
    };

    const openPreviewModal = () => {
        showPreviewModal.value = true;
    };

    const closePreviewModal = () => {
        showPreviewModal.value = false;
    };

    return {
        formData,
        formErrors,
        showCreateModal,
        showEditModal,
        showDeleteModal,
        showPreviewModal,
        creating,
        updating,
        deleting,
        certificateToEdit,
        certificateToDelete,
        todayDate,
        generateControlNo,
        resetForm,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        openDeleteModal,
        closeDeleteModal,
        openPreviewModal,
        closePreviewModal,
    };
}
