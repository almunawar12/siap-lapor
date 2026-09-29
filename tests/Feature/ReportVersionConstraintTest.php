<?php

use App\Models\Report;
use App\Models\ReportVersion;
use Illuminate\Database\QueryException;
use Tests\ReportTestHelpers as H;

it('menolak pointer current_version yang menunjuk versi laporan lain', function (): void {
    $user = H::kecamatan();
    $reportA = H::draft($user);
    $reportB = H::draft($user);

    expect(fn () => Report::query()
        ->whereKey($reportA->id)
        ->update(['current_version_id' => $reportB->current_version_id]))
        ->toThrow(QueryException::class);
});

it('menolak dua versi dengan nomor sama pada satu laporan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    expect(fn () => ReportVersion::factory()->create([
        'report_id' => $report->id,
        'version_number' => 1,
        'created_by' => $user->id,
    ]))->toThrow(QueryException::class);
});

it('menolak approved_version_id terisi pada status selain approved', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    expect(fn () => Report::query()
        ->whereKey($report->id)
        ->update(['approved_version_id' => $report->current_version_id]))
        ->toThrow(QueryException::class);
});

it('menolak nomor LHP duplikat pada level database', function (): void {
    $user = H::kecamatan();
    $a = H::draft($user);
    $b = H::draft($user);

    Report::query()->whereKey($a->id)->update(['report_number' => 'LHP/DUP/2026']);

    expect(fn () => Report::query()->whereKey($b->id)->update(['report_number' => 'LHP/DUP/2026']))
        ->toThrow(QueryException::class);
});

it('menolak penghapusan kecamatan yang masih dirujuk laporan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    expect(fn () => $report->district->delete())->toThrow(QueryException::class);
});
