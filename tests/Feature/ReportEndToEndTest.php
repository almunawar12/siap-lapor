<?php

use App\Enums\NoteStatus;
use App\Enums\ReportStatus;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\RevisionNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ReportTestHelpers as H;

/**
 * Alur utama PRD: kecamatan kirim → kabupaten kembalikan dengan catatan →
 * kecamatan revisi dan kirim ulang → kabupaten setujui. Diperiksa juga bahwa
 * riwayat versi, lampiran, dan audit tetap utuh sepanjang alur.
 */
it('menjalankan alur penuh submit, return, revisi, dan approve', function (): void {
    Storage::fake('local');

    $kecamatan = H::kecamatan();
    $kabupaten = H::kabupaten();
    $period = H::period();

    // 1. Kecamatan membuat draf.
    $this->actingAs($kecamatan)
        ->post('/reports', ['reporting_period_id' => $period->id])
        ->assertRedirect();

    $report = Report::query()->sole();

    // 2. Mengisi formulir dan mengunggah lampiran.
    $this->actingAs($kecamatan)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(['activity_location' => 'Kantor Kecamatan']),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kecamatan)->post("/reports/{$report->id}/attachments", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'category' => 'dokumentasi',
        'file' => UploadedFile::fake()->create('foto.pdf', 20, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    // 3. Mengirim.
    $this->actingAs($kecamatan)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertRedirect();
    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Submitted);

    // 4. Kabupaten memulai pemeriksaan dan melihat laporan.
    $this->actingAs($kabupaten)->get("/reports/{$report->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('report.capabilities.start_review', true));

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    // 5. Catatan pada field tertentu, lalu dikembalikan.
    $this->actingAs($kabupaten)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon sebutkan ruangan pada lokasi pelaksanaan.',
        'field_key' => 'activity_location',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versiDiperiksa = $report->current_version_id;

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'general_note' => 'Satu perbaikan kecil pada bagian kegiatan.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    expect($report->status)->toBe(ReportStatus::RevisionRequired);

    $note = RevisionNote::query()->sole();

    // 6. Kecamatan melihat catatan, menanggapi, memperbaiki, dan mengirim ulang.
    $this->actingAs($kecamatan)->get("/reports/{$report->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.open_notes_count', 1)
            ->where('report.notes.0.field_label', 'Tempat')
            ->where('report.capabilities.respond', true)
        );

    $this->actingAs($kecamatan)->post("/reports/{$report->id}/notes/{$note->id}/responses", [
        'lock_version' => $report->lock_version,
        'body' => 'Lokasi dilengkapi menjadi Kantor Kecamatan, Aula Utama.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kecamatan)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(['activity_location' => 'Kantor Kecamatan, Aula Utama']),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kecamatan)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertRedirect();
    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Submitted);

    // 7. Kabupaten memeriksa ulang, menyelesaikan catatan, lalu menyetujui.
    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    // Persetujuan ditolak selama catatan masih terbuka.
    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('notes');

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/notes/{$note->id}/resolve", [
        'lock_version' => $report->refresh()->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versiFinal = $report->current_version_id;

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versiFinal,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    // 8. Hasil akhir.
    $versions = ReportVersion::query()->where('report_id', $report->id)->orderBy('version_number')->get();

    expect($report->status)->toBe(ReportStatus::Approved)
        ->and($report->approved_version_id)->toBe($versiFinal)
        ->and($versions)->toHaveCount(2)
        ->and($versions[0]->id)->toBe($versiDiperiksa)
        ->and($versions[0]->payload['activity_location'])->toBe('Kantor Kecamatan')
        ->and($versions[1]->payload['activity_location'])->toBe('Kantor Kecamatan, Aula Utama')
        ->and($note->refresh()->status)->toBe(NoteStatus::Resolved)
        // Lampiran ikut tersalin ke versi revisi tanpa menduplikasi berkas.
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1)
        ->and($versions[1]->attachments()->count())->toBe(1);

    $events = ActivityLog::query()
        ->where('report_id', $report->id)
        ->orderBy('created_at')
        ->pluck('event')
        ->unique()
        ->values()
        ->all();

    expect($events)->toContain(
        'report_created',
        'draft_saved',
        'attachment_added',
        'report_submitted',
        'review_started',
        'note_added',
        'report_returned',
        'note_responded',
        'report_resubmitted',
        'note_resolved',
        'report_approved',
    );

    // Diff versi 1 → 2 menunjukkan perubahan lokasi.
    $this->actingAs($kabupaten)
        ->get("/reports/{$report->id}/versions/{$versions[1]->id}?compare={$versions[0]->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('diff.fields.0.key', 'activity_location')
            ->where('diff.fields.0.before', 'Kantor Kecamatan')
            ->where('diff.fields.0.after', 'Kantor Kecamatan, Aula Utama')
        );
});

it('menolak perbandingan dengan versi laporan lain', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    $lain = H::draft($user);

    $this->actingAs($user)
        ->get("/reports/{$report->id}/versions/{$report->current_version_id}?compare={$lain->current_version_id}")
        ->assertOk()
        // Versi milik laporan lain diabaikan, bukan dibandingkan.
        ->assertInertia(fn ($page) => $page->where('diff', null));
});
