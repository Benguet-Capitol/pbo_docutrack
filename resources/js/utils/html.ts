/**
 * HTML escaping for text that ends up inside HTML strings (v-html, document.write, generated reports),
 * so user-entered values are shown as text instead of being run as markup.
 */

const ENTITIES: Record<string, string> = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

/** escapeHtml: makes any value safe to place in HTML text or a quoted attribute */
export const escapeHtml = (value: unknown): string =>
    String(value ?? '').replace(/[&<>"']/g, (c) => ENTITIES[c]);

/**
 * sanitizeBasicHtml: escapes everything, then restores only simple formatting tags
 * (<strong>, <b>, <em>, <i>, <br>, <ul>, <li>) written without attributes.
 * For messages that intentionally include light formatting around user-entered values.
 */
export const sanitizeBasicHtml = (value: unknown): string =>
    escapeHtml(value).replace(/&lt;(\/?)(strong|b|em|i|ul|li)&gt;|&lt;br\s*\/?&gt;/g, (match, slash, tag) =>
        tag ? `<${slash}${tag}>` : '<br>'
    );
