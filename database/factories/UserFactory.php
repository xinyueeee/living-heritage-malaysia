<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => (string) Str::uuid(),
            'user_name' => fake()->name(),
            'user_email' => fake()->unique()->safeEmail(),
            'profile_photo' => null,
            'bio' => null,
        ];
    }
}
