<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::AdminKabupaten,
            'district_id' => null,
            'is_active' => true,
            'must_change_password' => false,
        ];
    }

    public function kabupaten(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::AdminKabupaten,
            'district_id' => null,
        ]);
    }

    public function kecamatan(?District $district = null): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::AdminKecamatan,
            'district_id' => $district instanceof District
                ? $district->id
                : District::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }
}
