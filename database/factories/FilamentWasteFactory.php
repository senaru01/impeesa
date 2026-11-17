<?php

namespace Database\Factories;

use App\Enums\FilamentWasteReason;
use App\Models\FilamentLot;
use App\Models\FilamentWaste;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FilamentWaste>
 */
class FilamentWasteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filament_lot_id' => FilamentLot::factory(),
            'user_id' => null,
            'reason' => FilamentWasteReason::Discarded,
            'grams' => 50,
            'value' => 10000,
            'notes' => null,
        ];
    }
}
