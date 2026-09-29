<?php

/*
| Identitas instansi. KPU/Bawaslu belum dikonfirmasi pemilik proyek, sehingga
| nilai ini sengaja dibuat sebagai konfigurasi, bukan hardcode.
|
| ponytail: config file, pindahkan ke tabel application_settings saat halaman
| pengaturan instansi (M4) dibangun agar dapat diubah tanpa deploy.
*/
return [
    'app_name' => env('APP_NAME', 'SIAP LAPOR'),
    'app_tagline' => env('APP_TAGLINE', 'Pelaporan tertata, pengawasan terlacak.'),
    'institution_name' => env('INSTITUTION_NAME', ''),
    'regency_name' => env('REGENCY_NAME', ''),
];
