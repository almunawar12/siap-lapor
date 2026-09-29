<?php

namespace App\Actions\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportEventNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Notifikasi dalam aplikasi untuk peristiwa laporan.
 *
 * Selalu dikirim setelah commit agar tidak ada notifikasi untuk transaksi yang
 * di-rollback. Deduplikasi memakai pasangan (penerima, event_id) yang dijaga
 * unique index, sehingga retry atau klik ganda tidak menggandakan
 * (ARCHITECTURE.md bagian 9).
 */
class NotifyReportEvent
{
    /**
     * Ke Admin Kabupaten aktif: laporan dikirim atau diajukan ulang.
     */
    public function submitted(Report $report, int $versionId, bool $isResubmission): void
    {
        $event = $isResubmission ? 'report_resubmitted' : 'report_submitted';

        $this->dispatch(
            report: $report,
            recipients: $this->activeKabupaten(),
            event: $event,
            title: $isResubmission ? 'Laporan diajukan ulang' : 'Laporan baru diajukan',
            body: sprintf(
                '%s mengirim LHP %s untuk diperiksa.',
                $report->district->name,
                $report->report_number ?? 'tanpa nomor',
            ),
            eventId: "{$event}:{$report->id}:{$versionId}",
        );
    }

    /**
     * Ke akun kecamatan aktif pada wilayah laporan: dikembalikan untuk revisi.
     */
    public function returned(Report $report, int $versionId, int $openNotes): void
    {
        $this->dispatch(
            report: $report,
            recipients: $this->activeKecamatan($report),
            event: 'report_returned',
            title: 'Laporan dikembalikan untuk revisi',
            body: sprintf(
                'LHP %s dikembalikan dengan %d catatan yang perlu ditanggapi.',
                $report->report_number ?? 'tanpa nomor',
                $openNotes,
            ),
            eventId: "report_returned:{$report->id}:{$versionId}",
        );
    }

    public function approved(Report $report, int $versionId): void
    {
        $this->dispatch(
            report: $report,
            recipients: $this->activeKecamatan($report),
            event: 'report_approved',
            title: 'Laporan disetujui',
            body: sprintf('LHP %s telah disetujui.', $report->report_number ?? 'tanpa nomor'),
            eventId: "report_approved:{$report->id}:{$versionId}",
        );
    }

    public function reopened(Report $report, int $versionId): void
    {
        $this->dispatch(
            report: $report,
            recipients: $this->activeKecamatan($report),
            event: 'report_reopened',
            title: 'Laporan disetujui dibuka kembali',
            body: sprintf(
                'LHP %s dibuka kembali dan perlu direvisi. Alasan tercantum pada catatan revisi.',
                $report->report_number ?? 'tanpa nomor',
            ),
            eventId: "report_reopened:{$report->id}:{$versionId}",
        );
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    protected function dispatch(
        Report $report,
        Collection $recipients,
        string $event,
        string $title,
        string $body,
        string $eventId,
    ): void {
        if ($recipients->isEmpty()) {
            return;
        }

        $report->loadMissing('district');

        DB::afterCommit(function () use ($report, $recipients, $event, $title, $body, $eventId): void {
            foreach ($recipients as $recipient) {
                try {
                    NotificationFacade::sendNow(
                        $recipient,
                        new ReportEventNotification($report, $event, $title, $body, $eventId),
                    );
                } catch (QueryException $exception) {
                    // Unique index (penerima, event_id) sudah menolak duplikat:
                    // peristiwa yang sama tidak boleh menghasilkan dua notifikasi.
                    if (! $this->isDuplicate($exception)) {
                        throw $exception;
                    }
                }
            }
        });
    }

    protected function isDuplicate(QueryException $exception): bool
    {
        return str_contains(
            $exception->getMessage(),
            'notifications_event_recipient_unique',
        );
    }

    /** @return Collection<int, User> */
    protected function activeKabupaten(): Collection
    {
        return User::query()
            ->where('role', UserRole::AdminKabupaten)
            ->where('is_active', true)
            ->get();
    }

    /** @return Collection<int, User> */
    protected function activeKecamatan(Report $report): Collection
    {
        return User::query()
            ->kecamatan()
            ->where('district_id', $report->district_id)
            ->where('is_active', true)
            ->get();
    }
}
