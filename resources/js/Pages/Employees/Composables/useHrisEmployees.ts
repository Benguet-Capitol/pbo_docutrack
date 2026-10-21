import { ref } from 'vue';

export interface HrisRecord {
    employee_id_number: string;
    name: string | null;
    position_title: string | null;
    status: string | null;
    office_desc: string | null;
    office_id: number | null;
}

// Module-level: loaded once per page visit and shared by the Create and Edit forms
const records = ref<HrisRecord[] | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

/**
 * useHrisEmployees: HRIS employee list through the server (/api/employees/hris), so the HRIS key stays in .env.
 */
export function useHrisEmployees() {
    const load = async () => {
        if (records.value || loading.value) return;
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/employees/hris', { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.error || data.message || 'Could not load HRIS employees');
            records.value = data.data || [];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Could not load HRIS employees';
        } finally {
            loading.value = false;
        }
    };

    return { records, loading, error, load };
}
