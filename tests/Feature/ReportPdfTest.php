<?php

use App\Models\District;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Services\Pdf\ReportPdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ReportTestHelpers as H;

/**
 * Menyiapkan laporan terkirim beserta versinya.
 *
 * @param  array<string, string|null>  $overrides
 * @return array{0: User, 1: Report}
 */
function submittedForPdf(array $overrides = []): array
{
    $user = H::kecamatan();
    $report = H::draft($user);

    test()->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload($overrides),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    return [$user, $report->refresh()];
}

/**
 * Isi surat diuji pada tingkat HTML template. Dompdf mensubset font dan
 * mengodekan teks di dalam stream biner, sehingga menguji isi dari biner PDF
 * akan menguji decoder buatan sendiri, bukan template.
 */
function renderedHtml(Report $report, ?int $versionId = null): string
{
    /** @var ReportVersion $version */
    $version = ReportVersion::query()
        ->whereKey($versionId ?? $report->current_version_id)
        ->sole();

    $pdf = app(ReportPdf::class);

    return view('pdf.model-a', $pdf->viewData($report, $version))->render();
}

/** Jumlah halaman ditanyakan langsung ke canvas Dompdf. */
function pdfPageCount(Report $report, ?int $versionId = null): int
{
    /** @var ReportVersion $version */
    $version = ReportVersion::query()
        ->whereKey($versionId ?? $report->current_version_id)
        ->sole();

    $pdf = app(ReportPdf::class)->render($report, $version);
    $pdf->output();

    return $pdf->getDomPDF()->getCanvas()->get_page_count();
}

it('mengunduh PDF versi terkirim sebagai attachment dengan header aman', function (): void {
    [$user, $report] = submittedForPdf();

    $response = $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf");

    $response->assertOk();

    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->getContent())->toStartWith('%PDF-')
        ->and(pdfPageCount($report))->toBeGreaterThanOrEqual(1);
});

it('menyusun nama berkas dari nomor LHP yang disanitasi', function (): void {
    [$user, $report] = submittedForPdf(['report_number' => 'LHP/007/IV/2026']);

    $response = $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf");

    // Garis miring diganti tanda hubung: tidak ada karakter path pada nama berkas.
    expect($response->headers->get('content-disposition'))
        ->toContain('LHP-LHP-007-IV-2026-v1.pdf');
});

it('mencetak judul Formulir Model A, nomor, bagian I sampai III, dan pengesahan', function (): void {
    config([
        'instansi.institution_name' => 'Instansi Uji',
        'instansi.regency_name' => 'Kabupaten Uji',
    ]);

    $district = District::factory()->create(['name' => 'Kecamatan Contoh']);
    $user = H::kecamatan($district);
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $html = renderedHtml($report);

    expect($html)
        ->toContain('FORMULIR MODEL A')
        ->toContain('Laporan Hasil Pengawasan')
        ->toContain('LHP/001/III/2026')
        ->toContain('I. Data Pengawas Pemilu')
        ->toContain('II. Kegiatan Pengawasan')
        ->toContain('III. Uraian Singkat Hasil Pengawasan')
        ->toContain('Siti Pengawas')
        ->toContain('SPT/010/III/2026')
        ->toContain('1 Maret 2026')
        ->toContain('Kecamatan Contoh')
        ->toContain('Instansi Uji')
        ->toContain('Kabupaten Uji')
        ->toContain('Budi Ketua')
        ->toContain('Ketua Pengawas Pemilu')
        ->toContain('7 Maret 2026')
        ->toContain('Persetujuan pada aplikasi bukan tanda tangan elektronik');
});

it('mempertahankan paragraf dan Unicode pada uraian', function (): void {
    [$user, $report] = submittedForPdf([
        'findings' => "Paragraf pertama.\n\nParagraf kedua dengan é, ±, dan “kutip”.",
    ]);

    $html = renderedHtml($report);

    expect($html)
        ->toContain("Paragraf pertama.\n\nParagraf kedua")
        ->toContain('é')
        ->toContain('±')
        // white-space: pre-line menjaga paragraf tanpa markup tambahan.
        ->toContain('white-space: pre-line');
});

it('meng-escape teks pengguna dan tidak merender uraian sebagai HTML', function (): void {
    [$user, $report] = submittedForPdf([
        'findings' => '<script>alert(1)</script> dan <b>tebal</b>',
        'activity_name' => 'Kegiatan <img src=x onerror=alert(1)>',
    ]);

    $html = renderedHtml($report);

    expect($html)
        ->not->toContain('<script>alert(1)</script>')
        ->not->toContain('<img src=x')
        ->toContain('&lt;script&gt;')
        ->toContain('&lt;b&gt;tebal&lt;/b&gt;');
});

it('menandai versi kerja yang belum dikirim sebagai belum terverifikasi', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertOk();

    expect(renderedHtml($report))->toContain('BELUM TERVERIFIKASI');
});

it('menandai versi historis dan menghapus penanda pada persetujuan aktif', function (): void {
    [$user, $report] = submittedForPdf();
    $reviewer = H::kabupaten();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versiPertama = $report->current_version_id;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versiPertama,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    // Persetujuan aktif: tanpa penanda apa pun.
    $aktif = renderedHtml($report, $versiPertama);

    expect($aktif)->not->toContain('BELUM TERVERIFIKASI')
        ->and($aktif)->not->toContain('VERSI HISTORIS');

    // Dibuka kembali: versi lama menjadi historis, versi kerja baru belum terverifikasi.
    $this->actingAs($reviewer)->post("/reports/{$report->id}/reopen", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'reason' => 'Ditemukan kekeliruan pada tanggal penandatanganan.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    expect(renderedHtml($report, $versiPertama))->toContain('VERSI HISTORIS')
        ->and(renderedHtml($report))->toContain('BELUM TERVERIFIKASI');
});

it('memakai snapshot versi historis, bukan isi draf terkini', function (): void {
    [$user, $report] = submittedForPdf(['activity_name' => 'Kegiatan versi pertama']);
    $reviewer = H::kabupaten();

    $versiPertama = $report->current_version_id;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon perbaiki nama kegiatan.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(['activity_name' => 'Kegiatan versi kedua']),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $lama = renderedHtml($report, $versiPertama);

    expect($lama)->toContain('Kegiatan versi pertama')
        ->and($lama)->not->toContain('Kegiatan versi kedua')
        ->and(renderedHtml($report))->toContain('Kegiatan versi kedua');
});

it('mencetak uraian panjang lebih dari satu halaman tanpa memotong isi', function (): void {
    $paragraf = collect(range(1, 80))
        ->map(fn (int $i): string => "Paragraf nomor {$i} berisi uraian hasil pengawasan yang cukup panjang "
            .'untuk memastikan dokumen melampaui satu halaman dan tidak terpotong.')
        ->implode("\n\n");

    [$user, $report] = submittedForPdf(['findings' => $paragraf]);

    $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertOk();

    $html = renderedHtml($report);

    expect(pdfPageCount($report))->toBeGreaterThan(1)
        // Seluruh uraian masuk ke dokumen, termasuk paragraf terakhir.
        ->and($html)->toContain('Paragraf nomor 1 ')
        ->and($html)->toContain('Paragraf nomor 80 ')
        // Blok pengesahan tetap ada setelah uraian panjang dan tidak dipecah.
        ->and($html)->toContain('Budi Ketua')
        ->and($html)->toContain('page-break-inside: avoid');
});

it('menghasilkan satu halaman untuk uraian pendek', function (): void {
    [$user, $report] = submittedForPdf(['findings' => 'Uraian singkat satu baris.']);

    $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertOk();

    expect(pdfPageCount($report))->toBe(1);
});

it('mencetak daftar lampiran versi tersebut', function (): void {
    Storage::fake('local');

    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->post("/reports/{$report->id}/attachments", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'category' => 'surat_tugas',
        'description' => 'Surat tugas asli',
        'file' => UploadedFile::fake()->create('spt-maret.pdf', 16, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $html = renderedHtml($report);

    expect($html)
        ->toContain('spt-maret.pdf')
        ->toContain('Surat Perintah Tugas')
        ->toContain('Surat tugas asli');
});

it('menolak unduhan PDF laporan kecamatan lain melalui URL langsung', function (): void {
    [, $report] = submittedForPdf();
    $lain = H::kecamatan();

    $this->actingAs($lain)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertForbidden();
});

it('menolak unduhan PDF oleh tamu', function (): void {
    [, $report] = submittedForPdf();

    // Helper penyiapan memakai actingAs, yang bertahan sepanjang test case.
    $this->app['auth']->forgetGuards();

    $this->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertRedirect('/login');
});

it('mengizinkan admin kabupaten mengunduh PDF seluruh kecamatan', function (): void {
    [, $report] = submittedForPdf();

    $this->actingAs(H::kabupaten())
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}/pdf")
        ->assertOk();
});

it('menolak PDF versi yang bukan milik laporan pada URL', function (): void {
    [$user, $report] = submittedForPdf();
    $lain = H::draft($user);

    $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$lain->current_version_id}/pdf")
        ->assertNotFound();
});
