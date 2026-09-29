<?php

use App\Models\Report;
use App\Models\ReportNotification;
use App\Models\ReportVersion;
use App\Models\RevisionNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\ReportTestHelpers as H;

/**
 * @return array{0: User, 1: Report}
 */
function submittedForNotification(): array
{
    $user = H::kecamatan();
    $report = H::draft($user);

    test()->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        // Nomor LHP unik per instalasi, jadi setiap laporan uji memakai nomor sendiri.
        ...H::completePayload(['report_number' => "LHP/{$report->id}/III/2026"]),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    test()->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    return [$user, $report->refresh()];
}

/** @return Collection<int, ReportNotification> */
function notificationsFor(User $user)
{
    return ReportNotification::query()
        ->where('notifiable_type', $user->getMorphClass())
        ->where('notifiable_id', $user->getKey())
        ->orderBy('created_at')
        ->get();
}

it('memberi notifikasi ke admin kabupaten aktif saat laporan dikirim', function (): void {
    $kabupaten = H::kabupaten();
    $kabupatenLain = H::kabupaten();
    $nonaktif = User::factory()->kabupaten()->inactive()->create();

    [, $report] = submittedForNotification();

    expect(notificationsFor($kabupaten))->toHaveCount(1)
        ->and(notificationsFor($kabupatenLain))->toHaveCount(1)
        // Akun nonaktif tidak menerima notifikasi.
        ->and(notificationsFor($nonaktif))->toHaveCount(0);

    $notification = notificationsFor($kabupaten)->sole();

    expect($notification->data['event'])->toBe('report_submitted')
        ->and($notification->data['title'])->toBe('Laporan baru diajukan')
        ->and($notification->report_id)->toBe($report->id)
        ->and($notification->event_id)->toBe("report_submitted:{$report->id}:{$report->current_version_id}")
        ->and($notification->read_at)->toBeNull()
        // Uraian hasil pengawasan tidak disalin ke notifikasi.
        ->and($notification->data['body'])->not->toContain('Paragraf pertama');
});

it('tidak memberi notifikasi ke akun kecamatan saat laporan dikirim sendiri', function (): void {
    H::kabupaten();
    [$user] = submittedForNotification();

    expect(notificationsFor($user))->toHaveCount(0);
});

it('tidak menggandakan notifikasi ketika kirim diklik berulang', function (): void {
    $kabupaten = H::kabupaten();
    $user = H::kecamatan();
    $report = H::draft($user);

    $this->actingAs($user)->patch("/reports/{$report->id}", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        ...H::completePayload(),
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $versionId = $report->current_version_id;
    $lock = $report->lock_version;

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertRedirect();

    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $versionId,
        'lock_version' => $lock,
    ])->assertRedirect();

    expect(notificationsFor($kabupaten))->toHaveCount(1);
});

it('tidak membuat notifikasi ketika transaksi gagal', function (): void {
    $kabupaten = H::kabupaten();
    $user = H::kecamatan();
    $report = H::draft($user);

    // Field wajib masih kosong: submit ditolak, jadi tidak ada notifikasi.
    $this->actingAs($user)->post("/reports/{$report->id}/submit", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasErrors();

    expect(notificationsFor($kabupaten))->toHaveCount(0)
        ->and(ReportNotification::query()->count())->toBe(0);
});

it('memberi notifikasi ke kecamatan saat dikembalikan, disetujui, dan dibuka kembali', function (): void {
    $kabupaten = H::kabupaten();
    [$user, $report] = submittedForNotification();

    // Mulai pemeriksaan lalu kembalikan.
    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon lengkapi lokasi pelaksanaan.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    expect(notificationsFor($user)->pluck('data.event')->all())->toBe(['report_returned']);

    // Tanggapi, kirim ulang, setujui.
    $note = RevisionNote::query()->sole();

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

    expect(notificationsFor($kabupaten)->pluck('data.event')->all())
        ->toBe(['report_submitted', 'report_resubmitted']);

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/notes/{$note->id}/resolve", [
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/approve", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/reopen", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
        'reason' => 'Ditemukan kekeliruan pada tanggal penandatanganan.',
    ])->assertSessionHasNoErrors();

    expect(notificationsFor($user)->pluck('data.event')->all())
        ->toBe(['report_returned', 'report_approved', 'report_reopened']);
});

it('hanya mengirim notifikasi ke kecamatan pada wilayah laporan', function (): void {
    H::kabupaten();
    [$user, $report] = submittedForNotification();
    $kecamatanLain = H::kecamatan();
    $kabupaten = H::kabupaten();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/start", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/notes", [
        'lock_version' => $report->lock_version,
        'body' => 'Mohon perbaiki.',
    ])->assertSessionHasNoErrors();
    $report->refresh();

    $this->actingAs($kabupaten)->post("/reports/{$report->id}/review/return", [
        'current_version_id' => $report->current_version_id,
        'lock_version' => $report->lock_version,
    ])->assertSessionHasNoErrors();

    expect(notificationsFor($user))->toHaveCount(1)
        ->and(notificationsFor($kecamatanLain))->toHaveCount(0);
});

it('menampilkan hanya notifikasi milik pengguna sendiri', function (): void {
    $kabupaten = H::kabupaten();
    $kabupatenLain = H::kabupaten();
    submittedForNotification();

    $this->actingAs($kabupaten)
        ->get('/notifikasi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->where('notifications.total', 1)
            ->where('unread_count', 1)
        );

    expect(notificationsFor($kabupatenLain))->toHaveCount(1);
});

it('menandai satu notifikasi sudah dibaca', function (): void {
    $kabupaten = H::kabupaten();
    submittedForNotification();

    $notification = notificationsFor($kabupaten)->sole();

    $this->actingAs($kabupaten)
        ->patch("/notifikasi/{$notification->id}")
        ->assertSessionHasNoErrors();

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('menolak menandai notifikasi pengguna lain sudah dibaca', function (): void {
    $kabupaten = H::kabupaten();
    $kabupatenLain = H::kabupaten();
    submittedForNotification();

    $milikLain = notificationsFor($kabupatenLain)->sole();

    $this->actingAs($kabupaten)
        ->patch("/notifikasi/{$milikLain->id}")
        ->assertNotFound();

    expect($milikLain->refresh()->read_at)->toBeNull();
});

it('menandai semua notifikasi sudah dibaca', function (): void {
    $kabupaten = H::kabupaten();
    submittedForNotification();
    submittedForNotification();

    expect(notificationsFor($kabupaten))->toHaveCount(2);

    $this->actingAs($kabupaten)
        ->patch('/notifikasi/baca-semua')
        ->assertSessionHasNoErrors();

    expect(notificationsFor($kabupaten)->whereNull('read_at'))->toHaveCount(0);
});

it('membagikan jumlah notifikasi belum dibaca pada props layout', function (): void {
    $kabupaten = H::kabupaten();
    submittedForNotification();

    $this->actingAs($kabupaten)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('unread_notifications', 1));

    $this->actingAs(H::kecamatan())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('unread_notifications', 0));
});

it('menolak tamu membuka daftar notifikasi', function (): void {
    $this->get('/notifikasi')->assertRedirect('/login');
});

it('menyimpan report_id sehingga notifikasi dapat ditaut ke laporannya', function (): void {
    $kabupaten = H::kabupaten();
    [, $report] = submittedForNotification();

    /** @var ReportVersion $version */
    $version = ReportVersion::query()->whereKey($report->current_version_id)->sole();

    $notification = notificationsFor($kabupaten)->sole();

    expect($notification->report_id)->toBe($report->id)
        ->and($notification->event_id)->toContain((string) $version->id);
});
