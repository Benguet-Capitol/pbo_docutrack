import { computed, nextTick, ref, watch } from 'vue';

/** Shown under a disabled Save/Update button when the form is still as it was opened */
export const NO_CHANGES_MESSAGE = 'No changes to save yet.';

/**
 * Whether an edit form was changed since its modal opened, so Save/Update stays disabled until something is edited
 * (like Create stays disabled until the form is complete).
 *
 * The form is snapshotted right after the modal opens. Scratch inputs named "new…" (e.g. a date being typed before
 * it is added) don't count. If the form is filled in later than that, it reads as changed, so Save is never wrongly
 * locked.
 */
export function useFormChanges(source: () => unknown, isOpen: () => boolean) {
    const snapshot = ref<string | null>(null);

    const serialize = (): string =>
        JSON.stringify(source(), (key, value) => {
            if (/^new[A-Z]/.test(key)) return undefined;
            if (value instanceof Set) return [...value].sort();
            return value;
        });

    watch(
        isOpen,
        async (open) => {
            snapshot.value = null;
            if (!open) return;
            await nextTick();
            snapshot.value = serialize();
        },
        { immediate: true },
    );

    const hasChanges = computed(() => snapshot.value === null || serialize() !== snapshot.value);

    return { hasChanges };
}
