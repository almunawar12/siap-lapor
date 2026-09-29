<?php

use App\Models\ReportingPeriod;
use Illuminate\Database\QueryException;
use Tests\ReportTestHelpers as H;

it('mengizinkan admin kabupaten mengelola periode', function (): void {
    $this->actingAs(H::kabupaten())->post('/admin/periods', [
        'name' => 'Triwulan I 2026',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-03-31',
        'submission_deadline' => '2026-04-05',
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    expect(ReportingPeriod::query()->where('name', 'Triwulan I 2026')->exists())->toBeTrue();
});

it('menolak admin kecamatan mengelola periode', function (): void {
    $user = H::kecamatan();

    $this->actingAs($user)->get('/admin/periods')->assertForbidden();
    $this->actingAs($user)->post('/admin/periods', [
        'name' => 'Periode Ilegal',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-03-31',
        'is_active' => true,
    ])->assertForbidden();

    expect(ReportingPeriod::query()->count())->toBe(0);
});

it('menolak tanggal selesai sebelum tanggal mulai', function (): void {
    $this->actingAs(H::kabupaten())->post('/admin/periods', [
        'name' => 'Periode Salah',
        'starts_on' => '2026-03-31',
        'ends_on' => '2026-01-01',
        'is_active' => true,
    ])->assertSessionHasErrors('ends_on');
});

it('menegakkan rentang periode pada level database', function (): void {
    expect(fn () => ReportingPeriod::factory()->create([
        'starts_on' => '2026-03-31',
        'ends_on' => '2026-01-01',
    ]))->toThrow(QueryException::class);
});

it('periode nonaktif tetap dapat difilter tetapi tidak dapat dipakai laporan baru', function (): void {
    $nonaktif = H::period(active: false);

    $this->actingAs(H::kecamatan())
        ->post('/reports', ['reporting_period_id' => $nonaktif->id])
        ->assertSessionHasErrors('reporting_period_id');

    $this->actingAs(H::kabupaten())
        ->get('/reports?period='.$nonaktif->id)
        ->assertOk();
});
