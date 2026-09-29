<?php

use App\Models\District;
use App\Models\VersionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ReportTestHelpers as H;

/**
 * PRD AC01: akun kecamatan A tidak boleh membaca atau mengubah apa pun milik
 * kecamatan B, termasuk melalui URL langsung.
 */
beforeEach(function (): void {
    $this->districtA = District::factory()->create(['name' => 'Kecamatan A']);
    $this->districtB = District::factory()->create(['name' => 'Kecamatan B']);
    $this->userA = H::kecamatan($this->districtA);
    $this->userB = H::kecamatan($this->districtB);
    $this->reportB = H::draft($this->userB);
});

it('menolak akun kecamatan lain membaca detail laporan', function (): void {
    $this->actingAs($this->userA)
        ->get("/reports/{$this->reportB->id}")
        ->assertForbidden();
});

it('menolak akun kecamatan lain membuka form edit', function (): void {
    $this->actingAs($this->userA)
        ->get("/reports/{$this->reportB->id}/edit")
        ->assertForbidden();
});

it('menolak akun kecamatan lain menyimpan draf', function (): void {
    $this->actingAs($this->userA)
        ->patch("/reports/{$this->reportB->id}", [
            'current_version_id' => $this->reportB->current_version_id,
            'lock_version' => 0,
            'activity_name' => 'Disusupi',
        ])
        ->assertForbidden();

    expect($this->reportB->refresh()->currentVersion->payload['activity_name'])->toBeNull();
});

it('menolak akun kecamatan lain mengirim laporan', function (): void {
    $this->actingAs($this->userA)
        ->post("/reports/{$this->reportB->id}/submit", [
            'current_version_id' => $this->reportB->current_version_id,
            'lock_version' => 0,
        ])
        ->assertForbidden();
});

it('menolak akun kecamatan lain membuka versi laporan', function (): void {
    $this->actingAs($this->userA)
        ->get("/reports/{$this->reportB->id}/versions/{$this->reportB->current_version_id}")
        ->assertForbidden();
});

it('tidak menampilkan laporan kecamatan lain pada daftar', function (): void {
    H::draft($this->userA);

    $this->actingAs($this->userA)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('reports.total', 1));
});

it('mengizinkan admin kabupaten melihat seluruh kecamatan tetapi tidak mengubah', function (): void {
    H::draft($this->userA);
    $kabupaten = H::kabupaten();

    $this->actingAs($kabupaten)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('reports.total', 2));

    $this->actingAs($kabupaten)->get("/reports/{$this->reportB->id}")->assertOk();

    $this->actingAs($kabupaten)
        ->patch("/reports/{$this->reportB->id}", [
            'current_version_id' => $this->reportB->current_version_id,
            'lock_version' => 0,
            'activity_name' => 'Kabupaten mengubah substansi',
        ])
        ->assertForbidden();
});

it('menolak unduhan lampiran kecamatan lain melalui URL langsung', function (): void {
    Storage::fake('local');

    $this->actingAs($this->userB)->post("/reports/{$this->reportB->id}/attachments", [
        'current_version_id' => $this->reportB->current_version_id,
        'lock_version' => 0,
        'category' => 'lainnya',
        'file' => UploadedFile::fake()->create('bukti.pdf', 12, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $attachment = VersionAttachment::query()->sole();

    $this->actingAs($this->userA)
        ->get("/reports/{$this->reportB->id}/attachments/{$attachment->id}/download")
        ->assertForbidden();

    $this->actingAs($this->userA)
        ->delete("/reports/{$this->reportB->id}/attachments/{$attachment->id}", ['lock_version' => 1])
        ->assertForbidden();

    expect(VersionAttachment::query()->count())->toBe(1);
});

it('menolak lampiran yang diakses melalui laporan yang bukan induknya', function (): void {
    Storage::fake('local');

    $reportA = H::draft($this->userA);

    $this->actingAs($this->userB)->post("/reports/{$this->reportB->id}/attachments", [
        'current_version_id' => $this->reportB->current_version_id,
        'lock_version' => 0,
        'category' => 'lainnya',
        'file' => UploadedFile::fake()->create('bukti.pdf', 12, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $attachment = VersionAttachment::query()->sole();

    // Induk pada URL adalah laporan A, tetapi lampiran milik laporan B.
    $this->actingAs($this->userA)
        ->get("/reports/{$reportA->id}/attachments/{$attachment->id}/download")
        ->assertNotFound();
});
