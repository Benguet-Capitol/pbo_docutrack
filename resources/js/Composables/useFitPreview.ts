import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Fits a portrait print preview (#preview-content) to the screen: shrunk to the modal's width everywhere, and on phones to its
 * height too, so the page needs no scrolling. A landscape page ({ landscape: true }) shrinks only as much as a portrait page
 * would and scrolls for the rest (the body keeps overflow-x-auto). Only wrappers around the page are scaled; Print copies the page unscaled.
 * Template: ref="fitHeader" / "fitBody" / "fitFooter" on the modal's parts, ref="fitPage" on the page, and the body holds
 * <div class="mx-auto" :style="fitFrameStyle"><div :style="fitPageStyle"> page </div></div>.
 */
/** A portrait page's width (8.5in at 96px/in): landscape pages shrink as much as a portrait page would, and scroll for the rest */
const PORTRAIT_WIDTH_PX = 8.5 * 96;

export function useFitPreview(isOpen: () => unknown, options: { landscape?: boolean } = {}) {
    const fitHeader = ref<HTMLElement | null>(null);
    const fitBody = ref<HTMLElement | null>(null);
    const fitFooter = ref<HTMLElement | null>(null);
    const fitPage = ref<HTMLElement | null>(null);
    const scale = ref(1);
    const size = ref({ width: 0, height: 0 });

    function fit() {
        const body = fitBody.value;
        const page = fitPage.value;
        if (!body || !page) return;
        // offsetWidth / offsetHeight ignore the transform: the page's own size
        size.value = { width: page.offsetWidth, height: page.offsetHeight };
        if (!size.value.width || !size.value.height) return;
        const style = getComputedStyle(body);
        const width = body.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
        let next = Math.min(1, width / (options.landscape ? PORTRAIT_WIDTH_PX : size.value.width));
        if (!options.landscape && window.matchMedia('(max-width: 767px)').matches) {
            // The modal's own height limit (e.g. 90vh) less its header, footer and the body's padding
            const panel = body.parentElement;
            const limit = (panel && parseFloat(getComputedStyle(panel).maxHeight)) || window.innerHeight;
            const height = limit - (fitHeader.value?.offsetHeight ?? 0) - (fitFooter.value?.offsetHeight ?? 0) - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom) - 2;
            next = Math.min(next, height / size.value.height);
        }
        scale.value = Math.max(0.2, next);
    }

    // The page can grow or shrink (content, paper size), so it is watched while shown
    const observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(() => fit()) : null;
    watch(fitPage, (page, old) => {
        if (old) observer?.unobserve(old);
        if (page) observer?.observe(page);
    });
    watch(() => Boolean(isOpen()), (open) => { if (open) nextTick(fit); }, { immediate: true });
    onMounted(() => window.addEventListener('resize', fit));
    onBeforeUnmount(() => {
        window.removeEventListener('resize', fit);
        observer?.disconnect();
    });

    // Until measured, the page shows at its own size
    const fitFrameStyle = computed(() => (size.value.width ? { width: `${size.value.width * scale.value}px`, height: `${size.value.height * scale.value}px` } : {}));
    const fitPageStyle = computed(() => (size.value.width ? { width: `${size.value.width}px`, transform: `scale(${scale.value})`, transformOrigin: 'top left' } : {}));

    return { fitHeader, fitBody, fitFooter, fitPage, fitFrameStyle, fitPageStyle };
}
