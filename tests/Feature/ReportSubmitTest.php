<?php

use App\Enums\ReportStatus;
use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\ReportVersion;
use App\Models\User;
use Tests\ReportTestHelpers as H;

/**
 * Mengisi versi kerja dengan payload lengkap lewat endpoint simpan draf.
 *
 * @param  array<string, string|null>  $overrides
 */
function fillComplete(User $user, Report $report, array $overrides = []): void
{
    $report->refresh();

    test()->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload($overrides),
    ])->assertSessionHasNoErrors();

    $report->refresh();
}

it('mengirim laporan lengkap dan membekukan versinya', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertRedirect("/reports/{$report->id}");

    $report->refresh();
    $version = $report->currentVersion;

    expect($report->status)->toBe(ReportStatus::Submitted)
        ->and($report->first_submitted_at)->not->toBeNull()
        ->and($report->report_number)->toBe('LHP/001/III/2026')
        ->and($version->submitted_at)->not->toBeNull()
        ->and($version->isEditable())->toBeFalse();
});

it('membekukan snapshot kecamatan dan instansi dari konfigurasi server saat submit', function (): void {
    config([
        'instansi.institution_name' => 'Instansi Uji',
        'instansi.regency_name' => 'Kabupaten Uji',
    ]);

    $district = District::factory()->create(['name' => 'Kecamatan Nyata']);
    $user = H::kecamatan($district);
    $report = H::draft($user);
    fillComplete($user, $report);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertRedirect();

    $payload = $report->refresh()->currentVersion->payload;

    expect($payload['district_name'])->toBe('Kecamatan Nyata')
        ->and($payload['institution_name'])->toBe('Instansi Uji')
        ->and($payload['regency_name'])->toBe('Kabupaten Uji');
});

it('menolak pengiriman ketika field wajib masih kosong dengan pesan per field', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report, [
        'findings' => null,
        'signer_name' => null,
    ]);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors(['findings', 'signer_name']);

    expect($report->refresh()->status)->toBe(ReportStatus::Draft)
        ->and($report->currentVersion->submitted_at)->toBeNull();
});

it('menerima pengiriman walaupun tanggal selesai dan jam kosong', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report, [
        'activity_end_date' => null,
        'activity_start_time' => null,
        'activity_end_time' => null,
    ]);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    expect($report->refresh()->status)->toBe(ReportStatus::Submitted);
});

it('menolak penyuntingan draf setelah laporan dikirim', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ]);

    $report->refresh();

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'activity_name' => 'Menyunting setelah kirim',
    ])->assertForbidden();

    $this->actingAs($user)->get("/reports/{$report->id}/edit")->assertForbidden();

    expect($report->refresh()->currentVersion->payload['activity_name'])
        ->toBe('Pengawasan pemutakhiran data pemilih berkelanjutan');
});

it('tidak menggandakan versi atau event ketika kirim diklik berulang', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report);

    $versionId = $report->current_version_id;
    $lockVersion = $report->lock_version;

    // Dua request dengan penanda yang sama, seperti klik ganda.
    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $versionId,
        'lock_version' => $lockVersion,
    ])->assertRedirect();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $versionId,
        'lock_version' => $lockVersion,
    ])->assertRedirect();

    expect(ReportVersion::query()->where('report_id', $report->id)->count())->toBe(1)
        ->and(ActivityLog::query()->where('report_id', $report->id)->where('event', 'report_submitted')->count())->toBe(1);
});

it('menandai laporan terlambat tanpa mengubah status alur', function (): void {
    $period = ReportingPeriod::factory()->withDeadline('2020-01-01')->create();
    $user = H::kecamatan();
    $report = H::draft($user, $period);
    fillComplete($user, $report);

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertRedirect();

    $log = ActivityLog::query()->where('report_id', $report->id)->where('event', 'report_submitted')->sole();

    expect($log->metadata['is_late'])->toBeTrue()
        ->and($report->refresh()->status)->toBe(ReportStatus::Submitted);
});

it('menolak admin kabupaten mengirim laporan kecamatan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    fillComplete($user, $report);

    $this->actingAs(H::kabupaten())->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertForbidden();

    expect($report->refresh()->status)->toBe(ReportStatus::Draft);
});
