<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Support\ReportPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitReport
{
    public function __construct(
        private readonly RecordActivity $log,
        private readonly NotifyReportEvent $notify,
    ) {}

    /**
     * Membekukan versi kerja lalu mengubah status menjadi submitted.
     *
     * Nama wilayah dan instansi diambil dari konfigurasi server saat submit lalu
     * dibekukan pada payload sebagai snapshot untuk PDF historis
     * (ARCHITECTURE.md bagian 3 dan 4).
     *
     * @throws StaleReportException
     * @throws ValidationException
     */
    public function handle(
        User $actor,
        Report $report,
        int $expectedVersionId,
        int $expectedLockVersion,
    ): ReportVersion {
        return DB::transaction(function () use (
            $actor,
            $report,
            $expectedVersionId,
            $expectedLockVersion
        ): ReportVersion {
            /** @var Report $locked */
            $locked = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->current_version_id !== $expectedVersionId) {
                throw new StaleReportException(
                    'Versi kerja sudah berganti. Muat ulang halaman sebelum mengirim.'
                );
            }

            /** @var ReportVersion $version */
            $version = ReportVersion::query()->whereKey($expectedVersionId)->firstOrFail();

            // Klik Kirim berulang tidak boleh menggandakan versi atau event:
            // versi yang sudah dikirim dikembalikan apa adanya (PRD AC15).
            if ($version->submitted_at !== null) {
                if ($locked->status === ReportStatus::Draft) {
                    throw new StaleReportException('Status laporan tidak konsisten. Muat ulang halaman.');
                }

                return $version;
            }

            if ($locked->lock_version !== $expectedLockVersion) {
                throw new StaleReportException;
            }

            if (! $locked->isEditable()) {
                throw new StaleReportException(
                    'Laporan tidak dapat dikirim pada status '.$locked->status->label().'.'
                );
            }

            $payload = $this->freezeSnapshot($locked, $version->normalizedPayload());

            $this->assertComplete($payload);
            $this->assertOpenNotesAnswered($locked, $version);

            $fromStatus = $locked->status;

            $version->payload = $payload;
            $version->submitted_at = now();
            $version->save();

            $locked->report_number = $payload['report_number'];
            $locked->status = ReportStatus::Submitted;
            $locked->first_submitted_at ??= $version->submitted_at;
            $locked->lock_version = $locked->lock_version + 1;
            $locked->save();

            $this->log->handle(
                report: $locked,
                actor: $actor,
                event: $fromStatus === ReportStatus::RevisionRequired ? 'report_resubmitted' : 'report_submitted',
                fromStatus: $fromStatus,
                toStatus: ReportStatus::Submitted,
                version: $version,
                metadata: [
                    'version_number' => $version->version_number,
                    'is_late' => $locked->loadMissing('period')->period->isLate($version->submitted_at),
                ],
            );

            $this->notify->submitted(
                report: $locked,
                versionId: $version->id,
                isResubmission: $fromStatus === ReportStatus::RevisionRequired,
            );

            $report->refresh();

            return $version->refresh();
        });
    }

    /**
     * @param  array<string, string|null>  $payload
     * @return array<string, string|null>
     */
    protected function freezeSnapshot(Report $report, array $payload): array
    {
        $report->loadMissing('district');

        return [
            ...$payload,
            'district_name' => $report->district->name,
            'institution_name' => config('instansi.institution_name') ?: null,
            'regency_name' => config('instansi.regency_name') ?: null,
        ];
    }

    /**
     * Setiap catatan terbuka harus punya tanggapan pada versi kerja ini sebelum
     * dikirim ulang (PRD bagian 7). Tanggapan tidak menyelesaikan catatan; itu
     * tetap keputusan Admin Kabupaten.
     *
     * @throws ValidationException
     */
    protected function assertOpenNotesAnswered(Report $report, ReportVersion $version): void
    {
        $unanswered = $report->notesQuery()
            ->open()
            ->whereDoesntHave(
                'responses',
                fn ($query) => $query->where('report_version_id', $version->id),
            )
            ->count();

        if ($unanswered === 0) {
            return;
        }

        throw ValidationException::withMessages([
            'notes' => "Masih ada {$unanswered} catatan revisi tanpa tanggapan pada versi ini. "
                .'Tulis tanggapan untuk setiap catatan sebelum mengirim ulang.',
        ]);
    }

    /**
     * @param  array<string, string|null>  $payload
     *
     * @throws ValidationException
     */
    protected function assertComplete(array $payload): void
    {
        $missing = ReportPayload::missingRequired($payload);

        if ($missing === []) {
            return;
        }

        throw ValidationException::withMessages(
            collect($missing)
                ->mapWithKeys(fn (string $key): array => [
                    $key => ReportPayload::label($key).' wajib diisi sebelum laporan dikirim.',
                ])
                ->all()
        );
    }
}
