<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Text>
 */
class TextFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'author_id' => fake()->boolean(70) ? Author::factory() : null,
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'year' => fake()->year(),
            'language' => fake()->randomElement(['English', 'Spanish', 'Italian']),
            'type' => fake()->randomElement(['poem', 'novel', 'short story', 'essay']),
        ];
    }
}
