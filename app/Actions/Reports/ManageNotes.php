<?php

namespace App\Actions\Reports;

use App\Enums\NoteStatus;
use App\Enums\ReportStatus;
use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\RevisionResponse;
use App\Models\User;
use App\Models\VersionAttachment;
use App\Support\ReportPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Catatan revisi, tanggapan, dan keputusan catatan. Semua mutasi anak juga
 * mengunci baris laporan dan menaikkan lock_version (ARCHITECTURE.md bagian 4).
 */
class ManageNotes
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Catatan baru pada pemeriksaan aktif. Target boleh berupa field tertentu,
     * lampiran tertentu, atau umum (kedua target null).
     *
     * @throws StaleReportException
     * @throws ValidationException
     */
    public function addNote(
        User $actor,
        Report $report,
        string $body,
        ?string $fieldKey,
        ?int $attachmentId,
        int $expectedLockVersion,
    ): RevisionNote {
        return DB::transaction(function () use (
            $actor,
            $report,
            $body,
            $fieldKey,
            $attachmentId,
            $expectedLockVersion
        ): RevisionNote {
            $locked = $this->lockReport($report, $expectedLockVersion);
            $review = $this->activeReviewOwnedBy($locked, $actor);

            if ($fieldKey !== null && ! in_array($fieldKey, ReportPayload::INPUT_KEYS, true)) {
                throw ValidationException::withMessages([
                    'field_key' => 'Field yang dirujuk tidak dikenal.',
                ]);
            }

            if ($attachmentId !== null) {
                $this->assertAttachmentBelongsToReport($locked, $attachmentId);
            }

            $note = RevisionNote::create([
                'review_id' => $review->id,
                'field_key' => $fieldKey,
                'attachment_id' => $attachmentId,
                'body' => $body,
                'status' => NoteStatus::Open->value,
            ]);

            $this->bumpLock($locked);

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'note_added',
                version: $review->version,
                metadata: [
                    'note_id' => $note->id,
                    'field_key' => $fieldKey,
                    'attachment_id' => $attachmentId,
                ],
            );

            $report->refresh();

            return $note;
        });
    }

    /**
     * Tanggapan kecamatan atas catatan. Tanggapan tidak otomatis menyelesaikan
     * catatan (PRD bagian 7) dan terhubung ke versi kerja yang menanggapinya.
     *
     * @throws StaleReportException
     * @throws ValidationException
     */
    public function addResponse(
        User $actor,
        Report $report,
        RevisionNote $note,
        string $body,
        int $expectedLockVersion,
    ): RevisionResponse {
        return DB::transaction(function () use (
            $actor,
            $report,
            $note,
            $body,
            $expectedLockVersion
        ): RevisionResponse {
            $locked = $this->lockReport($report, $expectedLockVersion);

            if (! $locked->isEditable()) {
                throw new StaleReportException(
                    'Tanggapan hanya dapat ditulis saat laporan dapat direvisi, bukan pada status '
                    .$locked->status->label().'.'
                );
            }

            $note->refresh();

            if (! $note->isOpen()) {
                throw ValidationException::withMessages([
                    'body' => 'Catatan ini sudah ditandai selesai.',
                ]);
            }

            $response = RevisionResponse::create([
                'revision_note_id' => $note->id,
                'report_version_id' => (int) $locked->current_version_id,
                'author_id' => $actor->id,
                'body' => $body,
            ]);

            $this->bumpLock($locked);

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'note_responded',
                version: $locked->currentVersion,
                metadata: ['note_id' => $note->id, 'response_id' => $response->id],
            );

            $report->refresh();

            return $response;
        });
    }

    /**
     * Menandai catatan selesai atau membukanya kembali. Hanya pemeriksa aktif,
     * saat under_review, dan boleh menyentuh catatan dari siklus lama. Body
     * catatan historis tidak pernah diubah.
     *
     * @throws StaleReportException
     */
    public function decideNote(
        User $actor,
        Report $report,
        RevisionNote $note,
        NoteStatus $target,
        int $expectedLockVersion,
    ): RevisionNote {
        return DB::transaction(function () use (
            $actor,
            $report,
            $note,
            $target,
            $expectedLockVersion
        ): RevisionNote {
            $locked = $this->lockReport($report, $expectedLockVersion);

            if ($locked->status !== ReportStatus::UnderReview) {
                throw new StaleReportException(
                    'Keputusan catatan hanya dapat dibuat saat laporan sedang diperiksa.'
                );
            }

            $this->activeReviewOwnedBy($locked, $actor);

            $note->refresh();

            if ($note->status === $target) {
                return $note;
            }

            $note->status = $target;
            $note->resolved_by = $target === NoteStatus::Resolved ? $actor->id : null;
            $note->resolved_at = $target === NoteStatus::Resolved ? now() : null;
            $note->save();

            $this->bumpLock($locked);

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: $target === NoteStatus::Resolved ? 'note_resolved' : 'note_reopened',
                version: $locked->currentVersion,
                metadata: ['note_id' => $note->id],
            );

            $report->refresh();

            return $note;
        });
    }

    /**
     * @throws StaleReportException
     */
    protected function lockReport(Report $report, int $expectedLockVersion): Report
    {
        /** @var Report $locked */
        $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

        if ($locked->lock_version !== $expectedLockVersion) {
            throw new StaleReportException;
        }

        return $locked;
    }

    /**
     * @throws StaleReportException
     */
    protected function activeReviewOwnedBy(Report $report, User $actor): Review
    {
        $review = Review::query()
            ->where('report_version_id', $report->current_version_id)
            ->active()
            ->first();

        if ($review === null) {
            throw new StaleReportException('Tidak ada pemeriksaan aktif pada laporan ini.');
        }

        if (! $review->isOwnedBy($actor)) {
            throw new StaleReportException(
                "Hanya pemeriksa aktif ({$review->reviewer->name}) yang dapat melakukan tindakan ini."
            );
        }

        return $review;
    }

    /**
     * Lampiran yang dirujuk catatan harus milik laporan yang sama.
     *
     * @throws ValidationException
     */
    protected function assertAttachmentBelongsToReport(Report $report, int $attachmentId): void
    {
        $belongs = VersionAttachment::query()
            ->whereKey($attachmentId)
            ->whereHas('version', fn ($query) => $query->where('report_id', $report->id))
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'attachment_id' => 'Lampiran yang dirujuk bukan milik laporan ini.',
            ]);
        }
    }

    protected function bumpLock(Report $report): void
    {
        $report->lock_version = $report->lock_version + 1;
        $report->save();
    }
}
