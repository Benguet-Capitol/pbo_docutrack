import { nextTick } from 'vue';

/**
 * Printing the live modal is unreliable: Chrome lays out the print preview from
 * the on-screen fixed/scrolling overlay and, on a cold first print (typical for
 * production builds where CSS/fonts/images are not yet cached), can produce a
 * blank page. Instead, clone the modal into a hidden same-origin iframe with
 * the page's stylesheets, wait for everything to load, and print the iframe.
 */

const IFRAME_OVERRIDES = `
    html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; overflow: visible !important; }
    .fixed { position: static !important; }
    .fixed.inset-0 { display: block !important; padding: 0 !important; background: none !important; }
    .max-h-\\[90vh\\] { max-height: none !important; }
    .overflow-y-auto { overflow: visible !important; }
    .relative { margin: 0 auto !important; }
    .print\\:hidden { display: none !important; }
`;

const findModalRoot = (): HTMLElement | null => {
    const overlays = Array.from(document.querySelectorAll<HTMLElement>('.fixed.inset-0')).filter(
        (el) => !el.closest('#app')
    );
    return overlays.length > 0 ? overlays[overlays.length - 1] : null;
};

const waitForStyles = (doc: Document): Promise<void> => {
    const links = Array.from(doc.querySelectorAll<HTMLLinkElement>('link[rel="stylesheet"]'));
    return Promise.all(
        links.map(
            (link) =>
                new Promise<void>((resolve) => {
                    if (link.sheet) return resolve();
                    link.addEventListener('load', () => resolve(), { once: true });
                    link.addEventListener('error', () => resolve(), { once: true });
                })
        )
    ).then(() => undefined);
};

const waitForImages = (doc: Document): Promise<void> =>
    Promise.all(
        Array.from(doc.images).map((img) =>
            img.complete
                ? Promise.resolve()
                : new Promise<void>((resolve) => {
                      img.addEventListener('load', () => resolve(), { once: true });
                      img.addEventListener('error', () => resolve(), { once: true });
                  })
        )
    ).then(() => undefined);

const nextFrames = (win: Window): Promise<void> =>
    new Promise((resolve) => win.requestAnimationFrame(() => win.requestAnimationFrame(() => resolve())));

const PRINT_DPI = 96;

/**
 * Some forms (e.g. Pass Slip / Tardiness) render two carbon-copy columns whose
 * on-screen size rarely matches the printed page's exact dimensions — content
 * can spill onto a near-empty second page, or (once shrunk to avoid that) sit
 * small in a corner with the rest of the sheet left blank. When the printed
 * element carries data-page-width-in/height-in(/margin-in) attributes, scale it
 * (visually only, via transform) up or down and center it so it always fills a
 * single page of that exact size, without affecting the on-screen preview.
 */
const fitContentToOnePage = (doc: Document): void => {
    const target = doc.getElementById('preview-content');
    if (!target) return;

    const widthIn = parseFloat(target.dataset.pageWidthIn || '');
    const heightIn = parseFloat(target.dataset.pageHeightIn || '');
    const marginIn = parseFloat(target.dataset.pageMarginIn || '0');
    if (!widthIn || !heightIn) return;

    const availWidth = (widthIn - marginIn * 2) * PRINT_DPI;
    const availHeight = (heightIn - marginIn * 2) * PRINT_DPI;

    const actualWidth = target.scrollWidth;
    const actualHeight = target.scrollHeight;
    if (!actualWidth || !actualHeight) return;

    const scale = Math.min(availWidth / actualWidth, availHeight / actualHeight);
    if (!isFinite(scale) || scale <= 0 || Math.abs(scale - 1) < 0.01) return;

    const wrapper = doc.createElement('div');
    wrapper.style.cssText =
        `width:${availWidth}px;height:${availHeight}px;overflow:hidden;` +
        `display:flex;align-items:center;justify-content:center;`;
    target.parentNode?.insertBefore(wrapper, target);
    wrapper.appendChild(target);

    target.style.width = `${actualWidth}px`;
    target.style.flex = 'none';
    target.style.transformOrigin = 'center center';
    target.style.transform = `scale(${scale})`;
};

export const printSafely = async (): Promise<void> => {
    await nextTick();

    const root = findModalRoot();
    if (!root) {
        window.print();
        return;
    }

    // Give the iframe a real, generous viewport (roughly the modal's natural on-screen
    // width) rather than 0x0. A near-zero viewport forces percentage-width content to
    // wrap into a narrow, artificially tall column *before* print layout kicks in, which
    // throws off any measurement (e.g. fit-to-one-page scaling) taken against it.
    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;left:-10000px;top:-10000px;width:1400px;height:2000px;border:0;visibility:hidden;';
    document.body.appendChild(iframe);

    const win = iframe.contentWindow;
    const doc = iframe.contentDocument;
    if (!win || !doc) {
        iframe.remove();
        window.print();
        return;
    }

    const headMarkup = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
        .map((node) => {
            if (node instanceof HTMLLinkElement) {
                return `<link rel="stylesheet" href="${node.href}">`;
            }
            return `<style>${node.textContent ?? ''}</style>`;
        })
        .join('\n');

    const clone = root.cloneNode(true) as HTMLElement;

    doc.open();
    doc.write(
        `<!DOCTYPE html><html><head><meta charset="utf-8"><base href="${document.baseURI}">${headMarkup}` +
            `<style>${IFRAME_OVERRIDES}</style></head><body></body></html>`
    );
    doc.close();
    doc.body.appendChild(doc.importNode(clone, true));

    try {
        await Promise.all([waitForStyles(doc), waitForImages(doc), doc.fonts ? doc.fonts.ready : Promise.resolve()]);
        await Promise.all(
            Array.from(doc.images).map((img) => (img.decode ? img.decode().catch(() => undefined) : undefined))
        );
        await nextFrames(win);

        fitContentToOnePage(doc);
        await nextFrames(win);

        win.addEventListener('afterprint', () => setTimeout(() => iframe.remove(), 500), { once: true });
        win.focus();
        win.print();
    } catch {
        iframe.remove();
        window.print();
    }
};
