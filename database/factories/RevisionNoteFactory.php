<?php

namespace Database\Factories;

use App\Enums\NoteStatus;
use App\Models\Review;
use App\Models\RevisionNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisionNote>
 */
class RevisionNoteFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'field_key' => null,
            'attachment_id' => null,
            'body' => 'Mohon lengkapi bagian ini.',
            'status' => NoteStatus::Open->value,
        ];
    }
}
