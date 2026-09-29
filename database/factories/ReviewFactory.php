<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\ReportVersion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'report_version_id' => ReportVersion::factory(),
            'reviewer_id' => User::factory()->kabupaten(),
            'status' => ReviewStatus::Active->value,
            'general_note' => null,
            'started_at' => now(),
            'decided_at' => null,
        ];
    }
}
