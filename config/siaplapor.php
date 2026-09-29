<?php

/*
| Batas operasional yang PRD nyatakan dapat dikonfigurasi.
*/
return [
    'attachments' => [
        // Disk privat. `local` berakar di storage/app/private, di luar webroot.
        'disk' => env('ATTACHMENT_DISK', 'local'),

        'max_size_kb' => (int) env('ATTACHMENT_MAX_SIZE_KB', 10240),

        'max_files_per_version' => (int) env('ATTACHMENT_MAX_FILES', 10),

        // SVG/HTML/executable sengaja tidak diizinkan.
        'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],

        'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'pagination' => [
        'per_page' => 20,
    ],
];
