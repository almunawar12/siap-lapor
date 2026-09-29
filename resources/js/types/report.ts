/**
 * Padanan TypeScript dari App\Support\ReportPayload. Keduanya harus berubah
 * bersama; jangan memakai `any` untuk menyembunyikan ketidaksesuaian.
 */
export type ReportStatus =
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'revision_required'
    | 'approved';

export type SignerCapacity = 'ketua' | 'anggota';

export type ReportPayload = {
    report_number: string | null;
    supervisor_name: string | null;
    supervisor_position: string | null;
    assignment_number: string | null;
    assignment_date: string | null;
    supervisor_address: string | null;
    activity_name: string | null;
    activity_form: string | null;
    activity_purpose: string | null;
    activity_target: string | null;
    activity_start_date: string | null;
    activity_end_date: string | null;
    activity_start_time: string | null;
    activity_end_time: string | null;
    activity_location: string | null;
    findings: string | null;
    signing_place: string | null;
    signing_date: string | null;
    signer_name: string | null;
    signer_capacity: string | null;
    district_name: string | null;
    institution_name: string | null;
    regency_name: string | null;
};

export type PayloadField = keyof ReportPayload;

/** Key snapshot dibekukan server; tidak pernah menjadi input form. */
export type SnapshotField =
    | 'district_name'
    | 'institution_name'
    | 'regency_name';

export type PayloadInputField = Exclude<PayloadField, SnapshotField>;

export type Attachment = {
    id: number;
    category: string;
    category_label: string;
    description: string | null;
    original_name: string;
    mime_type: string;
    size_bytes: number;
};

export type ReportVersion = {
    id: number;
    version_number: number;
    schema_version: number;
    submitted_at: string | null;
    is_editable: boolean;
    created_by: string;
    payload: ReportPayload;
    attachments: Attachment[];
};

export type VersionSummary = {
    id: number;
    version_number: number;
    submitted_at: string | null;
    is_current: boolean;
    is_approved: boolean;
};

export type ReportPeriodRef = {
    id: number;
    name: string;
    submission_deadline: string | null;
    is_active: boolean;
};

export type ReportDetail = {
    id: number;
    report_number: string | null;
    status: ReportStatus;
    status_label: string;
    lock_version: number;
    district: { id: number; code: string; name: string };
    period: ReportPeriodRef;
    created_by: string;
    created_at: string | null;
    first_submitted_at: string | null;
    is_late: boolean;
    current_version: ReportVersion | null;
    approved_version_id: number | null;
    versions: VersionSummary[];
    active_review: ActiveReview | null;
    reviews: ReviewCycle[];
    notes: RevisionNote[];
    open_notes_count: number;
    timeline: TimelineEntry[];
    capabilities: ReportCapabilities;
};

export type ReportListItem = {
    id: number;
    report_number: string | null;
    activity_name: string | null;
    status: ReportStatus;
    status_label: string;
    district: { id: number; code: string; name: string };
    period: { id: number; name: string };
    version_number: number | null;
    first_submitted_at: string | null;
    updated_at: string | null;
};

export type Option = { value: string; label: string };

export type ReviewStatus = 'active' | 'changes_requested' | 'approved';
export type NoteStatus = 'open' | 'resolved';

export type RevisionResponse = {
    id: number;
    author_name: string;
    version_id: number;
    body: string;
    created_at: string;
};

export type RevisionNote = {
    id: number;
    review_id: number;
    version_number: number;
    field_key: string | null;
    field_label: string | null;
    attachment_id: number | null;
    attachment_name: string | null;
    is_general: boolean;
    body: string;
    status: NoteStatus;
    status_label: string;
    resolved_by: string | null;
    resolved_at: string | null;
    created_at: string | null;
    responses: RevisionResponse[];
};

export type ReviewCycle = {
    id: number;
    version_id: number;
    version_number: number;
    reviewer_name: string;
    status: ReviewStatus;
    status_label: string;
    general_note: string | null;
    started_at: string;
    decided_at: string | null;
};

export type ActiveReview = {
    id: number;
    reviewer_name: string;
    is_mine: boolean;
    started_at: string;
};

export type TimelineEntry = {
    id: number;
    event: string;
    event_label: string;
    actor_name: string | null;
    from_status: string | null;
    to_status: string | null;
    reason: string | null;
    created_at: string;
};

export type ReportCapabilities = {
    update: boolean;
    submit: boolean;
    manage_attachments: boolean;
    respond: boolean;
    start_review: boolean;
    takeover_review: boolean;
    add_note: boolean;
    decide_note: boolean;
    return_for_revision: boolean;
    approve: boolean;
    reopen: boolean;
};

export type VersionDiff = {
    from: { id: number; version_number: number; submitted_at: string | null };
    to: { id: number; version_number: number; submitted_at: string | null };
    fields: {
        key: string;
        label: string;
        before: string | null;
        after: string | null;
    }[];
    attachments: {
        added: string[];
        removed: string[];
        unchanged: string[];
    };
};
