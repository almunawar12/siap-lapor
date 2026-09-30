<?php

use App\Enums\ReportStatus;
use App\Models\District;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DemoSeeder;

it('mengisi data demo Kabupaten Garut lewat alur nyata dan aman dijalankan ulang', function (): void {
    config(['siaplapor.demo_password' => 'demo-rahasia-123']);

    $this->seed(DemoSeeder::class);

    expect(District::query()->where('code', 'like', '32.05.%')->count())->toBe(42)
        ->and(District::query()->where('code', '32.05.01')->value('name'))->toBe('Kecamatan Garut Kota')
        ->and(User::query()->count())->toBe(44);

    $statuses = Report::query()->pluck('status')->map(fn (ReportStatus $s): string => $s->value)->countBy()->all();

    expect($statuses)->toEqual([
        'approved' => 3,
        'revision_required' => 2,
        'under_review' => 1,
        'submitted' => 1,
        'draft' => 1,
    ]);

    // Tiap laporan hanya milik kecamatan pembuatnya.
    Report::query()->with('creator')->each(
        fn (Report $r) => expect($r->creator->district_id)->toBe($r->district_id),
    );

    $this->post('/login', ['email' => 'garut-kota@'.DemoSeeder::EMAIL_DOMAIN, 'password' => 'demo-rahasia-123'])
        ->assertRedirect();
    $this->assertAuthenticated();

    $this->seed(DemoSeeder::class);
    expect(Report::query()->count())->toBe(8);
});

it('menolak berjalan tanpa DEMO_PASSWORD', function (): void {
    config(['siaplapor.demo_password' => null]);

    expect(fn () => $this->seed(DemoSeeder::class))->toThrow(RuntimeException::class);
    expect(User::query()->count())->toBe(0);
});
