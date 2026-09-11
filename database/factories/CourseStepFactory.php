<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseStep>
 */
class CourseStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'order' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requires_test' => false,
            'estimated_days' => fake()->numberBetween(1, 3),
            'status' => 'locked',
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function requiresTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_test' => true,
        ]);
    }
}
