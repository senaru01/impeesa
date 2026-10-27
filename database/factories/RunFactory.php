<?php

namespace Database\Factories;

use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Run>
 */
class RunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $distance = fake()->randomFloat(2, 2, 15);

        return [
            'user_id' => User::factory(),
            'run_on' => fake()->dateTimeBetween('-2 months', 'now'),
            'distance_km' => $distance,
            'duration_seconds' => (int) round($distance * fake()->numberBetween(300, 480)),
            'notes' => null,
        ];
    }
}
