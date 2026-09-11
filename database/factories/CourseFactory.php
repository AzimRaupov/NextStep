<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Support\LevelResolver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
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
            'topic' => fake()->words(3, true),
            'declared_level' => LevelResolver::DECLARED_BASICS,
            'title' => fake()->sentence(3),
            'summary' => fake()->paragraph(),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'status' => 'generating',
        ];
    }
}
