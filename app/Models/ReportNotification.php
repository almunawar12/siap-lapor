<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notifikasi database Laravel dengan kolom tambahan `report_id` dan `event_id`.
 * Query selalu ter-scope ke penerimanya; tidak ada akses lintas pengguna.
 *
 * @property string $id
 * @property int|null $report_id
 * @property string|null $event_id
 * @property array<string, mixed> $data
 * @property CarbonInterface|null $read_at
 */
class ReportNotification extends DatabaseNotification {}
