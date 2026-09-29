<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'disk' => 'local',
            'path' => 'attachments/2026/01/'.Str::uuid()->toString().'.pdf',
            'original_name' => 'lampiran.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', Str::random()),
            'uploaded_by' => User::factory()->kecamatan(),
        ];
    }
}
