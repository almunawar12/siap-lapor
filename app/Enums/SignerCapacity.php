<?php

namespace App\Enums;

/**
 * Kedudukan penandatangan pada blok pengesahan Formulir Model A.
 * Hanya dua nilai yang disebut PRD; jangan menambah jabatan yang belum
 * dikonfirmasi pemilik proyek.
 */
enum SignerCapacity: string
{
    case Ketua = 'ketua';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Ketua => 'Ketua',
            self::Anggota => 'Anggota',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
