<?php

namespace Database\Factories;

use App\Models\ReportingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportingPeriod>
 */
class ReportingPeriodFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'Periode '.fake()->unique()->numberBetween(1, 9999),
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-03-31',
            'submission_deadline' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withDeadline(string $date): static
    {
        return $this->state(fn (): array => ['submission_deadline' => $date]);
    }
}
