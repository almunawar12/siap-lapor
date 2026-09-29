<?php

use App\Models\File as StoredFile;
use App\Models\VersionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\ReportTestHelpers as H;

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = H::kecamatan();
    $this->report = H::draft($this->user);
});

/** @param array<string, mixed> $overrides */
function upload(array $overrides = []): TestResponse
{
    $report = test()->report->refresh();

    return test()->actingAs(test()->user)->post("/reports/{$report->id}/attachments", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'category' => 'dokumentasi',
        'description' => 'Foto kegiatan',
        'file' => UploadedFile::fake()->create('bukti.pdf', 64, 'application/pdf'),
        ...$overrides,
    ]);
}

it('menyimpan lampiran pada disk privat di luar webroot', function (): void {
    upload()->assertSessionHasNoErrors();

    $file = StoredFile::query()->sole();

    expect($file->disk)->toBe('local')
        ->and($file->path)->toStartWith('attachments/')
        ->and($file->original_name)->toBe('bukti.pdf')
        ->and($file->mime_type)->toBe('application/pdf')
        ->and($file->sha256)->toHaveLength(64)
        ->and(Storage::disk('local')->exists($file->path))->toBeTrue();

    // Tidak ada berkas bukti yang bocor ke storage/app/public.
    expect(Storage::disk('local')->exists('public/'.$file->path))->toBeFalse();
});

it('menaikkan lock_version setiap perubahan lampiran', function (): void {
    upload()->assertSessionHasNoErrors();

    expect($this->report->refresh()->lock_version)->toBe(1);
});

it('tidak membocorkan path privat ke frontend', function (): void {
    upload()->assertSessionHasNoErrors();

    $this->actingAs($this->user)
        ->get("/reports/{$this->report->id}")
        ->assertInertia(fn ($page) => $page
            ->missing('report.current_version.attachments.0.path')
            ->missing('report.current_version.attachments.0.disk')
            ->missing('report.current_version.attachments.0.sha256')
            ->where('report.current_version.attachments.0.original_name', 'bukti.pdf')
        );
});

it('mengunduh lampiran milik sendiri dengan header aman', function (): void {
    upload()->assertSessionHasNoErrors();
    $attachment = VersionAttachment::query()->sole();

    $response = $this->actingAs($this->user)
        ->get("/reports/{$this->report->id}/attachments/{$attachment->id}/download");

    $response->assertOk();
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('menolak tipe berkas yang tidak diizinkan', function (): void {
    upload(['file' => UploadedFile::fake()->create('skrip.svg', 8, 'image/svg+xml')])
        ->assertSessionHasErrors('file');

    upload(['file' => UploadedFile::fake()->create('halaman.html', 8, 'text/html')])
        ->assertSessionHasErrors('file');

    expect(StoredFile::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('menolak berkas melebihi batas ukuran', function (): void {
    $maxKb = (int) config('siaplapor.attachments.max_size_kb');

    upload(['file' => UploadedFile::fake()->create('besar.pdf', $maxKb + 1, 'application/pdf')])
        ->assertSessionHasErrors('file');

    expect(StoredFile::query()->count())->toBe(0);
});

it('menolak lampiran melebihi jumlah maksimal per versi', function (): void {
    config(['siaplapor.attachments.max_files_per_version' => 2]);

    upload(['file' => UploadedFile::fake()->create('satu.pdf', 8, 'application/pdf')])->assertSessionHasNoErrors();
    upload(['file' => UploadedFile::fake()->create('dua.pdf', 8, 'application/pdf')])->assertSessionHasNoErrors();
    upload(['file' => UploadedFile::fake()->create('tiga.pdf', 8, 'application/pdf')])->assertSessionHasErrors('file');

    expect(VersionAttachment::query()->count())->toBe(2)
        // Berkas ketiga sudah ditulis sebelum transaksi gagal, lalu dibersihkan.
        ->and(Storage::disk('local')->allFiles())->toHaveCount(2);
});

it('melepas lampiran dari versi kerja tanpa menghapus baris file', function (): void {
    upload()->assertSessionHasNoErrors();
    $attachment = VersionAttachment::query()->sole();

    $this->actingAs($this->user)
        ->delete("/reports/{$this->report->id}/attachments/{$attachment->id}", [
            'lock_version' => $this->report->refresh()->lock_version,
        ])
        ->assertSessionHasNoErrors();

    expect(VersionAttachment::query()->count())->toBe(0)
        ->and(StoredFile::query()->count())->toBe(1);
});

it('menolak unggahan setelah laporan dikirim', function (): void {
    $this->report->refresh();

    $this->actingAs($this->user)->patch("/reports/{$this->report->id}", [
        'current_version_id' => $this->report->current_version_id,
        'lock_version' => $this->report->lock_version,
        ...H::completePayload(),
    ])->assertSessionHasNoErrors();

    $this->report->refresh();

    $this->actingAs($this->user)->post("/reports/{$this->report->id}/submit", [
        'current_version_id' => $this->report->current_version_id,
        'lock_version' => $this->report->lock_version,
    ])->assertRedirect();

    upload()->assertForbidden();

    expect(VersionAttachment::query()->count())->toBe(0);
});

it('menolak unggahan dengan lock_version kedaluwarsa dan membersihkan berkasnya', function (): void {
    upload(['lock_version' => 99])->assertSessionHasErrors('conflict');

    expect(StoredFile::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});
