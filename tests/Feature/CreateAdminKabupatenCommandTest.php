<?php

use App\Enums\UserRole;
use App\Models\User;

it('membuat akun admin kabupaten dengan kata sandi tersembunyi', function (): void {
    $this->artisan('siaplapor:create-admin-kabupaten', [
        '--name' => 'Admin Kabupaten',
        '--email' => 'Admin@Contoh.test',
    ])
        ->expectsQuestion('Kata sandi', 'KataSandiKuat#2026')
        ->expectsQuestion('Ulangi kata sandi', 'KataSandiKuat#2026')
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@contoh.test')->sole();

    expect($user->role)->toBe(UserRole::AdminKabupaten)
        ->and($user->district_id)->toBeNull()
        ->and($user->is_active)->toBeTrue();
});

it('menolak kata sandi yang tidak cocok', function (): void {
    $this->artisan('siaplapor:create-admin-kabupaten', [
        '--name' => 'Admin Kabupaten',
        '--email' => 'admin@contoh.test',
    ])
        ->expectsQuestion('Kata sandi', 'KataSandiKuat#2026')
        ->expectsQuestion('Ulangi kata sandi', 'BerbedaSekali#2026')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('menolak email yang sudah terpakai', function (): void {
    User::factory()->kabupaten()->create(['email' => 'admin@contoh.test']);

    $this->artisan('siaplapor:create-admin-kabupaten', [
        '--name' => 'Admin Lain',
        '--email' => 'admin@contoh.test',
    ])
        ->expectsQuestion('Kata sandi', 'KataSandiKuat#2026')
        ->expectsQuestion('Ulangi kata sandi', 'KataSandiKuat#2026')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});
