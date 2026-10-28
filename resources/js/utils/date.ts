/**
 * "YYYY-MM-DD" of the user's own (local) day.
 * Not toISOString(): that gives the UTC day, which in the Philippines (UTC+8) is still yesterday before 8:00 AM.
 */
export const localDateString = (date: Date = new Date()): string =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
