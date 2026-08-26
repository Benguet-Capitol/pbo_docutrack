import { nextTick } from 'vue';

/**
 * Print modals sometimes come out blank because window.print() fires before
 * Vue has painted the just-shown DOM or before the header/logo images have
 * finished loading — Chrome captures whatever is on screen at that instant.
 * Wait for pending images and a couple of paint frames first.
 */
export const printSafely = async (): Promise<void> => {
    await nextTick();

    const pendingImages = Array.from(document.images).filter((img) => !img.complete);
    if (pendingImages.length > 0) {
        await Promise.all(
            pendingImages.map(
                (img) =>
                    new Promise<void>((resolve) => {
                        img.addEventListener('load', () => resolve(), { once: true });
                        img.addEventListener('error', () => resolve(), { once: true });
                    })
            )
        );
    }

    await new Promise<void>((resolve) => requestAnimationFrame(() => requestAnimationFrame(() => resolve())));

    window.print();
};
