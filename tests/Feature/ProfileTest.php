<?php

use App\Models\District;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('memperbarui profil sendiri tanpa mengubah role', function (): void {
    $district = District::factory()->create();
    $user = User::factory()->kecamatan($district)->create();

    $this->actingAs($user)->patch('/profil', [
        'name' => 'Nama Diperbarui',
        'email' => 'baru@contoh.test',
        'role' => 'admin_kabupaten',
        'district_id' => null,
        'is_active' => false,
    ])->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Nama Diperbarui')
        ->and($user->email)->toBe('baru@contoh.test')
        ->and($user->isKecamatan())->toBeTrue()
        ->and($user->district_id)->toBe($district->id)
        ->and($user->is_active)->toBeTrue();
});

it('mengganti kata sandi dan melepas penanda kata sandi sementara', function (): void {
    $user = User::factory()->kabupaten()->create([
        'password' => 'kata-sandi-lama',
        'must_change_password' => true,
    ]);

    $this->actingAs($user)->put('/profil/kata-sandi', [
        'current_password' => 'kata-sandi-lama',
        'password' => 'kata-sandi-baru-9',
        'password_confirmation' => 'kata-sandi-baru-9',
    ])->assertRedirect();

    $user->refresh();

    expect(Hash::check('kata-sandi-baru-9', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();
});

it('menolak penggantian kata sandi dengan kata sandi lama yang salah', function (): void {
    $user = User::factory()->kabupaten()->create(['password' => 'kata-sandi-lama']);

    $this->actingAs($user)->put('/profil/kata-sandi', [
        'current_password' => 'salah',
        'password' => 'kata-sandi-baru-9',
        'password_confirmation' => 'kata-sandi-baru-9',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('kata-sandi-lama', $user->fresh()->password))->toBeTrue();
});
