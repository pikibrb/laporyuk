<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HotlineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => fake()->numerify('1##'),
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
