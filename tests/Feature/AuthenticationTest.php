<?php

use App\Models\District;
use App\Models\User;

it('menampilkan halaman login', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

it('mengizinkan login akun aktif', function (): void {
    $user = User::factory()->kabupaten()->create(['password' => 'kata-sandi-rahasia']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'kata-sandi-rahasia',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('menolak kata sandi yang salah', function (): void {
    $user = User::factory()->kabupaten()->create(['password' => 'kata-sandi-rahasia']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'salah',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('mencocokkan email tanpa memperhatikan huruf besar kecil', function (): void {
    $user = User::factory()->kabupaten()->create([
        'email' => 'Admin.Kabupaten@Contoh.test',
        'password' => 'kata-sandi-rahasia',
    ]);

    expect($user->fresh()->email)->toBe('admin.kabupaten@contoh.test');

    $this->post('/login', [
        'email' => 'ADMIN.KABUPATEN@CONTOH.TEST',
        'password' => 'kata-sandi-rahasia',
    ])->assertRedirect('/dashboard');
});

it('menolak login akun nonaktif', function (): void {
    $user = User::factory()->kabupaten()->inactive()->create([
        'password' => 'kata-sandi-rahasia',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'kata-sandi-rahasia',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('mengakhiri sesi berjalan ketika akun dinonaktifkan', function (): void {
    $user = User::factory()->kecamatan(District::factory()->create())->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get('/dashboard')
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('mengeluarkan pengguna melalui logout', function (): void {
    $user = User::factory()->kabupaten()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});

it('mengarahkan tamu ke halaman login', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('tidak menyediakan route registrasi publik', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Penyusup',
        'email' => 'penyusup@contoh.test',
        'password' => 'kata-sandi-rahasia',
    ])->assertNotFound();

    expect(User::query()->count())->toBe(0);
});
