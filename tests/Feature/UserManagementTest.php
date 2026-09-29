<?php

use App\Enums\UserRole;
use App\Models\District;
use App\Models\User;

function kabupaten(): User
{
    return User::factory()->kabupaten()->create();
}

function kecamatan(?District $district = null): User
{
    return User::factory()->kecamatan($district ?? District::factory()->create())->create();
}

it('mengizinkan admin kabupaten membuka daftar akun', function (): void {
    $this->actingAs(kabupaten())
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/users/index'));
});

it('menolak admin kecamatan membuka pengelolaan akun', function (): void {
    $user = kecamatan();

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
    $this->actingAs($user)->get('/admin/districts')->assertForbidden();
});

it('menolak admin kecamatan membuat akun baru', function (): void {
    $district = District::factory()->create();

    $this->actingAs(kecamatan($district))
        ->post('/admin/users', [
            'name' => 'Akun Baru',
            'email' => 'baru@contoh.test',
            'district_id' => $district->id,
            'password' => 'kata-sandi-rahasia',
            'password_confirmation' => 'kata-sandi-rahasia',
        ])
        ->assertForbidden();

    expect(User::query()->where('email', 'baru@contoh.test')->exists())->toBeFalse();
});

it('membuat akun kecamatan dengan role dan status yang ditetapkan server', function (): void {
    $district = District::factory()->create();

    $this->actingAs(kabupaten())
        ->post('/admin/users', [
            'name' => 'Admin Kecamatan Contoh',
            'email' => 'Kecamatan@Contoh.test',
            'district_id' => $district->id,
            'password' => 'kata-sandi-rahasia',
            'password_confirmation' => 'kata-sandi-rahasia',
        ])
        ->assertRedirect('/admin/users');

    $created = User::query()->where('email', 'kecamatan@contoh.test')->sole();

    expect($created->role)->toBe(UserRole::AdminKecamatan)
        ->and($created->district_id)->toBe($district->id)
        ->and($created->is_active)->toBeTrue()
        ->and($created->must_change_password)->toBeTrue();
});

it('mengabaikan role yang dikirim dari payload', function (): void {
    $district = District::factory()->create();

    $this->actingAs(kabupaten())->post('/admin/users', [
        'name' => 'Penyusup',
        'email' => 'penyusup@contoh.test',
        'district_id' => $district->id,
        'password' => 'kata-sandi-rahasia',
        'password_confirmation' => 'kata-sandi-rahasia',
        'role' => UserRole::AdminKabupaten->value,
        'is_active' => false,
        'must_change_password' => false,
    ])->assertRedirect('/admin/users');

    $created = User::query()->where('email', 'penyusup@contoh.test')->sole();

    expect($created->role)->toBe(UserRole::AdminKecamatan)
        ->and($created->is_active)->toBeTrue();
});

it('menolak kecamatan yang tidak aktif saat membuat akun', function (): void {
    $district = District::factory()->inactive()->create();

    $this->actingAs(kabupaten())
        ->post('/admin/users', [
            'name' => 'Akun Baru',
            'email' => 'baru@contoh.test',
            'district_id' => $district->id,
            'password' => 'kata-sandi-rahasia',
            'password_confirmation' => 'kata-sandi-rahasia',
        ])
        ->assertSessionHasErrors('district_id');
});

it('tidak memindahkan kecamatan akun melalui payload update', function (): void {
    $asal = District::factory()->create();
    $tujuan = District::factory()->create();
    $target = kecamatan($asal);

    $this->actingAs(kabupaten())
        ->patch("/admin/users/{$target->id}", [
            'name' => 'Nama Baru',
            'email' => $target->email,
            'district_id' => $tujuan->id,
            'role' => UserRole::AdminKabupaten->value,
        ])
        ->assertRedirect('/admin/users');

    $target->refresh();

    expect($target->name)->toBe('Nama Baru')
        ->and($target->district_id)->toBe($asal->id)
        ->and($target->role)->toBe(UserRole::AdminKecamatan);
});

it('menolak admin kabupaten mengubah akun kabupaten lain', function (): void {
    $lain = User::factory()->kabupaten()->create();

    $this->actingAs(kabupaten())
        ->patch("/admin/users/{$lain->id}", [
            'name' => 'Diubah',
            'email' => $lain->email,
        ])
        ->assertForbidden();
});

it('menonaktifkan dan mengaktifkan kembali akun kecamatan', function (): void {
    $target = kecamatan();
    $admin = kabupaten();

    $this->actingAs($admin)->patch("/admin/users/{$target->id}/status");
    expect($target->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch("/admin/users/{$target->id}/status");
    expect($target->fresh()->is_active)->toBeTrue();
});

it('menolak admin kecamatan menonaktifkan akun lain', function (): void {
    $target = kecamatan();

    $this->actingAs(kecamatan())
        ->patch("/admin/users/{$target->id}/status")
        ->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
});

it('hanya menampilkan akun kecamatan pada daftar', function (): void {
    kecamatan();
    $admin = kabupaten();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertInertia(fn ($page) => $page->where('users.total', 1));
});
