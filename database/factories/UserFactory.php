<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'customer',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    public function demoAdmin(): static
    {
        return $this->state(fn () => ['role' => UserRole::DEMO_ADMIN]);
    }

    public function technician(): static
    {
        return $this->state(fn () => ['role' => UserRole::TECHNICIAN]);
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => UserRole::CUSTOMER]);
    }
}
