import type { FormData } from './useEmployeeForm';

/** Required employee fields still empty (same rules as validateForm and the server) */
export const employeeMissingFields = (f: FormData): string[] => {
    const missing: string[] = [];
    if (!String(f.employee_id ?? '').trim()) missing.push('Employee ID');
    if (!String(f.name ?? '').trim()) missing.push('Name');
    if (!String(f.position_title ?? '').trim()) missing.push('Position Title');
    if (!f.status) missing.push('Status');
    if (!f.office) missing.push('Office');
    return missing;
};
