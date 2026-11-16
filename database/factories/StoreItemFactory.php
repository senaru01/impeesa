<?php

namespace Database\Factories;

use App\Enums\StoreItemCategory;
use App\Models\StoreItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreItem>
 */
class StoreItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => StoreItemCategory::Accessory,
            'name' => 'Ring '.fake()->unique()->word(),
            'code' => null,
            'size' => null,
            'color' => null,
            'letter_color' => null,
            'stock' => 20,
            'min_stock' => 3,
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'filament_grams' => 0,
            'is_active' => true,
        ];
    }

    public function keycap(string $code, string $color = 'Putih', ?string $letterColor = null): static
    {
        return $this->state(fn () => [
            'category' => StoreItemCategory::Keycap,
            'name' => "Keycap {$code}",
            'code' => $code,
            'color' => $color,
            'letter_color' => $letterColor,
            'purchase_price' => 800,
            'selling_price' => 3000,
            'filament_grams' => 2.5,
        ]);
    }

    public function baseCap(int $size, string $color = 'Hitam'): static
    {
        return $this->state(fn () => [
            'category' => StoreItemCategory::BaseCap,
            'name' => "Base cap {$size} huruf",
            'size' => $size,
            'color' => $color,
            'purchase_price' => 1000 + $size * 300,
            'selling_price' => 4000 + $size * 1000,
            'filament_grams' => 3 + $size * 1.5,
        ]);
    }

    public function switch(): static
    {
        return $this->state(fn () => [
            'category' => StoreItemCategory::Switch,
            'name' => 'Switch Outemu Blue',
            'purchase_price' => 1200,
            'selling_price' => 2500,
            'filament_grams' => 0,
        ]);
    }
}
