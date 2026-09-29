<?php

/*
| Terjemahan untuk aturan validasi yang benar-benar dipakai aplikasi ini.
| Tambahkan entri baru saat aturan baru digunakan, bukan menyalin seluruh file
| bawaan Laravel yang sebagian besar tidak terpakai.
*/
return [
    'boolean' => 'Kolom :attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini tidak sesuai.',
    'date' => 'Kolom :attribute bukan tanggal yang valid.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => 'Kolom :attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',
    'password' => [
        'letters' => 'Kolom :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute yang diberikan pernah bocor pada kebocoran data. Pilih :attribute lain.',
    ],
    'attributes' => [],
];
