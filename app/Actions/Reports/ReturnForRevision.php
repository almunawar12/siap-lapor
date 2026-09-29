<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnForRevision
{
    public function __construct(
        private readonly RecordActivity $log,
        private readonly CreateWorkingVersion $createWorkingVersion,
        private readonly NotifyReportEvent $notify,
    ) {}

    /**
     * Mengembalikan laporan untuk revisi. Wajib ada minimal satu catatan
     * terbuka, baik catatan baru pada siklus aktif maupun catatan historis yang
     * belum selesai (PRD AC05). Versi yang diperiksa tidak diubah; versi kerja
     * baru N+1 dibuat tepat satu.
     *
     * @throws StaleReportException
     * @throws ValidationException
     */
    public function handle(
        User $actor,
        Report $report,
        ?string $generalNote,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): ReportVersion {
        return DB::transaction(function () use (
            $actor,
            $report,
            $generalNote,
            $expectedVersionId,
            $expectedLockVersion
        ): ReportVersion {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if ($locked->status !== ReportStatus::UnderReview) {
                throw new StaleReportException(
                    'Pengembalian hanya dapat dilakukan saat laporan sedang diperiksa, bukan pada status '
                    .$locked->status->label().'.'
                );
            }

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi yang diperiksa sudah berganti. Muat ulang halaman.'
                );
            }

            $review = Review::query()
                ->where('report_version_id', $expectedVersionId)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($review === null) {
                throw new StaleReportException('Tidak ada pemeriksaan aktif pada laporan ini.');
            }

            if (! $review->isOwnedBy($actor)) {
                throw new StaleReportException(
                    "Hanya pemeriksa aktif ({$review->reviewer->name}) yang dapat mengembalikan laporan."
                );
            }

            if ($locked->notesQuery()->open()->doesntExist()) {
                throw ValidationException::withMessages([
                    'notes' => 'Pengembalian memerlukan setidaknya satu catatan revisi yang belum selesai.',
                ]);
            }

            /** @var ReportVersion $reviewed */
            $reviewed = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            $review->status = ReviewStatus::ChangesRequested;
            $review->general_note = $generalNote;
            $review->decided_at = now();
            $review->save();

            $working = $this->createWorkingVersion->handle($actor, $locked, $reviewed);

            $locked->current_version_id = $working->id;
            $locked->status = ReportStatus::RevisionRequired;
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'report_returned',
                fromStatus: ReportStatus::UnderReview,
                toStatus: ReportStatus::RevisionRequired,
                version: $reviewed,
                metadata: [
                    'review_id' => $review->id,
                    'working_version_id' => $working->id,
                    'working_version_number' => $working->version_number,
                    'open_notes' => $locked->notesQuery()->open()->count(),
                ],
            );

            $this->notify->returned(
                report: $locked,
                versionId: $reviewed->id,
                openNotes: $locked->notesQuery()->open()->count(),
            );

            $report->refresh();

            return $working;
        });
    }
}
