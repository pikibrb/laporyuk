<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(array_keys(Feedback::TYPE_LABELS)),
            'name' => fake()->name(),
            'contact' => fake()->optional()->safeEmail(),
            'message' => fake()->paragraph(),
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'name' => null,
            'contact' => null,
        ]);
    }
}
