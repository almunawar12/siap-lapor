<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportVersion;
use App\Services\Pdf\ReportPdf;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
    /**
     * Cetak PDF versi tertentu. Otorisasi memakai policy laporan, jadi Admin
     * Kecamatan tidak dapat mengunduh PDF kecamatan lain walaupun menebak URL
     * (PRD AC01). PDF dikirim sebagai unduhan dengan header aman.
     */
    public function __invoke(Report $report, ReportVersion $version, ReportPdf $pdf): Response
    {
        $this->authorize('view', $report);

        abort_unless($version->report_id === $report->id, 404);

        $version->loadMissing('attachments.file');

        return $pdf->render($report, $version)
            ->download($pdf->filename($report, $version))
            ->withHeaders([
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]);
    }
}
