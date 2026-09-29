<?php

use App\Enums\NoteStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\RevisionNote;
use App\Models\VersionAttachment;
use Illuminate\Database\QueryException;
use Tests\ReportTestHelpers as H;

it('menolak dua pemeriksaan aktif pada satu versi di level database', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
    ]);

    expect(fn () => Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
    ]))->toThrow(QueryException::class);
});

it('mengizinkan pemeriksaan baru setelah yang lama diputuskan', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
        'status' => ReviewStatus::ChangesRequested->value,
        'decided_at' => now(),
    ]);

    Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
    ]);

    expect(Review::query()->count())->toBe(2);
});

it('menolak pemeriksaan aktif yang punya decided_at', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    expect(fn () => Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
        'status' => ReviewStatus::Active->value,
        'decided_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('menolak keputusan tanpa decided_at', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);

    expect(fn () => Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
        'status' => ReviewStatus::Approved->value,
        'decided_at' => null,
    ]))->toThrow(QueryException::class);
});

it('menolak catatan yang merujuk field dan lampiran sekaligus di level database', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    $review = Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
    ]);

    expect(fn () => RevisionNote::factory()->create([
        'review_id' => $review->id,
        'field_key' => 'findings',
        'attachment_id' => VersionAttachment::factory()->create([
            'report_version_id' => $report->current_version_id,
        ])->id,
    ]))->toThrow(QueryException::class);
});

it('menolak catatan selesai tanpa pelaku dan waktu penyelesaian', function (): void {
    $user = H::kecamatan();
    $report = H::draft($user);
    $review = Review::factory()->create([
        'report_version_id' => $report->current_version_id,
        'reviewer_id' => H::kabupaten()->id,
    ]);

    expect(fn () => RevisionNote::factory()->create([
        'review_id' => $review->id,
        'status' => NoteStatus::Resolved->value,
    ]))->toThrow(QueryException::class);
});
