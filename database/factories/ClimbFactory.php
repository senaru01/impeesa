<?php

namespace Database\Factories;

use App\Enums\ClimbStatus;
use App\Models\Climb;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Climb>
 */
class ClimbFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedOn = fake()->dateTimeBetween('-2 years', '-1 week');

        return [
            'user_id' => User::factory(),
            'mountain_name' => fake()->randomElement(['Gunung Rinjani', 'Gunung Semeru', 'Gunung Merbabu', 'Gunung Prau']),
            'route' => fake()->randomElement(['Sembalun', 'Ranu Pani', 'Selo', 'Patak Banteng']),
            'elevation' => fake()->numberBetween(2500, 3800),
            'started_on' => $startedOn,
            'finished_on' => (clone $startedOn)->modify('+2 days'),
            'status' => ClimbStatus::Summit,
            'notes' => null,
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_on' => now()->addDays(10),
            'finished_on' => now()->addDays(12),
            'status' => ClimbStatus::Planned,
        ]);
    }
}
