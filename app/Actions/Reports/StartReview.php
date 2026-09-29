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

class StartReview
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Dari status submitted, membuat satu pemeriksaan aktif atas versi saat ini
     * dan mengubah status menjadi under_review. Reviewer menjadi pemiliknya
     * (ARCHITECTURE.md bagian 4).
     *
     * @throws StaleReportException
     */
    public function handle(
        User $actor,
        Report $report,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): Review {
        return DB::transaction(function () use (
            $actor,
            $report,
            $expectedVersionId,
            $expectedLockVersion
        ): Review {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi laporan sudah berganti. Muat ulang halaman sebelum memulai pemeriksaan.'
                );
            }

            /** @var ReportVersion $version */
            $version = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            // Klik ganda: pemeriksaan aktif yang sudah ada dikembalikan apa adanya.
            $existing = $version->reviews()->active()->first();

            if ($existing !== null) {
                if (! $existing->isOwnedBy($actor)) {
                    throw new StaleReportException(
                        "Laporan sedang diperiksa {$existing->reviewer->name}. Gunakan Ambil Alih bila perlu."
                    );
                }

                return $existing;
            }

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if ($locked->status !== ReportStatus::Submitted) {
                throw new StaleReportException(
                    'Pemeriksaan hanya dapat dimulai dari status Diajukan, bukan '.$locked->status->label().'.'
                );
            }

            $review = Review::create([
                'report_version_id' => $version->id,
                'reviewer_id' => $actor->id,
                'status' => ReviewStatus::Active->value,
                'started_at' => now(),
            ]);

            $locked->status = ReportStatus::UnderReview;
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'review_started',
                fromStatus: ReportStatus::Submitted,
                toStatus: ReportStatus::UnderReview,
                version: $version,
                metadata: ['review_id' => $review->id],
            );

            $report->refresh();

            return $review;
        });
    }
}
