import { ref, computed, onMounted, onUnmounted } from 'vue';

export type ViewMode = 'table' | 'card';

// Below Tailwind's `sm` breakpoint a data table forces horizontal scrolling on every row, so card view is forced regardless of what the user last picked on desktop.
const MOBILE_QUERY = '(max-width: 639px)';

/**
 * useResponsiveView: table/card view mode for a module page.
 * @param storageKey - localStorage key that remembers the user's desktop choice, e.g. 'officesView'
 * @param defaultMode - mode used when nothing has been stored yet
 */
export function useResponsiveView(storageKey: string, defaultMode: ViewMode = 'table') {
    const isMobile = ref(false);
    let mql: MediaQueryList | null = null;

    const syncIsMobile = () => {
        isMobile.value = mql?.matches ?? false;
    };

    onMounted(() => {
        mql = window.matchMedia(MOBILE_QUERY);
        syncIsMobile();
        mql.addEventListener('change', syncIsMobile);
    });

    onUnmounted(() => {
        mql?.removeEventListener('change', syncIsMobile);
    });

    const readStoredMode = (): ViewMode => {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored === 'table' || stored === 'card' ? stored : defaultMode;
        } catch {
            return defaultMode;
        }
    };

    const storedMode = ref<ViewMode>(readStoredMode());

    const setViewMode = (mode: ViewMode) => {
        storedMode.value = mode;
        try {
            localStorage.setItem(storageKey, mode);
        } catch {
            // Not critical: the choice just won't survive a reload
        }
    };

    // Desktop/tablet respects whatever the user last chose; mobile always overrides to card and reverts once the viewport grows back past the breakpoint.
    const viewMode = computed<ViewMode>(() => (isMobile.value ? 'card' : storedMode.value));

    return { viewMode, isMobile, setViewMode };
}
