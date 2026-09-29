<?php

namespace Database\Factories;

use App\Models\ReportVersion;
use App\Models\RevisionNote;
use App\Models\RevisionResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisionResponse>
 */
class RevisionResponseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'revision_note_id' => RevisionNote::factory(),
            'report_version_id' => ReportVersion::factory(),
            'author_id' => User::factory()->kecamatan(),
            'body' => 'Sudah kami lengkapi.',
        ];
    }
}
