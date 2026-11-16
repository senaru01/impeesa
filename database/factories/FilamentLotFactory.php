<?php

namespace Database\Factories;

use App\Models\FilamentLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FilamentLot>
 */
class FilamentLotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material' => 'PLA',
            'color' => 'Putih',
            'brand' => null,
            'weight_grams' => 1000,
            'remaining_grams' => 1000,
            'price' => 200000,
            'purchased_on' => now(),
            'notes' => null,
        ];
    }
}
