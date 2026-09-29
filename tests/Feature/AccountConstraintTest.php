<?php

use App\Enums\UserRole;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\QueryException;

it('menolak akun kecamatan tanpa kecamatan pada level database', function (): void {
    expect(fn () => User::factory()->create([
        'role' => UserRole::AdminKecamatan,
        'district_id' => null,
    ]))->toThrow(QueryException::class);
});

it('menolak akun kabupaten yang terikat kecamatan pada level database', function (): void {
    $district = District::factory()->create();

    expect(fn () => User::factory()->create([
        'role' => UserRole::AdminKabupaten,
        'district_id' => $district->id,
    ]))->toThrow(QueryException::class);
});

it('menolak role di luar dua role yang ditetapkan', function (): void {
    expect(fn () => User::query()->insert([
        'name' => 'Peran Asing',
        'email' => 'asing@contoh.test',
        'password' => 'hash',
        'role' => 'super_admin',
        'district_id' => null,
        'is_active' => true,
        'must_change_password' => false,
    ]))->toThrow(QueryException::class);
});

it('menolak kecamatan yang masih dirujuk akun untuk dihapus', function (): void {
    $district = District::factory()->create();
    User::factory()->kecamatan($district)->create();

    expect(fn () => $district->delete())->toThrow(QueryException::class);
});
