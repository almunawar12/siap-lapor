<?php

use App\Actions\Reports\SaveDraft;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Enums\ReportStatus;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\ReportTestHelpers as H;

/*
| Uji konkurensi dengan dua proses PHP sungguhan pada PostgreSQL. Data harus
| sudah di-commit agar terlihat oleh proses lain, sehingga suite ini memakai
| DatabaseTruncation (lihat tests/Pest.php), bukan transaksi RefreshDatabase.
*/

/**
 * Menjalankan dua worker bersamaan dan mengembalikan output keduanya.
 *
 * @param  array<int, array<int, int|string>>  $calls
 * @return array<int, string>
 */
function race(array $calls): array
{
    $startAt = microtime(true) + 2;
    $env = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'pgsql',
        'DB_URL' => '',
        'DB_DATABASE' => (string) config('database.connections.pgsql.database'),
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];

    $results = Process::pool(function ($pool) use ($calls, $startAt, $env): void {
        foreach ($calls as $args) {
            $pool->env($env)->command([PHP_BINARY, __DIR__.'/worker.php', ...array_map('strval', $args), (string) $startAt]);
        }
    })->start()->wait();

    return collect($results)->map(function ($result): string {
        expect($result->successful())->toBeTrue($result->errorOutput());

        return trim($result->output());
    })->sort()->values()->all();
}

function submittedReport(User $kecamatan): Report
{
    $report = H::draft($kecamatan);
    app(SaveDraft::class)->handle($kecamatan, $report, H::completePayload(), $report->current_version_id, $report->lock_version);
    $report->refresh();
    app(SubmitReport::class)->handle($kecamatan, $report, $report->current_version_id, $report->lock_version);

    return $report->refresh();
}

it('dua proses mengirim laporan yang sama menghasilkan satu versi, satu audit, satu notifikasi', function (): void {
    $kabupaten = H::kabupaten();
    $kecamatan = H::kecamatan();
    $report = H::draft($kecamatan);
    app(SaveDraft::class)->handle($kecamatan, $report, H::completePayload(), $report->current_version_id, $report->lock_version);
    $report->refresh();

    $args = ['submit', $kecamatan->id, $report->id, $report->current_version_id, $report->lock_version];

    expect(race([$args, $args]))->toBe(['ok', 'ok']);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Submitted)
        ->and(ReportVersion::query()->where('report_id', $report->id)->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'report_submitted')->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $kabupaten->id)->count())->toBe(1);
});

it('dua Admin Kabupaten memulai pemeriksaan serentak menghasilkan satu pemeriksa aktif', function (): void {
    $first = H::kabupaten();
    $second = H::kabupaten();
    $report = submittedReport(H::kecamatan());

    $v = $report->current_version_id;
    $lock = $report->lock_version;

    expect(race([
        ['start', $first->id, $report->id, $v, $lock],
        ['start', $second->id, $report->id, $v, $lock],
    ]))->toBe(['ok', 'stale']);

    expect(Review::query()->where('report_version_id', $v)->active()->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'review_started')->count())->toBe(1)
        ->and($report->refresh()->status)->toBe(ReportStatus::UnderReview);
});

it('persetujuan serentak hanya mencatat satu keputusan', function (): void {
    $kabupaten = H::kabupaten();
    $kecamatan = H::kecamatan();
    $report = submittedReport($kecamatan);
    app(StartReview::class)->handle($kabupaten, $report, $report->current_version_id, $report->lock_version);
    $report->refresh();

    $args = ['approve', $kabupaten->id, $report->id, $report->current_version_id, $report->lock_version];

    expect(race([$args, $args]))->toBe(['ok', 'stale']);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Approved)
        ->and($report->approved_version_id)->toBe($report->current_version_id)
        ->and(ActivityLog::query()->where('event', 'report_approved')->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $kecamatan->id)
            ->where('data->event', 'report_approved')->count())->toBe(1);
});
