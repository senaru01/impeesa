<?php

namespace App\Models;

use Database\Factories\FilamentLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['material', 'color', 'brand', 'weight_grams', 'remaining_grams', 'price', 'purchased_on', 'notes'])]
class FilamentLot extends Model
{
    /** @use HasFactory<FilamentLotFactory> */
    use HasFactory;

    /**
     * Jenis filament yang umum dipakai.
     */
    public const MATERIALS = ['PLA', 'PLA+', 'PETG', 'ABS', 'TPU', 'Silk PLA'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight_grams' => 'integer',
            'remaining_grams' => 'float',
            'price' => 'integer',
            'purchased_on' => 'date',
        ];
    }

    /**
     * Harga filament per gram sesuai harga lot ini.
     */
    public function pricePerGram(): float
    {
        return $this->weight_grams > 0 ? $this->price / $this->weight_grams : 0.0;
    }

    public function label(): string
    {
        $remaining = number_format($this->remaining_grams, 0, ',', '.');

        return "{$this->material} {$this->color}".($this->brand ? " {$this->brand}" : '')." · sisa {$remaining} g";
    }

    public function remainingPercent(): int
    {
        return $this->weight_grams > 0 ? (int) round(max(0, $this->remaining_grams) / $this->weight_grams * 100) : 0;
    }

    /**
     * @param  Builder<FilamentLot>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('remaining_grams', '>', 0);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function letterMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'letter_filament_lot_id');
    }

    /**
     * Pesan kekurangan bila sisa roll tidak cukup untuk kebutuhan gram.
     *
     * @param  array<int, float>  $demand  id roll => gram
     * @return list<string>
     */
    public static function shortagesFor(array $demand): array
    {
        $lots = self::query()->findMany(array_keys($demand))->keyBy('id');
        $messages = [];

        foreach ($demand as $lotId => $grams) {
            $lot = $lots->get($lotId);

            if ($lot && $lot->remaining_grams < $grams) {
                $needed = number_format($grams, 1, ',', '.');
                $messages[] = "Filament {$lot->label()} tidak cukup: butuh {$needed} g.";
            }
        }

        return $messages;
    }
}
