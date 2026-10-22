/**
 * A user's employee record: the linked one (users.employee_id), else the employee with the user's exact name
 * (accounts made before the link existed).
 */
export const employeeOfUser = <T extends { id: number; name: string }>(
    employees: T[] | null | undefined,
    user: { name?: string | null; employee_id?: number | string | null } | null | undefined,
): T | undefined => {
    if (!user) return undefined;
    const linked = user.employee_id ? employees?.find((emp) => emp.id === Number(user.employee_id)) : undefined;
    return linked ?? employees?.find((emp) => emp.name === user.name);
};
