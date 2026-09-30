<?php

/*
| Proses PHP terpisah untuk ConcurrencyTest. Menjalankan satu action laporan
| pada waktu yang sama dengan proses lain, lalu mencetak "ok" atau "stale".
|
|   php worker.php <action> <actor_id> <report_id> <version_id> <lock_version> <start_at>
*/

use App\Actions\Reports\ApproveReport;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Exceptions\StaleReportException;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $action, $actorId, $reportId, $versionId, $lock, $startAt] = $argv;

$actor = User::query()->findOrFail((int) $actorId);
$report = Report::query()->findOrFail((int) $reportId);

// Barrier sederhana: kedua proses menunggu detik yang sama sebelum masuk transaksi.
time_sleep_until((float) $startAt);

try {
    match ($action) {
        'submit' => app(SubmitReport::class)->handle($actor, $report, (int) $versionId, (int) $lock),
        'start' => app(StartReview::class)->handle($actor, $report, (int) $versionId, (int) $lock),
        'approve' => app(ApproveReport::class)->handle($actor, $report, null, (int) $versionId, (int) $lock),
    };
    echo 'ok';
} catch (StaleReportException) {
    echo 'stale';
}
