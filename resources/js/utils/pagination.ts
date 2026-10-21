/**
 * Page numbers to show: first, last and two on each side of the current page.
 * Negative numbers mark an ellipsis (unique so they can be used as keys).
 */
export const visiblePageNumbers = (current: number, total: number): number[] => {
    const pages: number[] = [];
    for (let page = 1; page <= total; page++) {
        if (page === 1 || page === total || Math.abs(page - current) <= 2) {
            pages.push(page);
        } else if (pages[pages.length - 1] > 0) {
            pages.push(-page);
        }
    }
    return pages;
};
