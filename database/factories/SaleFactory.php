<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(10, 60) * 1000;

        return [
            'number' => 'IMP-'.now()->format('ymd').'-'.fake()->unique()->numerify('9##'),
            'user_id' => User::factory(),
            'customer_name' => null,
            'payment_method' => PaymentMethod::Cash,
            'total' => $total,
            'total_cost' => (int) round($total * 0.4),
            'paid_amount' => $total,
            'filament_grams' => fake()->randomFloat(1, 5, 40),
            'notes' => null,
        ];
    }
}
