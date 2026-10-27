/** The Documents page's records (from /api/documents) */

/**
 * Document interface defines the structure of document data
 * Represents a single document entity with all its properties
 */
export interface Document {
    id: number;
    tracking_no: string;
    date: string;
    document_type: string;
    particulars: string | null;
    source: string | null;
    sb_no?: string | null;
    status: string;
    remarks: string | null;
    user_id: number;
    user?: { id: number; name: string } | null;
    transactions?: DocumentTransaction[];
}

/** Stores the transactions for the selected document */
export interface DocumentTransaction {
    id: number;
    document_id: number;
    user_id: number;
    forwarded_to_user_id?: number | null;
    forwarded_to_office_id?: number | null;
    forwarded_to_municipality_id?: number | null;
    action: string;
    remarks: string | null;
    created_at: string;
    duration_hours?: number | null;
    user?: { id: number; name: string };
    forwardedToUser?: { id: number; name: string } | null;
    forwardedToOffice?: { id: number; office_name: string } | null;
    forwardedToMunicipality?: { id: number; name: string } | null;
    // Laravel serializes eager-loaded relations in snake_case
    forwarded_to_user?: { id: number; name: string } | null;
    forwarded_to_office?: { id: number; office_name: string } | null;
    forwarded_to_municipality?: { id: number; name: string } | null;
}
