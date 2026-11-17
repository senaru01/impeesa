<?php

namespace App\Models;

use Database\Factories\FilamentLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Nama master filament: semua roll dengan jenis & warna sama masuk satu kelompok.
     */
    public function groupLabel(): string
    {
        return "{$this->material} {$this->color}";
    }

    public function groupKey(): string
    {
        return mb_strtolower($this->material.'|'.$this->color);
    }

    /**
     * Label pilihan produksi: satu baris per warna, menunjuk roll yang sedang dipakai (FIFO).
     * Butuh atribut group_remaining & group_rolls dari activeRolls().
     */
    public function optionLabel(): string
    {
        $gram = fn (float $value): string => number_format($value, 0, ',', '.');
        $spare = (int) $this->getAttribute('group_rolls') - 1;
        $label = "{$this->groupLabel()} · roll dipakai".($this->brand ? " {$this->brand}" : '')." sisa {$gram($this->remaining_grams)} g";

        if ($spare > 0) {
            $label .= " (+{$spare} roll, total {$gram((float) $this->getAttribute('group_remaining'))} g)";
        }

        return $label.' · Rp'.number_format($this->pricePerGram(), 1, ',', '.').'/g';
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
     * Urutan pakai: roll terlama dulu (first in, first out).
     *
     * @param  Builder<FilamentLot>  $query
     */
    public function scopeFifo(Builder $query): void
    {
        $query->orderBy('purchased_on')->orderBy('id');
    }

    /**
     * Roll yang masih ada isinya dengan jenis & warna sama, urut FIFO.
     *
     * @return Collection<int, FilamentLot>
     */
    public function queue(): Collection
    {
        return self::query()->available()
            ->where('material', $this->material)
            ->where('color', $this->color)
            ->fifo()
            ->get();
    }

    /**
     * Satu roll per warna untuk pilihan produksi: roll terlama yang masih ada isinya,
     * beserta total sisa & jumlah roll di kelompoknya.
     *
     * @return Collection<int, FilamentLot>
     */
    public static function activeRolls(): Collection
    {
        $active = self::query()->available()->fifo()->get()
            ->groupBy(fn (FilamentLot $lot) => $lot->groupKey())
            ->map(function ($lots): FilamentLot {
                $lot = $lots->first();
                $lot->setAttribute('group_remaining', (float) $lots->sum('remaining_grams'));
                $lot->setAttribute('group_rolls', $lots->count());

                return $lot;
            })
            ->sortBy(fn (FilamentLot $lot) => $lot->groupLabel())
            ->values()
            ->all();

        return new Collection($active);
    }

    /**
     * Pakai filament dari kelompok roll ini secara FIFO: habiskan roll terlama dulu,
     * lalu lanjut ke roll berikutnya. Panggil di dalam transaksi database.
     *
     * @return array{lots: array<int, float>, cost: float} gram per roll & nilai filament terpakai
     */
    public function consume(float $grams): array
    {
        $left = round($grams, 2);
        $used = [];
        $cost = 0.0;
        $queue = $this->queue();

        foreach ($queue as $index => $lot) {
            if ($left <= 0) {
                break;
            }

            $take = $index === $queue->count() - 1 ? $left : min($left, (float) $lot->remaining_grams);
            $lot->decrement('remaining_grams', $take);
            $used[$lot->id] = $take;
            $cost += $take * $lot->pricePerGram();
            $left = round($left - $take, 2);
        }

        if ($left > 0) {
            $this->decrement('remaining_grams', $left);
            $used[$this->id] = ($used[$this->id] ?? 0) + $left;
            $cost += $left * $this->pricePerGram();
        }

        return ['lots' => $used, 'cost' => $cost];
    }

    /**
     * @return HasMany<FilamentWaste, $this>
     */
    public function wastes(): HasMany
    {
        return $this->hasMany(FilamentWaste::class);
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
     * Pesan kekurangan bila sisa semua roll sewarna tidak cukup untuk kebutuhan gram.
     *
     * @param  array<int, float>  $demand  id roll => gram
     * @return list<string>
     */
    public static function shortagesFor(array $demand): array
    {
        $groups = [];

        foreach (self::query()->findMany(array_keys($demand)) as $lot) {
            $groups[$lot->groupKey()] ??= ['lot' => $lot, 'grams' => 0.0];
            $groups[$lot->groupKey()]['grams'] += $demand[$lot->id];
        }

        $messages = [];

        foreach ($groups as ['lot' => $lot, 'grams' => $grams]) {
            $available = (float) $lot->queue()->sum('remaining_grams');

            if ($available < $grams) {
                $needed = number_format($grams, 1, ',', '.');
                $left = number_format($available, 0, ',', '.');
                $messages[] = "Filament {$lot->groupLabel()} tidak cukup: butuh {$needed} g, sisa semua roll {$left} g.";
            }
        }

        return $messages;
    }
}
