<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id'   => Role::where('name', 'penyewa')->value('id'), // default penyewa (role_id=3)
            'username'  => fake()->unique()->userName(),
            'password'  => static::$password ??= Hash::make('password123'),
            'is_active' => 1,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role_id' => Role::where('name', 'administrator')->value('id')]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role_id' => Role::where('name', 'manager')->value('id')]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => 0]);
    }
}