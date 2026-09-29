<?php

namespace App\Enums;

enum UserRole: string
{
    case AdminKecamatan = 'admin_kecamatan';
    case AdminKabupaten = 'admin_kabupaten';

    public function label(): string
    {
        return match ($this) {
            self::AdminKecamatan => 'Admin Kecamatan',
            self::AdminKabupaten => 'Admin Kabupaten',
        };
    }

    /**
     * Role that must be bound to exactly one district.
     */
    public function requiresDistrict(): bool
    {
        return $this === self::AdminKecamatan;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
