<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeightLog>
 */
class WeightLogFactory extends Factory
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
            'recorded_on' => fake()->dateTimeBetween('-6 months', 'now'),
            'weight_kg' => fake()->randomFloat(1, 50, 90),
            'height_cm' => fake()->numberBetween(155, 185),
            'notes' => null,
        ];
    }
}
