<?php

namespace App\Actions\Reports;

use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\User;
use App\Models\VersionAttachment;
use Illuminate\Support\Facades\DB;

class DetachFile
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Melepas link lampiran hanya pada versi kerja. Baris `files` dan byte-nya
     * tidak dihapus karena versi yang sudah dikirim mungkin masih merujuknya
     * (ARCHITECTURE.md bagian 5).
     *
     * @throws StaleReportException
     */
    public function handle(
        User $actor,
        Report $report,
        VersionAttachment $attachment,
        int $expectedLockVersion,
    ): void {
        DB::transaction(function () use ($actor, $report, $attachment, $expectedLockVersion): void {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if (! $locked->isEditable()) {
                throw new StaleReportException(
                    'Lampiran tidak dapat diubah pada status '.$locked->status->label().'.'
                );
            }

            if ($attachment->report_version_id !== $locked->current_version_id) {
                throw new StaleReportException('Lampiran ini bukan milik versi kerja yang aktif.');
            }

            $attachment->delete();

            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'attachment_removed',
                metadata: ['attachment_id' => $attachment->id],
            );

            $report->refresh();
        });
    }
}
