import { escapeHtml } from '@/utils/html';
import type { DocumentTransaction } from './types';
import type { DocumentsState } from './useDocumentsState';

/** Printing a document's checklist (the filled form, in a new window). */
export function usePrintChecklist(state: DocumentsState) {
    const { toastRef, error, editingDocument, municipalities, checklistData, formData } = state;

    /**
     * printChecklist: Print the checklist form with document transactions
     */
    const printChecklist = async () => {
        const printWindow = window.open('', '_blank');
        if (printWindow) {
            try {
                // Fetch document transactions
                let transactions: DocumentTransaction[] = [];
                if (editingDocument.value) {
                    const response = await fetch(`/api/documents/${editingDocument.value.id}/transactions`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        },
                        credentials: 'include'
                    });
                    
                    if (response.ok) {
                        transactions = await response.json();
                    }
                }
                
                // Find the municipality object to get the city class
                const selectedMunicipality = municipalities.value.find(
                    (m: any) => m.name === formData.value.source
                );
                const cityClass = selectedMunicipality?.city_class || '';
                
                // Determine form number and title based on document type
                const isBudgetProposals = formData.value.document_type === 'Budget Proposals';
                const formNumber = isBudgetProposals ? '' : (formData.value.document_type === 'Supplemental Budget' ? 'LBR Form No. 1B' : 'LBR Form No. 1A');
                const documentTypeTitle = formData.value.document_type === 'Supplemental Budget' 
                    ? 'Supplemental Budget' 
                    : (isBudgetProposals ? 'Budget Proposal' : 'Annual Budget');
                
                let checklistHTML = `
                <html>
                <head>
                    <title>Documentary Checklist - ${escapeHtml(formData.value.document_type)}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
                        .header-top { margin-bottom: 20px; }
                        .form-number { text-align: left; font-weight: bold; margin-bottom: 10px; }
                        .title { text-align: center; font-weight: bold; font-size: 12px; margin: 15px 0; }
                        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                        .detail-left { flex: 1; }
                        .detail-right { flex: 1; text-align: right; }
                        .center-text { text-align: center; font-weight: bold; margin: 10px 0; }
                        .budget-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
                        .budget-left { flex: 1; margin-left: 50px; font-weight: bold; }
                        .budget-right { flex: 1; text-align: right; margin-right: 50px; font-weight: bold; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
                        th { background-color: #f0f0f0; border: 1px solid #999; padding: 10px; font-size: 12px; text-align: center; font-weight: bold; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
                        th { background-color: #f0f0f0; border: 1px solid #999; padding: 10px; font-size: 12px; text-align: center; font-weight: bold; }
                        td { border: 1px solid #999; padding: 10px; font-size: 11px; vertical-align: top; }
                        th:nth-child(1), td:nth-child(1) { width: 20%; }
                        th:nth-child(2), td:nth-child(2) { width: 15%; }
                        th:nth-child(3), td:nth-child(3) { width: 15%; }
                        th:nth-child(4), td:nth-child(4) { width: 20%; }
                        th:nth-child(5), td:nth-child(5) { width: 30%; }
                        .transactions-section { margin-top: 30px; }
                        .transactions-title { text-align: center; font-weight: bold; font-size: 13px; margin: 20px 0 15px 0; }
                        .footer { margin-top: 30px; text-align: center; font-size: 11px; }
                        .underline { border-bottom: 1px solid #000; display: inline-block; min-width: 150px; }
                        .no-transactions { font-size: 11px; color: #999; text-align: center; padding: 15px; }
                    </style>
                </head>
                <body>
                    <div class="header-top">
                        ${formNumber ? `<div class="form-number">${formNumber}</div>` : ''}
                        
                        <div class="title">CHECKLIST ON DOCUMENTARY REQUIREMENTS${!isBudgetProposals ? 'AND SIGNATURE ' : ''}<br/>FOR THE <span style="text-transform: uppercase;">${escapeHtml(documentTypeTitle)}</span></div>
                        
                        <div class="detail-row">
                            <div class="detail-left">
                                Date Received: <strong>${formData.value.date ? new Date(formData.value.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : ''}</strong>
                            </div>
                            ${!isBudgetProposals ? `<div class="detail-right">
                                Deadline: <span class="underline"></span>
                            </div>` : ''}
                        </div>
                        
                        ${isBudgetProposals ? `
                        <div style="margin-bottom: 10px;">
                            Office: <span class="underline"><strong>${escapeHtml(formData.value.source || '-')}</strong></span>
                        </div>
                        ` : `
                        <div style="position: relative; text-align: center; margin-bottom: 10px;">
                            Municipality of <span class="underline"><strong>${escapeHtml(formData.value.source || '-')}</strong></span>
                            <span style="position: absolute; right: 0; top: 0;">
                                ${cityClass ? `<strong>${escapeHtml(cityClass)}</strong>` : '<span class="underline"></span>'} Class Municipality
                            </span>
                        </div>
                        `}
                        
                        <div class="budget-info">
                            <div class="budget-left">
                                ${formData.value.document_type === 'Supplemental Budget' && formData.value.sb_no ? `Supplemental Budget No. ${escapeHtml(formData.value.sb_no)}` : escapeHtml(documentTypeTitle)}
                            </div>
                            ${!isBudgetProposals ? `<div class="budget-right">
                                ${formData.value.document_type === 'Supplemental Budget' ? '___________ Fund' : 'General Fund'}
                            </div>` : ''}
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>DOCUMENT</th>
                                ${!isBudgetProposals ? '<th>SIGNATORY</th>' : ''}
                                <th>REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                checklistData.value.forEach((item: any, index: number) => {
                    const isChecked = item?.is_checked ? '<span style="font-size: 16px;">☑</span>' : '<span style="font-size: 16px;">☐</span>';
                    
                    // Format remarks object into HTML with proper formatting
                    const formatRemarksValue = (value: string, label: string): string => {
                        if (!value) return '';
                        // Check if it's a date field
                        if (label.toLowerCase().includes('date') || label.toLowerCase().includes('dated')) {
                            const dateObj = new Date(value);
                            if (!isNaN(dateObj.getTime())) {
                                return dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            }
                        }
                        // Check if it's an amount field
                        if (label.toLowerCase().includes('amount')) {
                            const numValue = parseFloat(value);
                            if (!isNaN(numValue)) {
                                return numValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                        return value;
                    };
                    
                    let remarksHTML = '';
                    Object.entries(item?.remarks || {}).forEach(([key, value]: [string, any]) => {
                        if (value) {
                            const formattedValue = formatRemarksValue(value, key);
                            remarksHTML += `${escapeHtml(key)}: <span style="border-bottom: 1px solid #000; white-space: pre-wrap; word-break: break-word;">${escapeHtml(formattedValue)}</span><br/>`;
                        }
                    });
                    
                    // Build document column content
                    let documentHTML = '';
                    if (item.is_subitem) {
                        // Sub-items are indented without letter
                        documentHTML = `<div style="margin-left: 20px;">${isChecked} ${escapeHtml(item.item_name)}`;
                    } else {
                        // Main items have letter prefix
                        documentHTML = `${isChecked} ${escapeHtml(item.group_letter)}. ${escapeHtml(item.item_name)}`;
                    }
                    
                    // Add annexes if they exist and this is not a merged subitem
                    const isMergedSubitem = formData.value.document_type === 'Supplemental Budget' && 
                                           ['c', 'd', 'e'].includes(item.group_letter) && 
                                           item.is_subitem;
                    
                    if (item.annexes && item.annexes.length > 0) {
                        documentHTML += '<div style="margin-left: 20px; margin-top: 5px;">';
                        item.annexes.forEach((annex: any) => {
                            const annexChecked = annex.is_checked ? '<span style="font-size: 16px;">☑</span>' : '<span style="font-size: 16px;">☐</span>';
                            documentHTML += `<div>${annexChecked} ${escapeHtml(annex.name)}</div>`;
                        });
                        documentHTML += '</div>';
                    }
                    
                    if (item.is_subitem) {
                        documentHTML += '</div>';
                    }
                    
                    // Check if this is a parent of a merged group
                    const isMergedParent = formData.value.document_type === 'Supplemental Budget' && 
                                          ['c', 'd', 'e'].includes(item.group_letter) && 
                                          !item.is_subitem;
                    
                    // Check if this is a sub-item of groups d or e (where parent signatories are shared)
                    const isGroupDESubitem = formData.value.document_type === 'Supplemental Budget' && 
                                            ['d', 'e'].includes(item.group_letter) && 
                                            item.is_subitem;
                    
                    // Calculate rowspan for merged parents (d and e)
                    let rowspan = 1;
                    let remarksRowspan = 1;
                    if (isMergedParent && ['d', 'e'].includes(item.group_letter)) {
                        const mergedItemsCount = checklistData.value.filter(
                            (i: any) => i.group_letter === item.group_letter
                        ).length;
                        rowspan = mergedItemsCount;
                        remarksRowspan = mergedItemsCount;
                    } else if (isMergedParent && item.group_letter === 'c') {
                        // Group c: parent doesn't have signatories, but remarks spans across sub-items
                        const mergedItemsCount = checklistData.value.filter(
                            (i: any) => i.group_letter === item.group_letter
                        ).length;
                        remarksRowspan = mergedItemsCount;
                    }
                    
                    // Build signatory column content
                    let signatoryHTML = '';
                    if (item.signatories && item.signatories.length > 0) {
                        item.signatories.forEach((signatory: any) => {
                            const sigChecked = signatory.is_checked ? '<span style="font-size: 16px;">☑</span>' : '<span style="font-size: 16px;">☐</span>';
                            const signatoryDisplay = signatory.acting_status 
                                ? `${signatory.name} (${signatory.acting_status})`
                                : signatory.name;
                            signatoryHTML += `<div>${sigChecked} ${escapeHtml(signatoryDisplay)}</div>`;
                        });
                    }
                    
                    const rowspanSigAttr = rowspan > 1 ? ` rowspan="${rowspan}"` : '';
                    const rowspanRemarkAttr = remarksRowspan > 1 ? ` rowspan="${remarksRowspan}"` : '';
                    
                    // Render row
                    if (isBudgetProposals) {
                        // Budget Proposals: Simple 2-column layout (DOCUMENT, REMARKS)
                        checklistHTML += `
                            <tr>
                                <td>${documentHTML}</td>
                                <td>${remarksHTML || '&nbsp;'}</td>
                            </tr>
                        `;
                    } else if (isGroupDESubitem) {
                        // Sub-items of groups d/e only show document column (signatories and remarks are spanned from parent)
                        checklistHTML += `
                            <tr>
                                <td>${documentHTML}</td>
                            </tr>
                        `;
                    } else if (isMergedSubitem && item.group_letter === 'c') {
                        // Group c sub-items: Show document and signatories (remarks are spanned from parent)
                        checklistHTML += `
                            <tr>
                                <td>${documentHTML}</td>
                                <td>${signatoryHTML || '&nbsp;'}</td>
                            </tr>
                        `;
                    } else {
                        // Parent items or non-merged items show all columns
                        checklistHTML += `
                            <tr>
                                <td>${documentHTML}</td>
                                <td${rowspanSigAttr}>${signatoryHTML || '&nbsp;'}</td>
                                <td${rowspanRemarkAttr}>${remarksHTML || '&nbsp;'}</td>
                            </tr>
                        `;
                    }
                });

                checklistHTML += `
                        </tbody>
                    </table>
                    
                    <div class="transactions-section">
                        <div class="transactions-title">DOCUMENT TRANSACTIONS</div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>USER</th>
                                    <th>DATE & TIME</th>
                                    <th>DURATION</th>
                                    <th>ACTION</th>
                                    <th>REMARKS</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                // Add transactions
                if (transactions.length === 0) {
                    checklistHTML += `<tr><td colspan="5" class="no-transactions">No transactions found</td></tr>`;
                } else {
                    transactions.forEach((transaction: any) => {
                        const actionDate = new Date(transaction.created_at);
                        const actionDateStr = actionDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                        const actionTimeStr = actionDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                        const dateTimeStr = `${actionDateStr} at ${actionTimeStr}`;
                        
                        let durationStr = '-';
                        if (transaction.duration_hours !== null && transaction.duration_hours !== undefined) {
                            const totalMinutes = Math.round(transaction.duration_hours * 60);
                            const days = Math.floor(totalMinutes / (24 * 60));
                            const remainingMinutesAfterDays = totalMinutes % (24 * 60);
                            const hours = Math.floor(remainingMinutesAfterDays / 60);
                            const minutes = remainingMinutesAfterDays % 60;
                            
                            const parts = [];
                            if (days > 0) {
                                parts.push(`${days} day${days > 1 ? 's' : ''}`);
                            }
                            if (hours > 0) {
                                parts.push(`${hours} hour${hours > 1 ? 's' : ''}`);
                            }
                            if (minutes > 0) {
                                parts.push(`${minutes} minute${minutes > 1 ? 's' : ''}`);
                            }
                            
                            durationStr = parts.length > 0 ? parts.join(' ') : '-';
                        }
                        
                        let remarksStr = transaction.remarks || '-';
                        
                        checklistHTML += `
                            <tr>
                                <td>${escapeHtml(transaction.user?.name || 'Unknown User')}</td>
                                <td>${dateTimeStr}</td>
                                <td>${durationStr}</td>
                                <td>${escapeHtml(transaction.action)}</td>
                                <td style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(remarksStr)}</td>
                            </tr>
                        `;
                    });
                }

                checklistHTML += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="footer">
                        <p><span style="font-style: italic;">Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span> <br><span style="font-weight: bold;">PBO|DocuTrack</span></p>
                    </div>
                </body>
                </html>
                `;
                
                printWindow.document.write(checklistHTML);
                printWindow.document.close();
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            } catch (e) {
                console.error('Error preparing print document:', e);
                printWindow?.close();
                toastRef.value?.add('error', 'Error', 'Failed to prepare print document', 4000);
            }
        }
    };

    return {
        printChecklist,
    };
}
