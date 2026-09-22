import { nextTick } from 'vue';

// Printing the live modal directly is unreliable (can yield a blank page on a cold first print), so clone it into a hidden iframe with the page's styles, wait for everything to load, and print that instead.

const IFRAME_OVERRIDES = `
    html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; overflow: visible !important; color-scheme: light !important; }
    .fixed { position: static !important; }
    .fixed.inset-0 { display: block !important; padding: 0 !important; background: none !important; }
    .max-h-\\[90vh\\] { max-height: none !important; }
    .overflow-y-auto { overflow: visible !important; }
    .relative { margin: 0 auto !important; }
    .print\\:hidden { display: none !important; }
    /* A cloned entrance-animation class replays from its starting frame here, so printing can fire mid-fade; kill all animations/transitions in the clone. */
    *, *::before, *::after {
        animation: none !important;
        transition: none !important;
    }
    /* Force exact colors so Chrome's print "economy" mode doesn't wash out text/borders/logos. */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
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

// Scales #preview-content (visually, via transform) to exactly fill a single page of the size given by its data-page-width-in/height-in/margin-in attributes.
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

    // A real, generous viewport (not 0x0) so percentage-width content lays out naturally instead of wrapping narrow/tall before measurement.
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
