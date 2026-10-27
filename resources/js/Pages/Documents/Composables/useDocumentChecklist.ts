import { computed, watch } from 'vue';
import { escapeHtml } from '@/utils/html';
import { getCsrfToken, isFirstItemOfGroupC, isFirstItemOfGroupD, isFirstItemOfGroupE, isPartOfGroupC, isPartOfGroupD, isPartOfGroupE, REQUIRED_BUDGET_PROPOSAL_ITEMS } from './documentDisplay';
import type { Document } from './types';
import type { DocumentsState } from './useDocumentsState';
import type { DocumentActions } from './useDocumentActions';

/** The document checklist (Annual Budget, Supplemental Budget, Budget Proposals): layout helpers, opening/editing it, validation, and saving a document with its checklist. */
export function useDocumentChecklist(state: DocumentsState, actions: DocumentActions) {
    const {
        toastRef, documents, error, editingDocument, showChecklistModal, checklistTemplate, checklistData,
        checklistValidationError, formData, formErrors,
    } = state;
    const { closeCreateModal, validateForm } = actions;

    /**
     * Helper: Get parent item remarks for shared groups
     * For Supplemental Budget groups c and d, remarks are shared with parent
     */
    const getParentRemarks = (item: any): Record<string, string> => {
        // Only for Supplemental Budget
        if (formData.value.document_type !== 'Supplemental Budget') {
            return {};
        }
        
        // Only for sub-items in groups c, d, or e
        if (!item.is_subitem || !['c', 'd', 'e'].includes(item.group_letter)) {
            return {};
        }
        
        // Find the parent item with the same group letter
        const parentItem = checklistData.value.find(
            (i: any) => i.group_letter === item.group_letter && !i.is_subitem
        );
        
        return parentItem?.remarks || {};
    };

    /**
     * Helper: Get parent item signatories for shared groups
     * For Supplemental Budget group d, signatories are shared with parent
     */
    const getParentSignatories = (item: any): any[] => {
        // Only for Supplemental Budget
        if (formData.value.document_type !== 'Supplemental Budget') {
            return [];
        }
        
        // Only for groups 'd' or 'e'
        if (!['d', 'e'].includes(item.group_letter)) {
            return [];
        }
        
        // If this is the parent item, return its own signatories
        if (!item.is_subitem) {
            return item.signatories || [];
        }
        
        // For sub-items, find the parent item with the same group letter
        const parentItem = checklistData.value.find(
            (i: any) => i.group_letter === item.group_letter && !i.is_subitem
        );
        
        return parentItem?.signatories || [];
    };

    /**
     * Helper: Check if remarks should be hidden for this item
     * For group 'c' items in Supplemental Budget, hide remarks except on the last sub-item
     */
    const shouldHideRemarks = (item: any, index: number): boolean => {
        // Hide remarks for parent items of groups c, d, and e in Supplemental Budget
        if (formData.value.document_type === 'Supplemental Budget' && ['c', 'd', 'e'].includes(item.group_letter) && !item.is_subitem) {
            return true;
        }
        
        // Hide remarks for sub-items of groups c, d, and e in Supplemental Budget (except last one)
        if (formData.value.document_type === 'Supplemental Budget' && ['c', 'd', 'e'].includes(item.group_letter) && item.is_subitem) {
            return !isLastSubitemOfGroup(item, index);
        }
        
        return false;
    };

    /**
     * Helper: Check if this is the last sub-item of a group
     */
    const isLastSubitemOfGroup = (item: any, index: number): boolean => {
        if (!item.is_subitem) return false;
        
        // Check if next item is not a sub-item with same group letter
        const nextItem = checklistData.value[index + 1];
        return !nextItem || nextItem.group_letter !== item.group_letter || !nextItem.is_subitem;
    };

    /**
     * Helper: Check if remarks should show parent remarks below this item
     */
    const shouldShowParentRemarksBelow = (item: any, index: number): boolean => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return false;
        }
        
        // For last sub-items of groups c, d, or e
        if (!['c', 'd', 'e'].includes(item.group_letter) || !item.is_subitem) {
            return false;
        }
        
        return isLastSubitemOfGroup(item, index);
    };

    /**
     * Helper: Get rowspan count for group 'c' items (parent + sub-items)
     * For Supplemental Budget group 'c': should be 3 (1 parent + 2 sub-items)
     */
    const getGroupCRowspan = (): number => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return 1;
        }
        
        // Count items in group 'c'
        const groupCItems = checklistData.value.filter((i: any) => i.group_letter === 'c');
        return groupCItems.length;
    };

    /**
     * Helper: Get rowspan count for group 'd' items (parent + sub-items)
     */
    const getGroupDRowspan = (): number => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return 1;
        }
        
        // Count items in group 'd'
        const groupDItems = checklistData.value.filter((i: any) => i.group_letter === 'd');
        return groupDItems.length;
    };

    /**
     * Helper: Get rowspan count for group 'e' items (parent + sub-items)
     */
    const getGroupERowspan = (): number => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return 1;
        }
        
        // Count items in group 'e'
        const groupEItems = checklistData.value.filter((i: any) => i.group_letter === 'e');
        return groupEItems.length;
    };

    /**
     * Helper: Check if we should skip the remarks cell (not first item of group c, d, or e)
     */
    const shouldSkipRemarksCell = (item: any): boolean => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return false;
        }
        
        // Skip remarks for all items in group 'c' except the first one
        if (isPartOfGroupC(item) && !isFirstItemOfGroupC(item)) {
            return true;
        }
        
        // Skip remarks for all items in group 'd' except the first one
        if (isPartOfGroupD(item) && !isFirstItemOfGroupD(item)) {
            return true;
        }
        
        // Skip remarks for all items in group 'e' except the first one
        if (isPartOfGroupE(item) && !isFirstItemOfGroupE(item)) {
            return true;
        }
        
        return false;
    };

    /**
     * Helper: Check if we should skip the signatory cell (not first item of group d or e)
     */
    const shouldSkipSignatoryCell = (item: any): boolean => {
        if (formData.value.document_type !== 'Supplemental Budget') {
            return false;
        }
        
        // Skip signatory for all items in group 'd' except the first one
        if (isPartOfGroupD(item) && !isFirstItemOfGroupD(item)) {
            return true;
        }
        
        // Skip signatory for all items in group 'e' except the first one
        if (isPartOfGroupE(item) && !isFirstItemOfGroupE(item)) {
            return true;
        }
        
        return false;
    };

    /**
     * Helper: Get the document item to check for disabling signatories
     * For Supplemental Budget items in groups d/e that are subitems, get the parent item
     * For all other items, return the item itself
     */
    const getDocumentItemForSignatories = (item: any): any => {
        // For subitems in groups d or e, find and return the parent item
        if (formData.value.document_type === 'Supplemental Budget' && ['d', 'e'].includes(item.group_letter) && item.is_subitem) {
            const parentItem = checklistData.value.find(
                (i: any) => i.group_letter === item.group_letter && !i.is_subitem
            );
            return parentItem || item;
        }
        
        // For all other items, return the item itself
        return item;
    };

    /**
     * Helper: Handle document checkbox change and uncheck signatories if document is unchecked
     * When a document checkbox is unchecked, all its signatories are automatically unchecked
     * For items in groups d/e, also uncheck signatories of the parent item
     */
    const handleDocumentCheckboxChange = (item: any): void => {
        // If document is being unchecked, uncheck all its signatories
        if (!item.is_checked) {
            // For subitems in groups d/e, uncheck parent's signatories
            if (formData.value.document_type === 'Supplemental Budget' && ['d', 'e'].includes(item.group_letter) && item.is_subitem) {
                const parentItem = checklistData.value.find(
                    (i: any) => i.group_letter === item.group_letter && !i.is_subitem
                );
                if (parentItem && parentItem.signatories) {
                    parentItem.signatories.forEach((sig: any) => {
                        sig.is_checked = false;
                    });
                }
            }
            // For regular items, uncheck their own signatories
            else if (item.signatories) {
                item.signatories.forEach((sig: any) => {
                    sig.is_checked = false;
                });
            }
        }
    };

    /**
     * Watch checklist data for changes and re-validate requirements
     * This provides real-time feedback as users check/uncheck items
     */
    watch(checklistData, () => {
        if (showChecklistModal.value) {
            validateChecklistRequirements();
        }
    }, { deep: true });

    /**
     * handleCreateDocument: Validates form and shows checklist modal if needed
     * - For documents requiring checklists (Annual Budget, Supplemental Budget, Budget Proposals):
     *   Shows the checklist modal first without saving the document
     * - For other documents, proceeds directly to saving
     */
    const handleCreateDocument = async () => {
        if (!validateForm()) return;
        
        // Check if this document type requires a checklist
        const checklistRequiredDocuments = ['Annual Budget', 'Supplemental Budget', 'Budget Proposals'];
        
        if (checklistRequiredDocuments.includes(formData.value.document_type)) {
            // Fetch the checklist template and show the modal
            // Document will be saved only after checklist is completed
            await showChecklistModalForDocument();
        } else {
            // No checklist required, proceed to save document directly
            await saveDocumentDirectly();
        }
    };

    /**
     * showChecklistModalForDocument: Fetches the checklist template and shows the modal
     */
    const showChecklistModalForDocument = async () => {
        try {
            const response = await fetch(`/api/documents/checklist/template/${formData.value.document_type}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include'
            });

            if (response.ok) {
                checklistTemplate.value = await response.json();
                
                // Initialize checklist data with the fetched template items and signatories
                if (checklistTemplate.value.items) {
                    // Filter out any E-1 to E-8 items that might exist as separate entries
                    const filteredItems = checklistTemplate.value.items.filter((item: any) => {
                        const isAnnex = /^E-[1-8]:/.test(item.item_name);
                        return !isAnnex;
                    });
                    
                    checklistData.value = filteredItems.map((item: any) => {
                        // Parse signatories from comma-separated string
                        const signatories = item.signatories 
                            ? item.signatories.split(',').map((sig: string) => ({
                                name: sig.trim(),
                                is_checked: false,
                                acting_status: ''
                              }))
                            : [];
                        
                        // Parse remarks from JSON or create default empty object
                        let remarks: Record<string, string> = {};
                        try {
                            remarks = item.remarks ? JSON.parse(item.remarks) : {};
                        } catch (e) {
                            // If JSON parsing fails, create default with single field
                            remarks = { 'Details': item.remarks || '' };
                        }
                        
                        // Initialize annexes for "Annual Investment Program to include Annexes" item
                        const annexes = item.item_name === 'Annual Investment Program to include Annexes' 
                            ? [
                                { name: 'E-1: GAD Plan & Budget', is_checked: false },
                                { name: 'E-2: LDRRM Plan', is_checked: false },
                                { name: 'E-3: HIV & AIDS Agenda', is_checked: false },
                                { name: 'E-4: CCC Action Plan', is_checked: false },
                                { name: 'E-5: Disaster Risk Reduction & Management', is_checked: false },
                                { name: 'E-6: Anti-Poverty Program', is_checked: false },
                                { name: 'E-7: Environmental Management & Protection', is_checked: false },
                                { name: 'E-8: Support to Peace and Reconciliation', is_checked: false }
                              ]
                            : [];
                        
                        return {
                            checklist_item_id: item.id,
                            item_name: item.item_name,
                            is_checked: false,
                            remarks: remarks,
                            signatories: signatories,
                            annexes: annexes,
                            group_letter: item.group_letter,
                            is_subitem: item.is_subitem
                        };
                    });
                }

                showChecklistModal.value = true;
                // Validate checklist requirements when modal opens
                validateChecklistRequirements();
            }
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'Failed to load checklist template';
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * closeChecklistModal: Closes the checklist modal
     */
    const closeChecklistModal = () => {
        showChecklistModal.value = false;
        checklistTemplate.value = { id: null, document_type: '', items: [] };
        checklistData.value = [];
        checklistValidationError.value = '';
        editingDocument.value = null;
    };

    /**
     * viewDocumentChecklist: Opens the checklist modal to view an existing document's checklist
     * @param document - The document whose checklist should be displayed
     */
    const viewDocumentChecklist = async (document: Document) => {
        const checklistRequiredDocuments = ['Annual Budget', 'Supplemental Budget', 'Budget Proposals'];
        if (!checklistRequiredDocuments.includes(document.document_type)) {
            toastRef.value?.add('error', 'Error', 'Only Annual Budget, Supplemental Budget, and Budget Proposal documents have checklists', 4000);
            return;
        }

        try {
            // Set document context for viewing
            editingDocument.value = document;
            formData.value = {
                tracking_no: document.tracking_no,
                date: document.date,
                document_type: document.document_type,
                particulars: document.particulars || '',
                source: document.source || '',
                sourceType: 'internal',
                sb_no: (document as any).sb_no || '',
                status: document.status || 'pending',
                remarks: document.remarks || ''
            };

            // Fetch the checklist template
            const response = await fetch(`/api/documents/checklist/template/${document.document_type}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include'
            });

            if (response.ok) {
                checklistTemplate.value = await response.json();
                
                // Fetch existing checklist records for this document
                const checklistRecordsResponse = await fetch(`/api/documents/${document.id}/checklist/records`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include'
                });

                // Initialize checklist data
                if (checklistTemplate.value.items) {
                    // Filter out annexes
                    const filteredItems = checklistTemplate.value.items.filter((item: any) => {
                        const isAnnex = /^E-[1-8]:/.test(item.item_name);
                        return !isAnnex;
                    });
                    
                    // If we have existing records, load them; otherwise initialize empty
                    if (checklistRecordsResponse.ok) {
                        const existingRecords = await checklistRecordsResponse.json();
                        
                        // Helper to find annex checked state from existing records
                        const findAnnexCheckedState = (annexName: string): boolean => {
                            const annexTemplateItem = checklistTemplate.value.items.find((item: any) => item.item_name === annexName);
                            if (annexTemplateItem) {
                                const existingAnnex = existingRecords.find((r: any) => r.checklist_item_id === annexTemplateItem.id);
                                return existingAnnex?.is_checked || false;
                            }
                            return false;
                        };
                        
                        // Map existing records to checklist data
                        checklistData.value = filteredItems.map((item: any) => {
                            const existingRecord = existingRecords.find((r: any) => r.checklist_item_id === item.id);
                            return {
                                checklist_item_id: item.id,
                                item_name: item.item_name,
                                is_checked: existingRecord?.is_checked || false,
                                remarks: existingRecord?.remarks 
                                    ? (typeof existingRecord.remarks === 'string' ? JSON.parse(existingRecord.remarks) : existingRecord.remarks)
                                    : (item.remarks ? JSON.parse(item.remarks) : {}),
                                signatories: item.signatories 
                                    ? item.signatories.split(',').map((sig: string, index: number) => {
                                        const existingSig = existingRecord?.signatories?.find((s: any) => s.name === sig.trim());
                                        return {
                                            name: sig.trim(),
                                            is_checked: existingSig?.is_checked || false,
                                            acting_status: existingSig?.acting_status || ''
                                        };
                                    })
                                    : [],
                                annexes: item.item_name === 'Annual Investment Program to include Annexes' 
                                    ? [
                                        { name: 'E-1: GAD Plan & Budget', is_checked: findAnnexCheckedState('E-1: GAD Plan & Budget') },
                                        { name: 'E-2: LDRRM Plan', is_checked: findAnnexCheckedState('E-2: LDRRM Plan') },
                                        { name: 'E-3: HIV & AIDS Agenda', is_checked: findAnnexCheckedState('E-3: HIV & AIDS Agenda') },
                                        { name: 'E-4: CCC Action Plan', is_checked: findAnnexCheckedState('E-4: CCC Action Plan') },
                                        { name: 'E-5: Disaster Risk Reduction & Management', is_checked: findAnnexCheckedState('E-5: Disaster Risk Reduction & Management') },
                                        { name: 'E-6: Anti-Poverty Program', is_checked: findAnnexCheckedState('E-6: Anti-Poverty Program') },
                                        { name: 'E-7: Environmental Management & Protection', is_checked: findAnnexCheckedState('E-7: Environmental Management & Protection') },
                                        { name: 'E-8: Support to Peace and Reconciliation', is_checked: findAnnexCheckedState('E-8: Support to Peace and Reconciliation') }
                                      ]
                                    : [],
                                group_letter: item.group_letter,
                                is_subitem: item.is_subitem
                            };
                        });
                    } else {
                        // Initialize empty checklist if no records found
                        checklistData.value = filteredItems.map((item: any) => {
                            return {
                                checklist_item_id: item.id,
                                item_name: item.item_name,
                                is_checked: false,
                                remarks: item.remarks ? JSON.parse(item.remarks) : {},
                                signatories: item.signatories 
                                    ? item.signatories.split(',').map((sig: string) => ({
                                        name: sig.trim(),
                                        is_checked: false,
                                        acting_status: ''
                                      }))
                                    : [],
                                annexes: item.item_name === 'Annual Investment Program to include Annexes' 
                                    ? [
                                        { name: 'E-1: GAD Plan & Budget', is_checked: false },
                                        { name: 'E-2: LDRRM Plan', is_checked: false },
                                        { name: 'E-3: HIV & AIDS Agenda', is_checked: false },
                                        { name: 'E-4: CCC Action Plan', is_checked: false },
                                        { name: 'E-5: Disaster Risk Reduction & Management', is_checked: false },
                                        { name: 'E-6: Anti-Poverty Program', is_checked: false },
                                        { name: 'E-7: Environmental Management & Protection', is_checked: false },
                                        { name: 'E-8: Support to Peace and Reconciliation', is_checked: false }
                                      ]
                                    : [],
                                group_letter: item.group_letter,
                                is_subitem: item.is_subitem
                            };
                        });
                    }
                }

                showChecklistModal.value = true;
                // Clear validation error on view
                checklistValidationError.value = '';
            }
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'Failed to load checklist';
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * openChecklistForEditing: Opens the checklist modal for editing an existing document's checklist
     */
    const openChecklistForEditing = async () => {
        const checklistRequiredDocuments = ['Annual Budget', 'Supplemental Budget', 'Budget Proposals'];
        if (!editingDocument.value || !checklistRequiredDocuments.includes(formData.value.document_type)) {
            toastRef.value?.add('error', 'Error', 'Only Annual Budget, Supplemental Budget, and Budget Proposal documents have checklists', 4000);
            return;
        }

        try {
            // Fetch the checklist template
            const response = await fetch(`/api/documents/checklist/template/${formData.value.document_type}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include'
            });

            if (response.ok) {
                checklistTemplate.value = await response.json();
                
                // Fetch existing checklist records for this document
                const checklistRecordsResponse = await fetch(`/api/documents/${editingDocument.value.id}/checklist/records`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include'
                });

                // Initialize checklist data
                if (checklistTemplate.value.items) {
                    // Filter out annexes for Annual/Supplemental Budget
                    const filteredItems = formData.value.document_type === 'Budget Proposals'
                        ? checklistTemplate.value.items
                        : checklistTemplate.value.items.filter((item: any) => {
                            const isAnnex = /^E-[1-8]:/.test(item.item_name);
                            return !isAnnex;
                        });
                    
                    // If we have existing records, load them; otherwise initialize empty
                    if (checklistRecordsResponse.ok) {
                        const existingRecords = await checklistRecordsResponse.json();
                        
                        // Helper to find annex checked state from existing records
                        const findAnnexCheckedState = (annexName: string): boolean => {
                            const annexTemplateItem = checklistTemplate.value.items.find((item: any) => item.item_name === annexName);
                            if (annexTemplateItem) {
                                const existingAnnex = existingRecords.find((r: any) => r.checklist_item_id === annexTemplateItem.id);
                                return existingAnnex?.is_checked || false;
                            }
                            return false;
                        };
                        
                        // Map existing records to checklist data
                        checklistData.value = filteredItems.map((item: any) => {
                            const existingRecord = existingRecords.find((r: any) => r.checklist_item_id === item.id);
                            return {
                                checklist_item_id: item.id,
                                item_name: item.item_name,
                                is_checked: existingRecord?.is_checked || false,
                                remarks: existingRecord?.remarks 
                                    ? (typeof existingRecord.remarks === 'string' ? JSON.parse(existingRecord.remarks) : existingRecord.remarks)
                                    : (item.remarks ? JSON.parse(item.remarks) : {}),
                                signatories: formData.value.document_type === 'Budget Proposals'
                                    ? []
                                    : (item.signatories 
                                        ? item.signatories.split(',').map((sig: string, index: number) => {
                                            const existingSig = existingRecord?.signatories?.find((s: any) => s.name === sig.trim());
                                            return {
                                                name: sig.trim(),
                                                is_checked: existingSig?.is_checked || false,
                                                acting_status: existingSig?.acting_status || ''
                                            };
                                        })
                                        : []),
                                annexes: formData.value.document_type !== 'Budget Proposals' && item.item_name === 'Annual Investment Program to include Annexes' 
                                    ? [
                                        { name: 'E-1: GAD Plan & Budget', is_checked: findAnnexCheckedState('E-1: GAD Plan & Budget') },
                                        { name: 'E-2: LDRRM Plan', is_checked: findAnnexCheckedState('E-2: LDRRM Plan') },
                                        { name: 'E-3: HIV & AIDS Agenda', is_checked: findAnnexCheckedState('E-3: HIV & AIDS Agenda') },
                                        { name: 'E-4: CCC Action Plan', is_checked: findAnnexCheckedState('E-4: CCC Action Plan') },
                                        { name: 'E-5: Disaster Risk Reduction & Management', is_checked: findAnnexCheckedState('E-5: Disaster Risk Reduction & Management') },
                                        { name: 'E-6: Anti-Poverty Program', is_checked: findAnnexCheckedState('E-6: Anti-Poverty Program') },
                                        { name: 'E-7: Environmental Management & Protection', is_checked: findAnnexCheckedState('E-7: Environmental Management & Protection') },
                                        { name: 'E-8: Support to Peace and Reconciliation', is_checked: findAnnexCheckedState('E-8: Support to Peace and Reconciliation') }
                                      ]
                                    : [],
                                group_letter: item.group_letter,
                                is_subitem: item.is_subitem
                            };
                        });
                    } else {
                        // Initialize empty checklist
                        checklistData.value = filteredItems.map((item: any) => {
                            return {
                                checklist_item_id: item.id,
                                item_name: item.item_name,
                                is_checked: false,
                                remarks: item.remarks ? JSON.parse(item.remarks) : {},
                                signatories: formData.value.document_type === 'Budget Proposals'
                                    ? []
                                    : (item.signatories 
                                        ? item.signatories.split(',').map((sig: string) => ({
                                            name: sig.trim(),
                                            is_checked: false,
                                            acting_status: ''
                                          }))
                                        : []),
                                annexes: formData.value.document_type !== 'Budget Proposals' && item.item_name === 'Annual Investment Program to include Annexes' 
                                    ? [
                                        { name: 'E-1: GAD Plan & Budget', is_checked: false },
                                        { name: 'E-2: LDRRM Plan', is_checked: false },
                                        { name: 'E-3: HIV & AIDS Agenda', is_checked: false },
                                        { name: 'E-4: CCC Action Plan', is_checked: false },
                                        { name: 'E-5: Disaster Risk Reduction & Management', is_checked: false },
                                        { name: 'E-6: Anti-Poverty Program', is_checked: false },
                                        { name: 'E-7: Environmental Management & Protection', is_checked: false },
                                        { name: 'E-8: Support to Peace and Reconciliation', is_checked: false }
                                      ]
                                    : [],
                                group_letter: item.group_letter,
                                is_subitem: item.is_subitem
                            };
                        });
                    }
                }

                showChecklistModal.value = true;
                // Validate checklist requirements when modal opens
                validateChecklistRequirements();
            }
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'Failed to load checklist';
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * Computed property to check if all checklist items are checked
     */
    const allChecklistItemsChecked = computed(() => {
        return checklistData.value.length > 0 && checklistData.value.every(item => item.is_checked);
    });

    /**
     * Computed property to count all checked checkboxes (items, signatories, and annexes)
     * Only counts checkboxes that are actually displayed
     */
    const checklistCheckedCount = computed(() => {
        let count = 0;
        checklistData.value.forEach(item => {
            // Count checked items
            if (item.is_checked) count++;
            // Count checked signatories - but only if they're displayed (exclude empty ones)
            if (item.signatories && item.signatories.length > 0) {
                const isGroupDESubitem = formData.value.document_type === 'Supplemental Budget' && 
                                        item.group_letter && 
                                        ['d', 'e'].includes(item.group_letter) && 
                                        item.is_subitem;
                // Don't count sub-item signatories for groups d and e (parent's signatories are displayed)
                if (!isGroupDESubitem) {
                    count += item.signatories.filter(sig => sig.is_checked && sig.name).length;
                }
            }
            // Count checked annexes
            if (item.annexes) {
                count += item.annexes.filter(annex => annex.is_checked).length;
            }
        });
        return count;
    });

    /**
     * Computed property to calculate total number of checkboxes (items, signatories, and annexes)
     * Only counts checkboxes that are actually displayed
     */
    const checklistTotalCount = computed(() => {
        let total = 0;
        checklistData.value.forEach(item => {
            // Count item checkbox
            total++;
            // Count signatory checkboxes - but only if they're displayed (exclude empty ones)
            if (item.signatories && item.signatories.length > 0) {
                const isGroupDESubitem = formData.value.document_type === 'Supplemental Budget' && 
                                        item.group_letter && 
                                        ['d', 'e'].includes(item.group_letter) && 
                                        item.is_subitem;
                // Don't count sub-item signatories for groups d and e (parent's signatories are displayed)
                if (!isGroupDESubitem) {
                    total += item.signatories.filter(sig => sig.name).length;
                }
            }
            // Count annex checkboxes
            if (item.annexes) {
                total += item.annexes.length;
            }
        });
        return total;
    });

    /**
     * Computed property to calculate checklist progress percentage
     */
    const checklistProgress = computed(() => {
        if (checklistTotalCount.value === 0) return 0;
        return Math.round((checklistCheckedCount.value / checklistTotalCount.value) * 100);
    });

    /**
     * toggleAllChecklist: Toggle all checklist items
     */
    const toggleAllChecklist = () => {
        const newState = !allChecklistItemsChecked.value;
        checklistData.value.forEach(item => {
            item.is_checked = newState;
        });
    };

    const validateChecklistRequirements = (): { valid: boolean; message: string } => {
        const docType = formData.value.document_type;
        let requiredItems: any[] = [];
        let missingItems: string[] = [];

        if (docType === 'Annual Budget') {
            // Annual Budget: a, b, c all mandatory
            requiredItems = checklistData.value.filter((item: any) => 
                ['a', 'b', 'c'].includes(item.group_letter)
            );
            
            requiredItems.forEach((item: any) => {
                // Check document
                if (!item.is_checked) {
                    missingItems.push(`${item.group_letter.toUpperCase()}. ${item.item_name} - Document`);
                }
                // Check signatories
                if (item.signatories && item.signatories.length > 0) {
                    const uncheckedSigs = item.signatories.filter((sig: any) => !sig.is_checked || !sig.name);
                    if (uncheckedSigs.length > 0) {
                        missingItems.push(`${item.group_letter.toUpperCase()}. ${item.item_name} - Signatory`);
                    }
                }
            });
        } else if (docType === 'Supplemental Budget') {
            // Supplemental Budget: a, b require document AND signatories (g is optional)
            const abItems = checklistData.value.filter((item: any) => 
                ['a', 'b'].includes(item.group_letter)
            );
            
            abItems.forEach((item: any) => {
                // Check document
                if (!item.is_checked) {
                    missingItems.push(`${item.group_letter.toUpperCase()}. ${item.item_name} - Document`);
                }
                // Check signatories
                if (item.signatories && item.signatories.length > 0) {
                    const uncheckedSigs = item.signatories.filter((sig: any) => !sig.is_checked || !sig.name);
                    if (uncheckedSigs.length > 0) {
                        missingItems.push(`${item.group_letter.toUpperCase()}. ${item.item_name} - Signatory`);
                    }
                }
            });
            
            // Letter c: only parent document required (not signatories for sub-items)
            const cParentItem = checklistData.value.find((item: any) => 
                item.group_letter === 'c' && !item.is_subitem
            );
            
            if (cParentItem && !cParentItem.is_checked) {
                missingItems.push(`C. ${cParentItem.item_name} - Document`);
            }
        } else if (docType === 'Budget Proposals') {
            // Budget Proposals: check specific required items by name (frontend-only validation)
            REQUIRED_BUDGET_PROPOSAL_ITEMS.forEach((requiredItemName: string) => {
                const item = checklistData.value.find((item: any) => item.item_name === requiredItemName);
                if (!item || !item.is_checked) {
                    const displayName = requiredItemName === 'LBP Form 2 (Program Appropriation & Obligation by Object of Expenditure)' 
                        ? 'LBP Form 2 (Program Appropriation & Obligation by Object of Expenditure)' 
                        : requiredItemName;
                    missingItems.push(displayName);
                }
            });
        }

        if (missingItems.length > 0) {
            const itemsList = missingItems.map(item => `<li>${escapeHtml(item)}</li>`).join('');
            const message = `These checkboxes are required for the document to be saved:<ul class="list-disc list-inside mt-2">${itemsList}</ul>`;
            checklistValidationError.value = message;
            return {
                valid: false,
                message: message
            };
        }

        checklistValidationError.value = '';
        return { valid: true, message: '' };
    };

    /**
     * saveDocumentWithChecklist: Saves the document with checklist data
     * - First creates the document via API
     * - Then saves the checklist records
     */
    const saveDocumentWithChecklist = async () => {
        try {
            // Validate checklist requirements
            const validation = validateChecklistRequirements();
            if (!validation.valid) {
                toastRef.value?.add('error', 'Validation Failed', validation.message, 5000);
                return;
            }

            // Prepare document data
            const submitData: any = {
                tracking_no: formData.value.tracking_no,
                date: formData.value.date,
                document_type: formData.value.document_type,
                particulars: formData.value.particulars,
                source: formData.value.source,
                status: formData.value.status,
                remarks: formData.value.remarks
            };
            
            // Add sb_no only for Supplemental Budget
            if (formData.value.document_type === 'Supplemental Budget') {
                submitData.sb_no = formData.value.sb_no;
            }
            
            // Create the document first
            const response = await fetch('/api/documents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify(submitData),
                credentials: 'include'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to create document');
            }
            
            const newDocument = await response.json();
            
            // Now save the checklist data if checklist items exist
            if (checklistData.value.length > 0) {
                // Prepare checklist data with remarks stringified as JSON and include annexes
                const checklistItems: any[] = [];
                
                checklistData.value.forEach((item: any) => {
                    // Add main item
                    checklistItems.push({
                        checklist_item_id: item.checklist_item_id,
                        is_checked: item.is_checked,
                        remarks: item.remarks ? JSON.stringify(item.remarks) : null,
                        signatories: item.signatories || []
                    });
                    
                    // Add annexes as separate items if they exist
                    if (item.annexes && item.annexes.length > 0) {
                        item.annexes.forEach((annex: any) => {
                            // Find the annex item in the template to get its ID
                            const annexTemplateItem = checklistTemplate.value.items.find((tpl: any) => tpl.item_name === annex.name);
                            if (annexTemplateItem) {
                                checklistItems.push({
                                    checklist_item_id: annexTemplateItem.id,
                                    is_checked: annex.is_checked,
                                    remarks: null,
                                    signatories: []
                                });
                            }
                        });
                    }
                });

                const checklistResponse = await fetch(`/api/documents/${newDocument.id}/checklist/save`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        checklist_items: checklistItems
                    }),
                    credentials: 'include'
                });
                
                if (!checklistResponse.ok) {
                    console.error('Failed to save checklist, but document was created');
                }
            }
            
            documents.value.push(newDocument);
            closeCreateModal();
            closeChecklistModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `Document: <strong>${newDocument.tracking_no}</strong> has been created successfully with checklist!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * saveDocumentDirectly: Saves a document without checklist (for documents that don't require a checklist)
     */
    const saveDocumentDirectly = async () => {
        try {
            const submitData: any = {
                tracking_no: formData.value.tracking_no,
                date: formData.value.date,
                document_type: formData.value.document_type,
                particulars: formData.value.particulars,
                source: formData.value.source,
                status: formData.value.status,
                remarks: formData.value.remarks
            };
            
            // Add sb_no only for Supplemental Budget
            if (formData.value.document_type === 'Supplemental Budget') {
                submitData.sb_no = formData.value.sb_no;
            }
            
            const response = await fetch('/api/documents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify(submitData),
                credentials: 'include'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to create document');
            }
            
            const newDocument = await response.json();
            documents.value.push(newDocument);
            closeCreateModal();
            
            toastRef.value?.add(
                'success',
                'Success',
                `Document: <strong>${newDocument.tracking_no}</strong> has been created successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            formErrors.value['submit'] = errorMsg;
            
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    /**
     * saveChecklistChanges: Saves checklist changes for an existing document
     * - Prepares checklist data with remarks as JSON strings
     * - Makes POST request to save the checklist records
     * - Closes the modal and shows success toast
     */
    const saveChecklistChanges = async () => {
        if (!editingDocument.value) {
            toastRef.value?.add('error', 'Error', 'No document selected', 4000);
            return;
        }

        try {
            // Validate checklist requirements
            const validation = validateChecklistRequirements();
            if (!validation.valid) {
                toastRef.value?.add('error', 'Validation Failed', validation.message, 5000);
                return;
            }
            // Prepare checklist data with remarks stringified as JSON and include annexes
            const checklistItems: any[] = [];
            
            checklistData.value.forEach((item: any) => {
                // Add main item
                checklistItems.push({
                    checklist_item_id: item.checklist_item_id,
                    is_checked: item.is_checked,
                    remarks: item.remarks ? JSON.stringify(item.remarks) : null,
                    signatories: item.signatories || []
                });
                
                // Add annexes as separate items if they exist
                if (item.annexes && item.annexes.length > 0) {
                    item.annexes.forEach((annex: any) => {
                        // Find the annex item in the template to get its ID
                        const annexTemplateItem = checklistTemplate.value.items.find((tpl: any) => tpl.item_name === annex.name);
                        if (annexTemplateItem) {
                            checklistItems.push({
                                checklist_item_id: annexTemplateItem.id,
                                is_checked: annex.is_checked,
                                remarks: null,
                                signatories: []
                            });
                        }
                    });
                }
            });

            const response = await fetch(`/api/documents/${editingDocument.value.id}/checklist/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({
                    checklist_items: checklistItems
                }),
                credentials: 'include'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || 'Failed to save checklist');
            }

            // Store tracking_no before closing modal (which resets editingDocument)
            const trackingNo = editingDocument.value?.tracking_no;

            closeChecklistModal();

            toastRef.value?.add(
                'success',
                'Success',
                `Checklist for <strong>${trackingNo}</strong> has been saved successfully!`,
                3000
            );
        } catch (e) {
            const errorMsg = e instanceof Error ? e.message : 'An error occurred';
            toastRef.value?.add('error', 'Error', errorMsg, 4000);
        }
    };

    return {
        getParentRemarks,
        getParentSignatories,
        shouldHideRemarks,
        isLastSubitemOfGroup,
        shouldShowParentRemarksBelow,
        getGroupCRowspan,
        getGroupDRowspan,
        getGroupERowspan,
        shouldSkipRemarksCell,
        shouldSkipSignatoryCell,
        getDocumentItemForSignatories,
        handleDocumentCheckboxChange,
        handleCreateDocument,
        showChecklistModalForDocument,
        closeChecklistModal,
        viewDocumentChecklist,
        openChecklistForEditing,
        allChecklistItemsChecked,
        checklistCheckedCount,
        checklistTotalCount,
        checklistProgress,
        toggleAllChecklist,
        validateChecklistRequirements,
        saveDocumentWithChecklist,
        saveDocumentDirectly,
        saveChecklistChanges,
    };
}
