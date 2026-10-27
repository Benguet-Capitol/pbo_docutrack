import { inject, type InjectionKey } from 'vue';
import * as display from './documentDisplay';
import { useDocumentsState } from './useDocumentsState';
import { useDocumentsList } from './useDocumentsList';
import { useDocumentActions } from './useDocumentActions';
import { useDocumentChecklist } from './useDocumentChecklist';
import { usePrintChecklist } from './usePrintChecklist';

/**
 * The Documents page, put together: its state, the list, the document actions and the checklist. Documents.vue
 * creates it and provides it; the Partials (list, cards, modals) inject it with useDocumentsContext().
 */
export function useDocumentsPage() {
    const state = useDocumentsState();
    const list = useDocumentsList(state);
    const actions = useDocumentActions(state);
    const checklist = useDocumentChecklist(state, actions);
    const print = usePrintChecklist(state);

    return { ...display, ...state, ...list, ...actions, ...checklist, ...print };
}

export type DocumentsPage = ReturnType<typeof useDocumentsPage>;

export const DOCUMENTS_PAGE: InjectionKey<DocumentsPage> = Symbol('DocumentsPage');

/** The page context, in a Partial */
export function useDocumentsContext(): DocumentsPage {
    const page = inject(DOCUMENTS_PAGE);
    if (!page) throw new Error('useDocumentsContext() must be used inside the Documents page');

    return page;
}
