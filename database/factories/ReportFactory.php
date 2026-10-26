<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'phone' => null,
            'photo_path' => null,
            'latitude' => fake()->latitude(3.52, 3.68),
            'longitude' => fake()->longitude(98.60, 98.74),
            'status' => Report::STATUS_PENDING,
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_PROCESSING,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_RESOLVED,
        ]);
    }
}
