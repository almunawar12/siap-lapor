<?php

namespace App\Actions\Reports;

use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Support\ReportPayload;
use Illuminate\Support\Facades\DB;

class SaveDraft
{
    public function __construct(private readonly RecordActivity $log) {}

    /**
     * Menyimpan versi kerja. Baris laporan dikunci, `lock_version` yang
     * diharapkan dicocokkan, dan hanya status draft/revision_required dengan
     * current version yang belum dikirim boleh diubah (ARCHITECTURE.md bagian 4).
     *
     * @param  array<string, mixed>  $values
     *
     * @throws StaleReportException
     */
    public function handle(
        User $actor,
        Report $report,
        array $values,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): ReportVersion {
        return DB::transaction(function () use (
            $actor,
            $report,
            $values,
            $expectedVersionId,
            $expectedLockVersion
        ): ReportVersion {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if (! $locked->isEditable()) {
                throw new StaleReportException(
                    'Laporan tidak dapat diubah pada status '.$locked->status->label().'.'
                );
            }

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi kerja sudah berganti. Muat ulang halaman sebelum menyimpan.'
                );
            }

            /** @var ReportVersion $version */
            $version = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            if (! $version->isEditable()) {
                throw new StaleReportException(
                    'Versi ini sudah dikirim dan tidak dapat diubah.'
                );
            }

            $payload = ReportPayload::normalize($values);
            $version->payload = $payload;
            $version->save();

            // Nomor aktif disinkronkan agar unique/search bekerja di kolom
            // tabel, sementara nomor versi lama tetap ada di payload.
            $locked->report_number = $payload['report_number'];
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: 'draft_saved',
                version: $version,
            );

            $report->refresh();

            return $version->refresh();
        });
    }
}
