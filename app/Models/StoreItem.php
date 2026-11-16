<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Enums\StoreItemCategory;
use Database\Factories\StoreItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category', 'name', 'code', 'size', 'color', 'letter_color', 'stock', 'min_stock', 'purchase_price', 'fixed_cost', 'selling_price', 'filament_grams', 'is_active'])]
class StoreItem extends Model
{
    /** @use HasFactory<StoreItemFactory> */
    use HasFactory;

    /**
     * Jumlah huruf maksimal pada satu base cap gantungan kunci.
     */
    public const MAX_BASE_SIZE = 10;

    /**
     * Karakter keycap standar: huruf A-Z lalu angka 0-9.
     */
    public const KEYCAP_CODES = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Porsi filament huruf pada keycap dua warna (AMS); sisanya untuk badan cap.
     */
    public const LETTER_FILAMENT_SHARE = 0.05;

    /**
     * Pemisah warna cap dan warna huruf pada kunci varian keycap, mis. "Putih|Hitam".
     */
    public const VARIANT_SEPARATOR = '|';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => StoreItemCategory::class,
            'size' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'purchase_price' => 'integer',
            'fixed_cost' => 'integer',
            'selling_price' => 'integer',
            'filament_grams' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Nama lengkap item beserta warnanya, dipakai di keranjang dan struk.
     */
    public function label(): string
    {
        return $this->color ? "{$this->name} · {$this->variantLabel()}" : $this->name;
    }

    /**
     * Keycap dua warna: badan cap dari satu filament, huruf dari filament lain.
     */
    public function hasLetterColor(): bool
    {
        return $this->category === StoreItemCategory::Keycap && filled($this->letter_color);
    }

    /**
     * Kunci kombinasi warna keycap untuk pilihan di kasir, mis. "Putih|Hitam".
     */
    public function variantKey(): string
    {
        return self::makeVariantKey((string) $this->color, $this->hasLetterColor() ? $this->letter_color : null);
    }

    public function variantLabel(): string
    {
        return self::variantLabelFor($this->variantKey());
    }

    public static function makeVariantKey(string $color, ?string $letterColor): string
    {
        return filled($letterColor) ? $color.self::VARIANT_SEPARATOR.$letterColor : $color;
    }

    /**
     * @return array{0: string, 1: ?string} warna cap dan warna huruf
     */
    public static function parseVariantKey(string $key): array
    {
        [$color, $letterColor] = array_pad(explode(self::VARIANT_SEPARATOR, $key, 2), 2, null);

        return [trim((string) $color), filled($letterColor) ? trim($letterColor) : null];
    }

    public static function variantLabelFor(string $key): string
    {
        [$color, $letterColor] = self::parseVariantKey($key);

        return $letterColor ? "{$color}, huruf {$letterColor}" : $color;
    }

    /**
     * Nama bawaan item bila tidak diisi manual.
     */
    public static function defaultName(StoreItemCategory $category, ?string $code, ?int $size): ?string
    {
        return match ($category) {
            StoreItemCategory::Keycap => filled($code) ? "Keycap {$code}" : null,
            StoreItemCategory::BaseCap => $size !== null ? "Base cap {$size} huruf" : null,
            default => null,
        };
    }

    /**
     * Gram filament per pcs untuk badan dan huruf. Huruf hanya dipisah bila keycap dua warna
     * dan roll huruf dipilih; selain itu semua gram diambil dari roll utama.
     *
     * @return array{main: float, letter: float}
     */
    public function filamentSplit(bool $withLetterLot): array
    {
        $letter = $withLetterLot && $this->hasLetterColor() ? round($this->filament_grams * self::LETTER_FILAMENT_SHARE, 4) : 0.0;

        return ['main' => $this->filament_grams - $letter, 'letter' => $letter];
    }

    /**
     * Modal per pcs: biaya tetap (listrik + penyusutan) + gram badan × harga/gram roll utama
     * + gram huruf × harga/gram roll huruf.
     */
    public function unitCostFrom(FilamentLot $lot, ?FilamentLot $letterLot = null): int
    {
        $split = $this->filamentSplit($letterLot !== null);

        return $this->fixed_cost + (int) round($split['main'] * $lot->pricePerGram() + $split['letter'] * ($letterLot?->pricePerGram() ?? 0));
    }

    /**
     * Gram filament yang terpakai untuk mencetak sejumlah pcs.
     */
    public function filamentNeededFor(int $quantity): float
    {
        return round($quantity * $this->filament_grams, 2);
    }

    /**
     * Kebutuhan gram per roll untuk mencetak sejumlah pcs.
     *
     * @return array<int, float> id roll => gram
     */
    public function filamentDemand(int $quantity, ?FilamentLot $lot, ?FilamentLot $letterLot = null): array
    {
        if (! $lot) {
            return [];
        }

        $split = $this->filamentSplit($letterLot !== null);
        $demand = [$lot->id => round($split['main'] * $quantity, 2)];

        if ($letterLot && $split['letter'] > 0) {
            $demand[$letterLot->id] = round(($demand[$letterLot->id] ?? 0) + $split['letter'] * $quantity, 2);
        }

        return $demand;
    }

    /**
     * Tambah stok hasil produksi/pembelian. Bila dicetak dari roll filament, modal dihitung ulang
     * dari harga roll dan sisa gram roll dikurangi. Keycap dua warna memotong 5% gram dari roll
     * huruf. Panggil di dalam transaksi database.
     */
    public function produce(int $quantity, ?User $user, ?FilamentLot $lot = null, ?int $unitCost = null, ?string $notes = null, ?FilamentLot $letterLot = null): StockMovement
    {
        $letterLot = $lot && $this->hasLetterColor() ? $letterLot : null;
        $split = $this->filamentSplit($letterLot !== null);
        $mainGrams = $lot ? round($split['main'] * $quantity, 2) : 0.0;
        $letterGrams = $letterLot ? round($split['letter'] * $quantity, 2) : 0.0;

        if ($lot) {
            $unitCost = $this->unitCostFrom($lot, $letterLot);
            $lot->decrement('remaining_grams', $mainGrams);
            $letterLot?->decrement('remaining_grams', $letterGrams);
            $this->purchase_price = $unitCost;
        }

        $unitCost ??= $this->purchase_price;
        $this->stock += $quantity;
        $this->save();

        return $this->movements()->create([
            'user_id' => $user?->id,
            'filament_lot_id' => $lot?->id,
            'type' => StockMovementType::Production,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'filament_grams' => $mainGrams,
            'letter_filament_lot_id' => $letterLot?->id,
            'letter_filament_grams' => $letterGrams,
            'notes' => $notes,
        ]);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function margin(): int
    {
        return $this->selling_price - $this->purchase_price;
    }

    /**
     * @param  Builder<StoreItem>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<StoreItem>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Filter halaman Item & stok. Dipakai juga oleh aksi massal "semua hasil filter"
     * supaya yang dihapus/direset persis sama dengan yang terlihat.
     *
     * @param  Builder<StoreItem>  $query
     * @param  array{category?: ?StoreItemCategory, color?: string, search?: string, lowStock?: bool}  $filters
     */
    public function scopeMatchingFilters(Builder $query, array $filters): void
    {
        $category = $filters['category'] ?? null;
        $color = $filters['color'] ?? '';
        $search = $filters['search'] ?? '';

        $query
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($color !== '', fn ($query) => $query->where('color', $color))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', $search)))
            ->when($filters['lowStock'] ?? false, fn ($query) => $query->lowStock());
    }

    /**
     * Urutan tampil: kategori, warna, lalu ukuran/karakter.
     *
     * @param  Builder<StoreItem>  $query
     */
    public function scopeCatalogOrder(Builder $query): void
    {
        $query->orderBy('category')->orderBy('color')->orderBy('letter_color')->orderBy('size')->orderBy('code')->orderBy('name');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
