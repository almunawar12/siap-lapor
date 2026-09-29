<?php

namespace App\Support;

use App\Enums\SignerCapacity;

/**
 * Kontrak payload versi laporan (Formulir Model A).
 *
 * Payload disimpan pada `report_versions.payload` sebagai JSONB, tetapi bukan
 * JSON bebas: hanya key di sini yang diterima, urutannya kanonik, dan string
 * kosong dinormalisasi menjadi null. Padanan TypeScript ada di
 * `resources/js/types/report.ts` — keduanya harus berubah bersama.
 */
final class ReportPayload
{
    /**
     * Key snapshot yang dibekukan server saat submit, bukan input pengguna.
     *
     * @var array<int, string>
     */
    public const SNAPSHOT_KEYS = [
        'district_name',
        'institution_name',
        'regency_name',
    ];

    /**
     * Key yang diisi pengguna, dalam urutan tampilan Formulir Model A.
     *
     * @var array<int, string>
     */
    public const INPUT_KEYS = [
        'report_number',
        'supervisor_name',
        'supervisor_position',
        'assignment_number',
        'assignment_date',
        'supervisor_address',
        'activity_name',
        'activity_form',
        'activity_purpose',
        'activity_target',
        'activity_start_date',
        'activity_end_date',
        'activity_start_time',
        'activity_end_time',
        'activity_location',
        'findings',
        'signing_place',
        'signing_date',
        'signer_name',
        'signer_capacity',
    ];

    /**
     * Key yang boleh kosong walaupun laporan sudah dikirim (PRD bagian 5).
     *
     * @var array<int, string>
     */
    public const OPTIONAL_ON_SUBMIT = [
        'activity_end_date',
        'activity_start_time',
        'activity_end_time',
    ];

    /**
     * Batas panjang teks sesuai tabel Formulir Model A pada PRD bagian 5.
     *
     * @var array<string, int>
     */
    public const MAX_LENGTHS = [
        'report_number' => 150,
        'supervisor_name' => 255,
        'supervisor_position' => 255,
        'assignment_number' => 150,
        'supervisor_address' => 2000,
        'activity_name' => 500,
        'activity_form' => 2000,
        'activity_purpose' => 5000,
        'activity_target' => 5000,
        'activity_location' => 2000,
        'findings' => 20000,
        'signing_place' => 255,
        'signer_name' => 255,
        'district_name' => 150,
        'institution_name' => 255,
        'regency_name' => 255,
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return [...self::INPUT_KEYS, ...self::SNAPSHOT_KEYS];
    }

    /**
     * Payload kosong berisi seluruh key bernilai null, sehingga bentuk JSONB
     * konsisten sejak versi pertama.
     *
     * @return array<string, string|null>
     */
    public static function empty(): array
    {
        return array_fill_keys(self::keys(), null);
    }

    /**
     * Menyaring ke key yang dikenal dan mengubah string kosong menjadi null.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>
     */
    public static function normalize(array $values): array
    {
        $normalized = [];

        foreach (self::keys() as $key) {
            $value = $values[$key] ?? null;

            if (is_string($value)) {
                // Normalisasi baris baru agar paragraf uraian tetap konsisten.
                $value = str_replace(["\r\n", "\r"], "\n", $value);
                $value = $key === 'findings' ? rtrim($value) : trim($value);
            }

            $normalized[$key] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $normalized;
    }

    /**
     * Key wajib yang masih kosong. Dipakai untuk pesan validasi submit per field.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    public static function missingRequired(array $payload): array
    {
        $required = array_values(array_diff(self::INPUT_KEYS, self::OPTIONAL_ON_SUBMIT));

        return array_values(array_filter(
            $required,
            fn (string $key): bool => ($payload[$key] ?? null) === null,
        ));
    }

    /**
     * Label bahasa Indonesia per field, dipakai pada pesan validasi dan PDF.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'report_number' => 'Nomor LHP',
            'district_name' => 'Kecamatan',
            'institution_name' => 'Nama instansi',
            'regency_name' => 'Nama kabupaten',
            'supervisor_name' => 'Nama pelaksana tugas pengawasan',
            'supervisor_position' => 'Jabatan',
            'assignment_number' => 'Nomor surat perintah tugas',
            'assignment_date' => 'Tanggal surat perintah tugas',
            'supervisor_address' => 'Alamat',
            'activity_name' => 'Kegiatan',
            'activity_form' => 'Bentuk',
            'activity_purpose' => 'Tujuan',
            'activity_target' => 'Sasaran',
            'activity_start_date' => 'Tanggal mulai',
            'activity_end_date' => 'Tanggal selesai',
            'activity_start_time' => 'Jam mulai',
            'activity_end_time' => 'Jam selesai',
            'activity_location' => 'Tempat',
            'findings' => 'Uraian singkat hasil pengawasan',
            'signing_place' => 'Tempat penandatanganan',
            'signing_date' => 'Tanggal penandatanganan',
            'signer_name' => 'Nama penandatangan',
            'signer_capacity' => 'Kedudukan penandatangan',
        ];
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    public static function signerCapacityLabel(?string $value): ?string
    {
        return SignerCapacity::tryFrom((string) $value)?->label();
    }
}
