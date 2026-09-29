<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi dalam aplikasi untuk peristiwa laporan. Hanya kanal database:
 * tidak ada email atau WhatsApp (batas lingkup PRD bagian 10).
 *
 * Isi sengaja singkat dan tidak menyalin uraian hasil pengawasan
 * (ARCHITECTURE.md bagian 9).
 */
class ReportEventNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $eventId  Penanda deduplikasi, unik per peristiwa bisnis.
     */
    public function __construct(
        public readonly Report $report,
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly string $eventId,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'report_id' => $this->report->id,
            'report_number' => $this->report->report_number,
            'district_name' => $this->report->district->name,
            'status' => $this->report->status->value,
            'status_label' => $this->report->status->label(),
        ];
    }
}
