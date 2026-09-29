import { formatTanggal } from '@/lib/format';
import type { ReportDetail, ReportVersion } from '@/types';

/**
 * Pratinjau tata letak Formulir Model A. Semua teks dirender sebagai teks
 * biasa; tidak ada dangerouslySetInnerHTML. Uraian memakai whitespace-pre-line
 * agar paragraf terjaga.
 */
function Row({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid gap-1 border-b py-2 sm:grid-cols-[14rem_1fr] sm:gap-4">
            <dt className="text-sm font-medium">{label}</dt>
            <dd className="text-sm whitespace-pre-line">{value || '—'}</dd>
        </div>
    );
}

export function ReportPreview({
    report,
    version,
}: {
    report: ReportDetail;
    version: ReportVersion;
}) {
    const p = version.payload;

    const waktu = [
        formatTanggal(p.activity_start_date),
        p.activity_end_date
            ? `s.d. ${formatTanggal(p.activity_end_date)}`
            : null,
        p.activity_start_time
            ? `pukul ${p.activity_start_time}${p.activity_end_time ? `–${p.activity_end_time}` : ''} WIB`
            : null,
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <article className="space-y-6">
            <header className="space-y-1 text-center">
                <p className="text-xs tracking-widest uppercase">
                    Formulir Model A
                </p>
                <h2 className="text-base font-semibold">
                    Laporan Hasil Pengawasan
                </h2>
                <p className="text-sm">Nomor: {p.report_number || '—'}</p>
                {version.submitted_at === null ? (
                    <p className="text-xs font-semibold text-destructive">
                        BELUM TERVERIFIKASI
                    </p>
                ) : null}
                <p className="text-xs text-muted-foreground">
                    {p.district_name ?? report.district.name}
                    {p.institution_name ? ` · ${p.institution_name}` : ''}
                    {p.regency_name ? ` · ${p.regency_name}` : ''}
                </p>
            </header>

            <section>
                <h3 className="mb-2 text-sm font-semibold">
                    I. Data Pengawas Pemilu
                </h3>
                <dl>
                    <Row label="Nama" value={p.supervisor_name} />
                    <Row label="Jabatan" value={p.supervisor_position} />
                    <Row
                        label="Nomor surat perintah tugas"
                        value={p.assignment_number}
                    />
                    <Row
                        label="Tanggal surat perintah tugas"
                        value={formatTanggal(p.assignment_date)}
                    />
                    <Row label="Alamat" value={p.supervisor_address} />
                </dl>
            </section>

            <section>
                <h3 className="mb-2 text-sm font-semibold">
                    II. Kegiatan Pengawasan
                </h3>
                <dl>
                    <Row label="Kegiatan" value={p.activity_name} />
                    <Row label="Bentuk" value={p.activity_form} />
                    <Row label="Tujuan" value={p.activity_purpose} />
                    <Row label="Sasaran" value={p.activity_target} />
                    <Row label="Waktu" value={waktu || null} />
                    <Row label="Tempat" value={p.activity_location} />
                </dl>
            </section>

            <section>
                <h3 className="mb-2 text-sm font-semibold">
                    III. Uraian Singkat Hasil Pengawasan
                </h3>
                <p className="text-sm whitespace-pre-line">
                    {p.findings || '—'}
                </p>
            </section>

            <section className="flex justify-end">
                <div className="space-y-12 text-sm">
                    <p>
                        {p.signing_place || '—'},{' '}
                        {formatTanggal(p.signing_date)}
                    </p>
                    <div className="space-y-1">
                        <p className="font-medium">{p.signer_name || '—'}</p>
                        <p className="text-muted-foreground">
                            {p.signer_capacity === 'ketua'
                                ? 'Ketua'
                                : p.signer_capacity === 'anggota'
                                  ? 'Anggota'
                                  : '—'}{' '}
                            Pengawas Pemilu
                        </p>
                    </div>
                </div>
            </section>

            {version.attachments.length > 0 ? (
                <section>
                    <h3 className="mb-2 text-sm font-semibold">
                        Lampiran ({version.attachments.length})
                    </h3>
                    <ul className="list-inside list-disc text-sm">
                        {version.attachments.map((attachment) => (
                            <li key={attachment.id}>
                                {attachment.original_name} —{' '}
                                {attachment.category_label}
                            </li>
                        ))}
                    </ul>
                </section>
            ) : null}
        </article>
    );
}
