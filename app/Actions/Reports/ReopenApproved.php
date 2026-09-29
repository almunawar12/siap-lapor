<?php

namespace App\Actions\Reports;

use App\Enums\NoteStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReopenApproved
{
    public function __construct(
        private readonly RecordActivity $log,
        private readonly CreateWorkingVersion $createWorkingVersion,
        private readonly NotifyReportEvent $notify,
    ) {}

    /**
     * Membuka kembali laporan yang sudah disetujui. Alasan wajib. Keputusan
     * approved historis tidak diubah: review lama tetap berstatus approved agar
     * dapat ditampilkan sebagai riwayat. Catatan umum baru dilampirkan ke review
     * persetujuan lama sehingga wajib diselesaikan sebelum persetujuan
     * berikutnya (ARCHITECTURE.md bagian 4).
     *
     * @throws StaleReportException
     */
    public function handle(
        User $actor,
        Report $report,
        string $reason,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): ReportVersion {
        return DB::transaction(function () use (
            $actor,
            $report,
            $reason,
            $expectedVersionId,
            $expectedLockVersion
        ): ReportVersion {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if ($locked->status !== ReportStatus::Approved) {
                throw new StaleReportException(
                    'Hanya laporan berstatus Disetujui yang dapat dibuka kembali.'
                );
            }

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi laporan sudah berganti. Muat ulang halaman.'
                );
            }

            /** @var ReportVersion $approvedVersion */
            $approvedVersion = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            /** @var Review $approvalReview */
            $approvalReview = Review::query()
                ->where('report_version_id', $approvedVersion->id)
                ->where('status', ReviewStatus::Approved)
                ->orderByDesc('decided_at')
                ->firstOrFail();

            $note = RevisionNote::create([
                'review_id' => $approvalReview->id,
                'field_key' => null,
                'attachment_id' => null,
                'body' => $reason,
                'status' => NoteStatus::Open->value,
            ]);

            $working = $this->createWorkingVersion->handle($actor, $locked, $approvedVersion);

            // approved_version_id dikosongkan lebih dulu: CHECK constraint
            // melarang status non-approved punya approved_version_id.
            $locked->approved_version_id = null;
            $locked->current_version_id = $working->id;
            $locked->status = ReportStatus::RevisionRequired;
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'report_reopened',
                fromStatus: ReportStatus::Approved,
                toStatus: ReportStatus::RevisionRequired,
                version: $approvedVersion,
                metadata: [
                    'reason' => $reason,
                    'note_id' => $note->id,
                    'previous_approved_version_id' => $approvedVersion->id,
                    'working_version_id' => $working->id,
                ],
            );

            $this->notify->reopened($locked, $approvedVersion->id);

            $report->refresh();

            return $working;
        });
    }
}
