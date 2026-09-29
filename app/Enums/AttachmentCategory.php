<?php

namespace App\Enums;

/**
 * Kategori lampiran bersifat penanda arsip, bukan daftar dokumen wajib.
 * PRD menegaskan lampiran tetap opsional.
 */
enum AttachmentCategory: string
{
    case SuratTugas = 'surat_tugas';
    case Dokumentasi = 'dokumentasi';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::SuratTugas => 'Surat Perintah Tugas',
            self::Dokumentasi => 'Dokumentasi Kegiatan',
            self::Lainnya => 'Lampiran Lain',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
