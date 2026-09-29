<?php

use App\Enums\NoteStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\ActivityLog;
use App\Models\File;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\RevisionResponse;
use App\Models\User;
use App\Models\VersionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ReportTestHelpers as H;

/**
 * Menyiapkan laporan yang sudah dikembalikan untuk revisi, dengan satu catatan
 * terbuka pada field activity_location.
 *
 * @return array{0: User, 1: User, 2: Report, 3: RevisionNote}
 */
function returned(): array
{
    $user = H::kecamatan();
    $reviewer = H::kabupaten();
    $report = H::draft($user);

    test()->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon lengkapi lokasi pelaksanaan.',
        'field_key' => 'activity_location',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    return [$user, $reviewer, $report->refresh(), RevisionNote::query()->sole()];
}

it('kecamatan membaca catatan dan menulis tanggapan tanpa menyelesaikannya', function (): void {
    [$user, , $report, $note] = returned();

    $this->actingAs($user)->post("/reports/{$report->id}/notes/{$note->id}/responses", [
        'lock_version' => $report->lock_version,
        'body' => 'Lokasi sudah dilengkapi menjadi Kantor Kecamatan Contoh.',
    ])->assertSessionHasNoErrors();

    $response = RevisionResponse::query()->sole();

    expect($response->author_id)->toBe($user->id)
        ->and($response->report_version_id)->toBe($report->refresh()->current_version_id)
        // Tanggapan tidak otomatis menyelesaikan catatan.
        ->and($note->refresh()->status)->toBe(NoteStatus::Open);
});

it('menolak kecamatan menyelesaikan catatannya sendiri', function (): void {
    [$user, , $report, $note] = returned();

    $this->actingAs($user)->post("/reports/{$report->id}/notes/{$note->id}/resolve", [
        'lock_version' => $report->lock_version,
    ])->assertForbidden();

    expect($note->refresh()->status)->toBe(NoteStatus::Open);
});

it('menolak pengiriman ulang sebelum setiap catatan terbuka ditanggapi', function (): void {
    [$user, , $report] = returned();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('notes');

    expect($report->refresh()->status)->toBe(ReportStatus::RevisionRequired);
});

it('pengiriman ulang mempertahankan versi lama, tanggapan, dan tidak membuat versi ekstra', function (): void {
    [$user, , $report, $note] = returned();

    $versiLama = ReportVersion::query()->where('report_id', $report->id)->orderBy('version_number')->first();
    $payloadLama = $versiLama->payload;

    $this->actingAs($user)->post("/reports/{$report->id}/notes/{$note->id}/responses", [
        'lock_version' => $report->lock_version,
        'body' => 'Lokasi sudah dilengkapi.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(['activity_location' => 'Kantor Kecamatan Contoh, Aula Utama']),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    $report->refresh();
    $versions = ReportVersion::query()->where('report_id', $report->id)->orderBy('version_number')->get();

    expect($report->status)->toBe(ReportStatus::Submitted)
        // Tidak ada versi N+2.
        ->and($versions)->toHaveCount(2)
        ->and($versions[1]->submitted_at)->not->toBeNull()
        // Versi lama utuh.
        ->and($versiLama->refresh()->payload)->toBe($payloadLama)
        ->and($versions[1]->payload['activity_location'])->toBe('Kantor Kecamatan Contoh, Aula Utama')
        // Tanggapan tetap ada dan catatan masih terbuka sampai kabupaten memutuskan.
        ->and(RevisionResponse::query()->count())->toBe(1)
        ->and($note->refresh()->status)->toBe(NoteStatus::Open)
        ->and(ActivityLog::query()->where('event', 'report_resubmitted')->count())->toBe(1);
});

it('lampiran versi lama tetap ada dan link disalin ke versi kerja baru', function (): void {
    Storage::fake('local');

    $user = H::kecamatan();
    $reviewer = H::kabupaten();
    $report = H::draft($user);

    $this->actingAs($user)->post("/reports/{$report->id}/attachments", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'category' => 'surat_tugas',
        'description' => 'Surat tugas asli',
        'file' => UploadedFile::fake()->create('spt.pdf', 32, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versiPertama = $report->current_version_id;

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

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon tambahkan dokumentasi kegiatan.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $lampiranLama = VersionAttachment::query()->where('report_version_id', $versiPertama)->get();
    $lampiranBaru = VersionAttachment::query()->where('report_version_id', $report->current_version_id)->get();

    expect($lampiranLama)->toHaveCount(1)
        ->and($lampiranBaru)->toHaveCount(1)
        // Byte tidak disalin: kedua link menunjuk baris files yang sama.
        ->and($lampiranBaru->first()->file_id)->toBe($lampiranLama->first()->file_id)
        ->and($lampiranBaru->first()->description)->toBe('Surat tugas asli')
        ->and(File::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1);

    // Lampiran versi yang sudah dikirim tidak dapat dilepas.
    $this->actingAs($user)
        ->delete("/reports/{$report->id}/attachments/{$lampiranLama->first()->id}", [
            'lock_version' => $report->lock_version,
        ])
        ->assertSessionHasErrors('conflict');

    expect(VersionAttachment::query()->where('report_version_id', $versiPertama)->count())->toBe(1);
});

it('membuka kembali laporan disetujui memerlukan alasan dan menjaga keputusan lama', function (): void {
    [$user, $reviewer, $report, $note] = returned();

    // Selesaikan siklus sampai disetujui.
    $this->actingAs($user)->post("/reports/{$report->id}/notes/{$note->id}/responses", [
        'lock_version' => $report->lock_version,
        'body' => 'Sudah dilengkapi.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes/{$note->id}/resolve", [
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versiDisetujui = $report->current_version_id;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versiDisetujui,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Approved)
        ->and($report->approved_version_id)->toBe($versiDisetujui);

    // Alasan wajib.
    $this->actingAs($reviewer)->post("/reports/{$report->id}/reopen", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('reason');

    expect($report->refresh()->status)->toBe(ReportStatus::Approved);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/reopen", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'reason' => 'Ditemukan kekeliruan pada tanggal penandatanganan.',
    ])->assertSessionHasNoErrors();

    $report->refresh();
    $versions = ReportVersion::query()->where('report_id', $report->id)->orderBy('version_number')->get();

    expect($report->status)->toBe(ReportStatus::RevisionRequired)
        // Keluar dari hitungan approved.
        ->and($report->approved_version_id)->toBeNull()
        ->and($versions)->toHaveCount(3)
        ->and($report->current_version_id)->toBe($versions[2]->id)
        ->and($versions[2]->submitted_at)->toBeNull()
        // Riwayat persetujuan lama tidak hilang.
        ->and(Review::query()->where('status', ReviewStatus::Approved)->count())->toBe(1)
        ->and(Review::query()->where('status', ReviewStatus::Approved)->sole()->report_version_id)
        ->toBe($versiDisetujui)
        // Alasan menjadi catatan umum terbuka pada review persetujuan lama.
        ->and(RevisionNote::query()->where('status', NoteStatus::Open)->count())->toBe(1);

    $log = ActivityLog::query()->where('event', 'report_reopened')->sole();
    expect($log->metadata['reason'])->toContain('tanggal penandatanganan');
});

it('menolak admin kecamatan membuka kembali laporan disetujui', function (): void {
    [$user, , $report] = returned();

    $this->actingAs($user)->post("/reports/{$report->id}/reopen", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'reason' => 'Ingin mengubah isi laporan yang sudah disetujui.',
    ])->assertForbidden();
});

it('menolak catatan dan tanggapan lintas laporan melalui URL langsung', function (): void {
    [, , $report, $note] = returned();
    $lain = H::kecamatan();
    $reportLain = H::draft($lain);

    // Catatan milik laporan lain: 404, bukan 403, karena bukan anak laporan ini.
    $this->actingAs($lain)
        ->post("/reports/{$reportLain->id}/notes/{$note->id}/responses", [
            'lock_version' => $reportLain->lock_version,
            'body' => 'Menanggapi catatan laporan orang lain.',
        ])
        ->assertNotFound();

    // Kecamatan lain menanggapi catatan pada laporan aslinya: ditolak policy.
    $this->actingAs($lain)
        ->post("/reports/{$report->id}/notes/{$note->id}/responses", [
            'lock_version' => $report->lock_version,
            'body' => 'Menanggapi catatan kecamatan lain.',
        ])
        ->assertForbidden();

    expect(RevisionResponse::query()->count())->toBe(0);
});

it('menolak catatan yang merujuk field tak dikenal atau dua target sekaligus', function (): void {
    [, $reviewer, $report] = returned();

    // Laporan sedang revision_required, jadi mulai siklus baru lebih dulu.
    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Catatan pada field karangan.',
        'field_key' => 'field_karangan',
    ])->assertSessionHasErrors('field_key');

    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Dua target sekaligus.',
        'field_key' => 'findings',
        'attachment_id' => 1,
    ])->assertSessionHasErrors('field_key');
});
