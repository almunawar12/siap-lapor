<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequired = 'revision_required';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Diajukan',
            self::UnderReview => 'Sedang Diperiksa',
            self::RevisionRequired => 'Perlu Revisi',
            self::Approved => 'Disetujui',
        };
    }

    /**
     * Status yang membolehkan Admin Kecamatan menyunting versi kerja.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::RevisionRequired], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
