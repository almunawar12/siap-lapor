<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Active = 'active';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Sedang Diperiksa',
            self::ChangesRequested => 'Dikembalikan',
            self::Approved => 'Disetujui',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
