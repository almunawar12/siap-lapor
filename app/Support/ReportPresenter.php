<?php

namespace App\Support;

use App\Enums\ReportStatus;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\RevisionResponse;
use App\Models\User;
use App\Models\VersionAttachment;

/**
 * Menyusun props Inertia. Hanya field yang dibutuhkan UI dikirim; path privat,
 * disk, dan hash file tidak pernah keluar ke frontend (ARCHITECTURE.md bagian 8).
 */
final class ReportPresenter
{
    /** @return array<string, mixed> */
    public static function listItem(Report $report): array
    {
        return [
            'id' => $report->id,
            'report_number' => $report->report_number,
            'activity_name' => $report->currentVersion?->payload['activity_name'] ?? null,
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'district' => $report->district->only(['id', 'code', 'name']),
            'period' => ['id' => $report->period->id, 'name' => $report->period->name],
            'version_number' => $report->currentVersion?->version_number,
            'first_submitted_at' => $report->first_submitted_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(Report $report, User $viewer): array
    {
        $report->loadMissing([
            'district', 'period', 'creator',
            'currentVersion.attachments.file', 'currentVersion.creator',
            'versions',
        ]);

        $activeReview = $report->currentVersion?->reviews()->active()->with('reviewer:id,name')->first();

        return [
            'id' => $report->id,
            'report_number' => $report->report_number,
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'lock_version' => $report->lock_version,
            'district' => $report->district->only(['id', 'code', 'name']),
            'period' => [
                'id' => $report->period->id,
                'name' => $report->period->name,
                'submission_deadline' => $report->period->submission_deadline?->toDateString(),
                'is_active' => $report->period->is_active,
            ],
            'created_by' => $report->creator->name,
            'created_at' => $report->created_at?->toIso8601String(),
            'first_submitted_at' => $report->first_submitted_at?->toIso8601String(),
            'is_late' => $report->first_submitted_at !== null
                && $report->period->isLate($report->first_submitted_at),
            'current_version' => $report->currentVersion === null
                ? null
                : self::version($report->currentVersion),
            'approved_version_id' => $report->approved_version_id,
            'versions' => $report->versions
                ->map(fn (ReportVersion $version): array => [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'submitted_at' => $version->submitted_at?->toIso8601String(),
                    'is_current' => $version->id === $report->current_version_id,
                    'is_approved' => $version->id === $report->approved_version_id,
                ])
                ->values()
                ->all(),
            'active_review' => $activeReview === null ? null : [
                'id' => $activeReview->id,
                'reviewer_name' => $activeReview->reviewer->name,
                'is_mine' => $activeReview->isOwnedBy($viewer),
                'started_at' => $activeReview->started_at->toIso8601String(),
            ],
            'reviews' => self::reviews($report),
            'notes' => self::notes($report),
            'open_notes_count' => $report->notesQuery()->open()->count(),
            'timeline' => self::timeline($report),
            'capabilities' => self::capabilities($report, $viewer, $activeReview),
        ];
    }

    /**
     * Kemampuan dihitung ulang di server. Frontend hanya menyembunyikan tombol;
     * keputusan akhir tetap pada policy dan action.
     *
     * @return array<string, bool>
     */
    protected static function capabilities(Report $report, User $viewer, ?Review $activeReview): array
    {
        $ownsActiveReview = $activeReview !== null && $activeReview->isOwnedBy($viewer);
        $canReview = $viewer->can('review', $report);
        $openNotes = $report->notesQuery()->open()->exists();

        return [
            'update' => $viewer->can('update', $report),
            'submit' => $viewer->can('submit', $report) && $report->isEditable(),
            'manage_attachments' => $viewer->can('manageAttachments', $report),
            'respond' => $viewer->can('respond', $report) && $report->isEditable(),
            'start_review' => $canReview
                && $report->status === ReportStatus::Submitted
                && $activeReview === null,
            'takeover_review' => $canReview
                && $report->status === ReportStatus::UnderReview
                && $activeReview !== null
                && ! $ownsActiveReview,
            'add_note' => $canReview
                && $report->status === ReportStatus::UnderReview
                && $ownsActiveReview,
            'decide_note' => $canReview
                && $report->status === ReportStatus::UnderReview
                && $ownsActiveReview,
            'return_for_revision' => $canReview
                && $report->status === ReportStatus::UnderReview
                && $ownsActiveReview
                && $openNotes,
            'approve' => $canReview
                && $report->status === ReportStatus::UnderReview
                && $ownsActiveReview
                && ! $openNotes,
            'reopen' => $viewer->can('reopen', $report),
        ];
    }

    /**
     * Seluruh siklus pemeriksaan laporan, termasuk yang sudah diputuskan.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function reviews(Report $report): array
    {
        return Review::query()
            ->whereIn('report_version_id', $report->versions->pluck('id'))
            ->with(['reviewer:id,name', 'version:id,version_number'])
            ->orderBy('started_at')
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'version_id' => $review->report_version_id,
                'version_number' => $review->version->version_number,
                'reviewer_name' => $review->reviewer->name,
                'status' => $review->status->value,
                'status_label' => $review->status->label(),
                'general_note' => $review->general_note,
                'started_at' => $review->started_at->toIso8601String(),
                'decided_at' => $review->decided_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Catatan revisi dari seluruh siklus, beserta tanggapannya.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function notes(Report $report): array
    {
        return $report->notesQuery()
            ->with([
                'responses.author:id,name',
                'resolver:id,name',
                'review:id,report_version_id,status',
                'review.version:id,version_number',
                'attachment.file:id,original_name',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (RevisionNote $note): array => [
                'id' => $note->id,
                'review_id' => $note->review_id,
                'version_number' => $note->review->version->version_number,
                'field_key' => $note->field_key,
                'field_label' => $note->field_key === null
                    ? null
                    : ReportPayload::label($note->field_key),
                'attachment_id' => $note->attachment_id,
                'attachment_name' => $note->attachment?->file->original_name,
                'is_general' => $note->isGeneral(),
                'body' => $note->body,
                'status' => $note->status->value,
                'status_label' => $note->status->label(),
                'resolved_by' => $note->resolver?->name,
                'resolved_at' => $note->resolved_at?->toIso8601String(),
                'created_at' => $note->created_at?->toIso8601String(),
                'responses' => $note->responses
                    ->map(fn (RevisionResponse $response): array => [
                        'id' => $response->id,
                        'author_name' => $response->author->name,
                        'version_id' => $response->report_version_id,
                        'body' => $response->body,
                        'created_at' => $response->created_at->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function timeline(Report $report): array
    {
        return ActivityLog::query()
            ->where('report_id', $report->id)
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'event' => $log->event,
                'event_label' => self::eventLabel($log->event),
                'actor_name' => $log->actor?->name,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                // Hanya alasan yang ditampilkan; metadata teknis lain tidak dikirim.
                'reason' => is_string($log->metadata['reason'] ?? null)
                    ? $log->metadata['reason']
                    : null,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->all();
    }

    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'report_created' => 'Draf dibuat',
            'draft_saved' => 'Draf disimpan',
            'report_submitted' => 'Laporan dikirim',
            'report_resubmitted' => 'Laporan diajukan ulang',
            'review_started' => 'Pemeriksaan dimulai',
            'review_taken_over' => 'Pemeriksaan diambil alih',
            'note_added' => 'Catatan revisi ditambahkan',
            'note_responded' => 'Tanggapan ditulis',
            'note_resolved' => 'Catatan ditandai selesai',
            'note_reopened' => 'Catatan dibuka kembali',
            'report_returned' => 'Dikembalikan untuk revisi',
            'report_approved' => 'Laporan disetujui',
            'report_reopened' => 'Laporan dibuka kembali',
            'attachment_added' => 'Lampiran diunggah',
            'attachment_removed' => 'Lampiran dilepas',
            default => $event,
        };
    }

    /** @return array<string, mixed> */
    public static function version(ReportVersion $version): array
    {
        $version->loadMissing(['attachments.file', 'creator']);

        return [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'schema_version' => $version->schema_version,
            'submitted_at' => $version->submitted_at?->toIso8601String(),
            'is_editable' => $version->isEditable(),
            'created_by' => $version->creator->name,
            'payload' => $version->normalizedPayload(),
            'attachments' => $version->attachments
                ->map(fn (VersionAttachment $attachment): array => self::attachment($attachment))
                ->values()
                ->all(),
        ];
    }

    /**
     * Perbandingan dua versi: payload typed dan metadata lampiran. Output berupa
     * data, dirender sebagai teks biasa oleh frontend.
     *
     * @return array<string, mixed>
     */
    public static function diff(ReportVersion $from, ReportVersion $to): array
    {
        $before = $from->normalizedPayload();
        $after = $to->normalizedPayload();

        $fields = [];

        foreach (ReportPayload::keys() as $key) {
            if (($before[$key] ?? null) === ($after[$key] ?? null)) {
                continue;
            }

            $fields[] = [
                'key' => $key,
                'label' => ReportPayload::label($key),
                'before' => $before[$key] ?? null,
                'after' => $after[$key] ?? null,
            ];
        }

        $from->loadMissing('attachments.file');
        $to->loadMissing('attachments.file');

        $names = fn (ReportVersion $version): array => $version->attachments
            ->map(fn (VersionAttachment $a): string => $a->file->original_name)
            ->sort()
            ->values()
            ->all();

        $beforeNames = $names($from);
        $afterNames = $names($to);

        return [
            'from' => [
                'id' => $from->id,
                'version_number' => $from->version_number,
                'submitted_at' => $from->submitted_at?->toIso8601String(),
            ],
            'to' => [
                'id' => $to->id,
                'version_number' => $to->version_number,
                'submitted_at' => $to->submitted_at?->toIso8601String(),
            ],
            'fields' => $fields,
            'attachments' => [
                'added' => array_values(array_diff($afterNames, $beforeNames)),
                'removed' => array_values(array_diff($beforeNames, $afterNames)),
                'unchanged' => array_values(array_intersect($beforeNames, $afterNames)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function attachment(VersionAttachment $attachment): array
    {
        $attachment->loadMissing('file');

        return [
            'id' => $attachment->id,
            'category' => $attachment->category->value,
            'category_label' => $attachment->category->label(),
            'description' => $attachment->description,
            'original_name' => $attachment->file->original_name,
            'mime_type' => $attachment->file->mime_type,
            'size_bytes' => $attachment->file->size_bytes,
        ];
    }
}
