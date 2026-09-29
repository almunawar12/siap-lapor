<?php

use App\Enums\ReportStatus;
use App\Models\District;
use App\Models\Report;
use App\Support\ReportPayload;
use Tests\ReportTestHelpers as H;

it('membuat draf dengan kecamatan dari akun, bukan dari payload', function (): void {
    $district = District::factory()->create();
    $lain = District::factory()->create();
    $user = H::kecamatan($district);
    $period = H::period();

    $this->actingAs($user)->post('/reports', [
        'reporting_period_id' => $period->id,
        'district_id' => $lain->id,
        'status' => ReportStatus::Approved->value,
        'lock_version' => 99,
    ])->assertRedirect();

    $report = Report::query()->sole();

    expect($report->district_id)->toBe($district->id)
        ->and($report->status)->toBe(ReportStatus::Draft)
        ->and($report->lock_version)->toBe(0)
        ->and($report->current_version_id)->not->toBeNull();

    $version = $report->currentVersion;

    // PostgreSQL jsonb tidak menjaga urutan key, jadi urutan kanonik dijamin
    // saat dibaca melalui normalizedPayload(), bukan oleh penyimpanan.
    expect($version->version_number)->toBe(1)
        ->and($version->submitted_at)->toBeNull()
        ->and(array_keys($version->payload))->toEqualCanonicalizing(ReportPayload::keys())
        ->and(array_keys($version->normalizedPayload()))->toEqual(ReportPayload::keys());
});

it('menolak admin kabupaten membuat laporan', function (): void {
    $period = H::period();

    $this->actingAs(H::kabupaten())
        ->post('/reports', ['reporting_period_id' => $period->id])
        ->assertForbidden();

    expect(Report::query()->count())->toBe(0);
});

it('menolak pembuatan laporan pada periode nonaktif', function (): void {
    $period = H::period(active: false);

    $this->actingAs(H::kecamatan())
        ->post('/reports', ['reporting_period_id' => $period->id])
        ->assertSessionHasErrors('reporting_period_id');

    expect(Report::query()->count())->toBe(0);
});

it('menyimpan draf parsial tanpa menolak field kosong', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'activity_name' => 'Baru sebagian terisi',
    ])->assertSessionHasNoErrors();

    $report->refresh();

    expect($report->currentVersion->payload['activity_name'])->toBe('Baru sebagian terisi')
        ->and($report->currentVersion->payload['findings'])->toBeNull()
        ->and($report->status)->toBe(ReportStatus::Draft)
        ->and($report->lock_version)->toBe(1);
});

it('menormalkan string kosong menjadi null', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'activity_name' => '   ',
        'report_number' => '',
    ])->assertSessionHasNoErrors();

    $report->refresh();

    expect($report->currentVersion->payload['activity_name'])->toBeNull()
        ->and($report->report_number)->toBeNull();
});

it('mempertahankan paragraf pada uraian hasil pengawasan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'findings' => "Paragraf satu.\r\n\r\nParagraf dua.",
    ])->assertSessionHasNoErrors();

    expect($report->refresh()->currentVersion->payload['findings'])
        ->toBe("Paragraf satu.\n\nParagraf dua.");
});

it('mengabaikan snapshot wilayah dan instansi yang dikirim dari payload', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'district_name' => 'Kecamatan Palsu',
        'institution_name' => 'Instansi Palsu',
        'regency_name' => 'Kabupaten Palsu',
    ])->assertSessionHasNoErrors();

    $payload = $report->refresh()->currentVersion->payload;

    expect($payload['district_name'])->toBeNull()
        ->and($payload['institution_name'])->toBeNull()
        ->and($payload['regency_name'])->toBeNull();
});

it('menolak tanggal selesai sebelum tanggal mulai', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'activity_start_date' => '2026-03-10',
        'activity_end_date' => '2026-03-09',
    ])->assertSessionHasErrors('activity_end_date');
});

it('menolak uraian melebihi batas panjang', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'findings' => str_repeat('a', ReportPayload::MAX_LENGTHS['findings'] + 1),
    ])->assertSessionHasErrors('findings');
});

it('menolak nomor LHP yang sudah dipakai laporan lain tanpa menyimpan apa pun', function (): void {
    $user = H::kecamatan();
    $pertama = H::draft($user);
    $kedua = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$pertama->id}", [
        'current_version_id' => $pertama->current_version_id,
        'lock_version' => 0,
        'report_number' => 'LHP/UNIK/2026',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->patch("/reports/{$kedua->id}", [
        'current_version_id' => $kedua->current_version_id,
        'lock_version' => 0,
        'report_number' => 'LHP/UNIK/2026',
        'activity_name' => 'Tidak boleh tersimpan',
    ])->assertSessionHasErrors('report_number');

    $kedua->refresh();

    expect($kedua->report_number)->toBeNull()
        ->and($kedua->currentVersion->payload['activity_name'])->toBeNull()
        ->and($kedua->lock_version)->toBe(0);
});

it('menolak penyimpanan dengan lock_version kedaluwarsa tanpa menimpa perubahan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'activity_name' => 'Perubahan pengguna pertama',
    ])->assertSessionHasNoErrors();

    // Request kedua masih memakai lock_version lama.
    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 0,
        'activity_name' => 'Perubahan yang seharusnya ditolak',
    ])->assertSessionHasErrors('conflict');

    expect($report->refresh()->currentVersion->payload['activity_name'])
        ->toBe('Perubahan pengguna pertama');
});

it('menolak penyimpanan pada versi kerja yang bukan versi aktif', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id + 999,
        'lock_version' => 0,
        'activity_name' => 'Versi salah',
    ])->assertSessionHasErrors('conflict');
});

it('mengembalikan status 409 untuk klien JSON saat terjadi konflik', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patchJson("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => 99,
        'activity_name' => 'Konflik',
    ])->assertStatus(409);
});
