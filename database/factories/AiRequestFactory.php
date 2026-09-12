<?php

namespace Database\Factories;

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
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
            'payload' => [
                'model' => 'gpt-5-nano',
                'instructions' => fake()->sentence(),
                'input' => fake()->sentence(),
            ],
            'status' => 'pending',
        ];
    }
}
