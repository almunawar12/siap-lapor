/**
 * Tanggal sipil (YYYY-MM-DD) diformat tanpa konversi timezone agar tanggal
 * surat tidak bergeser. Timestamp ISO ditampilkan dalam zona Asia/Jakarta.
 */
const BULAN = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

export function formatTanggal(value: string | null): string {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return `${day} ${BULAN[month - 1]} ${year}`;
}

export function formatWaktu(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(iso));
}

export function formatUkuran(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
