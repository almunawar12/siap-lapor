<?php

namespace App\Services\Pdf;

use App\Enums\ReviewStatus;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Support\ReportPayload;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\App;

/**
 * Cetak Formulir Model A dari snapshot satu versi.
 *
 * Isi selalu berasal dari payload versi yang diminta, bukan dari draf terkini,
 * sehingga unduhan versi historis tetap sesuai surat aslinya (PRD bagian 9).
 * Persetujuan aplikasi bukan tanda tangan elektronik; template hanya menyediakan
 * ruang tanda tangan.
 */
class ReportPdf
{
    /** Dinaikkan bila tata letak template berubah; ikut tercatat di metadata. */
    public const TEMPLATE_VERSION = 1;

    public function render(Report $report, ReportVersion $version): PDF
    {
        /** @var PDF $pdf */
        $pdf = App::make('dompdf.wrapper');

        $pdf->setPaper('a4', 'portrait');

        // Remote asset fetching dinonaktifkan: tidak ada gambar/CSS eksternal.
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->loadView('pdf.model-a', $this->viewData($report, $version));
    }

    /**
     * Data template, dipisahkan agar isi surat dapat diuji pada tingkat HTML
     * tanpa mengurai ulang biner PDF (font Dompdf disubset dan teksnya dikodekan).
     *
     * @return array<string, mixed>
     */
    public function viewData(Report $report, ReportVersion $version): array
    {
        $payload = $version->normalizedPayload();

        return [
            'report' => $report,
            'version' => $version,
            'payload' => $payload,
            'marker' => $this->marker($report, $version),
            'signerCapacity' => ReportPayload::signerCapacityLabel($payload['signer_capacity']),
            'templateVersion' => self::TEMPLATE_VERSION,
            'generatedAt' => now(),
        ];
    }

    public function filename(Report $report, ReportVersion $version): string
    {
        $number = $version->payload['report_number'] ?? $report->report_number ?? 'tanpa-nomor';

        // Nama berkas disanitasi: hanya huruf, angka, titik, dan tanda hubung.
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $number) ?? 'lhp';

        return sprintf('LHP-%s-v%d.pdf', trim($safe, '-'), $version->version_number);
    }

    /**
     * Penanda status versi (PRD bagian 9):
     * - belum pernah disetujui → BELUM TERVERIFIKASI
     * - pernah disetujui tetapi bukan persetujuan aktif → VERSI HISTORIS
     * - persetujuan aktif → tanpa penanda
     *
     * @return array{label: string, note: string}|null
     */
    protected function marker(Report $report, ReportVersion $version): ?array
    {
        if ($report->approved_version_id === $version->id) {
            return null;
        }

        $wasApproved = Review::query()
            ->where('report_version_id', $version->id)
            ->where('status', ReviewStatus::Approved)
            ->exists();

        if ($wasApproved) {
            return [
                'label' => 'VERSI HISTORIS',
                'note' => 'Versi ini pernah disetujui, tetapi bukan persetujuan yang berlaku saat ini.',
            ];
        }

        return [
            'label' => 'BELUM TERVERIFIKASI',
            'note' => 'Versi ini belum disetujui Admin Kabupaten.',
        ];
    }
}
