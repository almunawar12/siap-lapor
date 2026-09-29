<?php

namespace App\Actions\Reports;

use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TakeoverReview
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Mengalihkan kepemilikan pemeriksaan aktif. Alasan wajib, pemeriksa lama
     * dan baru dicatat pada audit (ARCHITECTURE.md bagian 4).
     *
     * @throws StaleReportException
     */
    public function handle(
        User $actor,
        Report $report,
        string $reason,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): Review {
        return DB::transaction(function () use (
            $actor,
            $report,
            $reason,
            $expectedVersionId,
            $expectedLockVersion
        ): Review {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi laporan sudah berganti. Muat ulang halaman sebelum mengambil alih.'
                );
            }

            $review = Review::query()
                ->where('report_version_id', $expectedVersionId)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($review === null) {
                throw new StaleReportException('Tidak ada pemeriksaan aktif untuk diambil alih.');
            }

            if ($review->isOwnedBy($actor)) {
                throw new StaleReportException('Anda sudah menjadi pemeriksa laporan ini.');
            }

            $previousReviewerId = $review->reviewer_id;

            $review->reviewer_id = $actor->id;
            $review->save();

            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'review_taken_over',
                version: $review->version,
                metadata: [
                    'review_id' => $review->id,
                    'previous_reviewer_id' => $previousReviewerId,
                    'new_reviewer_id' => $actor->id,
                    'reason' => $reason,
                ],
            );

            $report->refresh();

            return $review->refresh();
        });
    }
}
