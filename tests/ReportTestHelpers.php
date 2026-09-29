<?php

namespace Tests;

use App\Actions\Reports\CreateReport;
use App\Enums\SignerCapacity;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\User;

/**
 * Helper pengujian laporan. Pembuatan laporan selalu lewat action CreateReport
 * agar pointer versi dan constraint composite di database tetap konsisten.
 */
final class ReportTestHelpers
{
    public static function kabupaten(): User
    {
        return User::factory()->kabupaten()->create();
    }

    public static function kecamatan(?District $district = null): User
    {
        return User::factory()->kecamatan($district ?? District::factory()->create())->create();
    }

    public static function period(bool $active = true): ReportingPeriod
    {
        return ReportingPeriod::factory()->state(['is_active' => $active])->create();
    }

    public static function draft(User $actor, ?ReportingPeriod $period = null): Report
    {
        return app(CreateReport::class)->handle($actor, $period ?? self::period());
    }

    /**
     * Payload Model A yang lengkap dan valid untuk pengiriman.
     *
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    public static function completePayload(array $overrides = []): array
    {
        return [
            'report_number' => 'LHP/001/III/2026',
            'supervisor_name' => 'Siti Pengawas',
            'supervisor_position' => 'Anggota Panwaslu Kecamatan',
            'assignment_number' => 'SPT/010/III/2026',
            'assignment_date' => '2026-03-01',
            'supervisor_address' => "Jalan Merdeka Nomor 1\nKecamatan Contoh",
            'activity_name' => 'Pengawasan pemutakhiran data pemilih berkelanjutan',
            'activity_form' => 'Pengawasan langsung dan pemeriksaan dokumen',
            'activity_purpose' => 'Memastikan pemutakhiran data pemilih sesuai prosedur',
            'activity_target' => 'Petugas pemutakhiran data pemilih tingkat kecamatan',
            'activity_start_date' => '2026-03-05',
            'activity_end_date' => '2026-03-06',
            'activity_start_time' => '08:30',
            'activity_end_time' => '15:00',
            'activity_location' => 'Kantor Kecamatan Contoh',
            'findings' => "Paragraf pertama hasil pengawasan.\n\nParagraf kedua dengan karakter Unicode: ±, é, “kutip”.",
            'signing_place' => 'Kecamatan Contoh',
            'signing_date' => '2026-03-07',
            'signer_name' => 'Budi Ketua',
            'signer_capacity' => SignerCapacity::Ketua->value,
            ...$overrides,
        ];
    }
}
