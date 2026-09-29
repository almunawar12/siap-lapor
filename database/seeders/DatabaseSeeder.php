<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sengaja kosong. Tidak ada kredensial bawaan: akun Admin Kabupaten dibuat
     * lewat `php artisan siaplapor:create-admin-kabupaten`, akun Admin Kecamatan
     * dibuat dari halaman /admin/users. Daftar kecamatan resmi belum
     * dikonfirmasi sehingga tidak diseed.
     */
    public function run(): void
    {
        $this->command->info('Tidak ada data seed bawaan. Jalankan siaplapor:create-admin-kabupaten untuk akun pertama.');
    }
}
