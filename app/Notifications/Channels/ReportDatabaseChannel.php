<?php

namespace App\Notifications\Channels;

use App\Notifications\ReportEventNotification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

/**
 * Menambahkan `report_id` dan `event_id` pada baris notifikasi. Channel bawaan
 * hanya menulis id/type/data, sedangkan kedua kolom itu dibutuhkan untuk query
 * ter-scope per laporan dan deduplikasi peristiwa.
 */
class ReportDatabaseChannel extends DatabaseChannel
{
    /** @return array<string, mixed> */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);

        if ($notification instanceof ReportEventNotification) {
            $payload['report_id'] = $notification->report->id;
            $payload['event_id'] = $notification->eventId;
        }

        return $payload;
    }
}
