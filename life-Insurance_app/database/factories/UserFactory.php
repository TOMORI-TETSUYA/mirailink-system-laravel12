<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'login_id' => 'user_'.Str::lower(Str::random(8)),
            'display_name' => $this->faker->name(),
            'password' => 'TestPassword-12345',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function auditor(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_AUDITOR]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
