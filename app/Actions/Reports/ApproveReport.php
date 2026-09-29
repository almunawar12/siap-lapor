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

class ApproveReport
{
    public function __construct(
        private readonly RecordActivity $log,
        private readonly NotifyReportEvent $notify,
    ) {}

    /**
     * Menyetujui versi yang sedang diperiksa. Ditolak bila masih ada catatan
     * terbuka dari siklus mana pun (PRD AC08). Keputusan berbasis data lama
     * ditolak sebagai konflik, sehingga dua permintaan bersamaan hanya
     * menghasilkan satu keputusan sah (PRD AC07).
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
    ): Review {
        return DB::transaction(function () use (
            $actor,
            $report,
            $generalNote,
            $expectedVersionId,
            $expectedLockVersion
        ): Review {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if ($locked->status !== ReportStatus::UnderReview) {
                throw new StaleReportException(
                    'Persetujuan hanya dapat dilakukan saat laporan sedang diperiksa, bukan pada status '
                    .$locked->status->label().'.'
                );
            }

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi yang diperiksa sudah berganti. Muat ulang halaman.'
                );
            }

            /** @var ReportVersion $version */
            $version = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            if ($version->submitted_at === null) {
                throw new StaleReportException(
                    'Versi ini belum dikirim sehingga tidak dapat disetujui.'
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
                    "Hanya pemeriksa aktif ({$review->reviewer->name}) yang dapat menyetujui laporan."
                );
            }

            $openNotes = $locked->notesQuery()->open()->count();

            if ($openNotes > 0) {
                throw ValidationException::withMessages([
                    'notes' => "Masih ada {$openNotes} catatan revisi terbuka. Selesaikan semuanya sebelum menyetujui.",
                ]);
            }

            $review->status = ReviewStatus::Approved;
            $review->general_note = $generalNote;
            $review->decided_at = now();
            $review->save();

            $locked->status = ReportStatus::Approved;
            $locked->approved_version_id = $version->id;
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'report_approved',
                fromStatus: ReportStatus::UnderReview,
                toStatus: ReportStatus::Approved,
                version: $version,
                metadata: [
                    'review_id' => $review->id,
                    'version_number' => $version->version_number,
                ],
            );

            $this->notify->approved($locked, $version->id);

            $report->refresh();

            return $review->refresh();
        });
    }
}
