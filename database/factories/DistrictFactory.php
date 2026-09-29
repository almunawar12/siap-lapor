<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('##.##.##'),
            'name' => 'Kecamatan '.fake()->unique()->city(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
