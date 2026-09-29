<?php

use App\Enums\NoteStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\User;
use Tests\ReportTestHelpers as H;

/**
 * Menyiapkan laporan yang sudah dikirim dan siap diperiksa.
 *
 * @return array{0: User, 1: Report}
 */
function submitted(): array
{
    $user = H::kecamatan();
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
    ])->assertRedirect();

    return [$user, $report->refresh()];
}

/** Memulai pemeriksaan sebagai admin kabupaten. */
function startReview(User $reviewer, Report $report): void
{
    test()->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    $report->refresh();
}

/** @param array<string, mixed> $overrides */
function addNote(User $reviewer, Report $report, array $overrides = []): RevisionNote
{
    test()->actingAs($reviewer)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->refresh()->lock_version,
        'body' => 'Mohon lengkapi lokasi pelaksanaan.',
        ...$overrides,
    ])->assertSessionHasNoErrors();

    $report->refresh();

    return RevisionNote::query()->latest('id')->sole();
}

it('memulai pemeriksaan dari status diajukan', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();

    startReview($reviewer, $report);

    $review = Review::query()->sole();

    expect($report->status)->toBe(ReportStatus::UnderReview)
        ->and($review->reviewer_id)->toBe($reviewer->id)
        ->and($review->status)->toBe(ReviewStatus::Active)
        ->and($review->report_version_id)->toBe($report->current_version_id)
        ->and($review->decided_at)->toBeNull();
});

it('menolak admin kecamatan memulai pemeriksaan', function (): void {
    [$user, $report] = submitted();

    $this->actingAs($user)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertForbidden();

    expect($report->refresh()->status)->toBe(ReportStatus::Submitted);
});

it('hanya membuat satu pemeriksaan aktif walaupun mulai diklik berulang', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();

    $versionId = $report->current_version_id;
    $lock = $report->lock_version;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertSessionHasNoErrors();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertSessionHasNoErrors();

    expect(Review::query()->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'review_started')->count())->toBe(1);
});

it('menolak pemeriksa lain memutuskan tanpa mengambil alih', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    $lain = H::kabupaten();

    startReview($reviewer, $report);

    $this->actingAs($lain)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Catatan dari pemeriksa lain.',
    ])->assertSessionHasErrors('conflict');

    $this->actingAs($lain)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('conflict');

    expect(RevisionNote::query()->count())->toBe(0)
        ->and($report->refresh()->status)->toBe(ReportStatus::UnderReview);
});

it('mengalihkan pemeriksaan dengan alasan tercatat', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    $lain = H::kabupaten();

    startReview($reviewer, $report);

    $this->actingAs($lain)->post("/reports/{$report->id}/review/takeover", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'reason' => 'Pemeriksa sebelumnya sedang bertugas di luar kantor.',
    ])->assertSessionHasNoErrors();

    $review = Review::query()->sole();
    $log = ActivityLog::query()->where('event', 'review_taken_over')->sole();

    expect($review->reviewer_id)->toBe($lain->id)
        ->and($log->metadata['previous_reviewer_id'])->toBe($reviewer->id)
        ->and($log->metadata['new_reviewer_id'])->toBe($lain->id)
        ->and($log->metadata['reason'])->toContain('bertugas di luar kantor');
});

it('menolak pengalihan pemeriksaan tanpa alasan', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    $lain = H::kabupaten();

    startReview($reviewer, $report);

    $this->actingAs($lain)->post("/reports/{$report->id}/review/takeover", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('reason');

    expect(Review::query()->sole()->reviewer_id)->toBe($reviewer->id);
});

it('menolak pengembalian tanpa catatan terbuka', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('notes');

    expect($report->refresh()->status)->toBe(ReportStatus::UnderReview)
        ->and(ReportVersion::query()->where('report_id', $report->id)->count())->toBe(1);
});

it('pengembalian valid membuat tepat satu versi kerja baru', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);

    $reviewedVersionId = $report->current_version_id;
    addNote($reviewer, $report, ['field_key' => 'activity_location']);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'general_note' => 'Mohon perbaiki bagian kegiatan.',
    ])->assertSessionHasNoErrors();

    $report->refresh();
    $versions = ReportVersion::query()->where('report_id', $report->id)->orderBy('version_number')->get();

    expect($report->status)->toBe(ReportStatus::RevisionRequired)
        ->and($versions)->toHaveCount(2)
        ->and($report->current_version_id)->toBe($versions[1]->id)
        ->and($versions[1]->version_number)->toBe(2)
        ->and($versions[1]->submitted_at)->toBeNull()
        // Versi yang diperiksa tidak diubah.
        ->and($versions[0]->id)->toBe($reviewedVersionId)
        ->and($versions[0]->submitted_at)->not->toBeNull()
        ->and($versions[1]->payload['activity_name'])->toBe($versions[0]->payload['activity_name']);

    $review = Review::query()->sole();
    expect($review->status)->toBe(ReviewStatus::ChangesRequested)
        ->and($review->decided_at)->not->toBeNull()
        ->and($review->report_version_id)->toBe($reviewedVersionId);
});

it('menolak persetujuan bila masih ada catatan terbuka', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);
    addNote($reviewer, $report);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('notes');

    expect($report->refresh()->status)->toBe(ReportStatus::UnderReview)
        ->and($report->approved_version_id)->toBeNull();
});

it('menyetujui versi yang tepat setelah catatan diselesaikan', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);

    $note = addNote($reviewer, $report);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/notes/{$note->id}/resolve", [
        'lock_version' => $report->refresh()->lock_version,
    ])->assertSessionHasNoErrors();

    $report->refresh();
    $versionId = $report->current_version_id;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versionId,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Approved)
        ->and($report->approved_version_id)->toBe($versionId)
        ->and($report->current_version_id)->toBe($versionId)
        ->and(Review::query()->sole()->status)->toBe(ReviewStatus::Approved)
        ->and($note->refresh()->status)->toBe(NoteStatus::Resolved);
});

it('menolak keputusan berbasis lock_version kedaluwarsa', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version - 1,
    ])->assertSessionHasErrors('conflict');

    expect($report->refresh()->status)->toBe(ReportStatus::UnderReview);
});

it('menolak dua permintaan persetujuan bersamaan sehingga hanya satu keputusan sah', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();
    startReview($reviewer, $report);

    $versionId = $report->current_version_id;
    $lock = $report->lock_version;

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertSessionHasNoErrors();

    // Permintaan kedua memakai penanda lama: ditolak sebagai konflik.
    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertSessionHasErrors('conflict');

    expect(Review::query()->where('status', ReviewStatus::Approved)->count())->toBe(1)
        ->and(ActivityLog::query()->where('event', 'report_approved')->count())->toBe(1);
});

it('menolak pengembalian dan persetujuan di luar status sedang diperiksa', function (): void {
    [, $report] = submitted();
    $reviewer = H::kabupaten();

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('conflict');

    $this->actingAs($reviewer)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors('conflict');

    expect($report->refresh()->status)->toBe(ReportStatus::Submitted);
});
