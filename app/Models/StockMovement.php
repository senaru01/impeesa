<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_item_id', 'sale_id', 'user_id', 'filament_lot_id', 'type', 'quantity', 'unit_cost', 'filament_grams', 'letter_filament_lot_id', 'letter_filament_grams', 'notes'])]
class StockMovement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'unit_cost' => 'integer',
            'filament_grams' => 'float',
            'letter_filament_grams' => 'float',
        ];
    }

    /**
     * Uang keluar untuk produksi/pembelian stok ini.
     */
    public function totalCost(): int
    {
        return $this->type === StockMovementType::Production ? $this->quantity * $this->unit_cost : 0;
    }

    /**
     * Uang yang benar-benar keluar. Produksi dari lot filament tidak dihitung lagi karena
     * uangnya sudah keluar saat lot filament dibeli; yang dihitung hanya barang beli jadi.
     */
    public function cashOut(): int
    {
        return $this->filament_lot_id === null ? $this->totalCost() : 0;
    }

    /**
     * @return BelongsTo<FilamentLot, $this>
     */
    public function filamentLot(): BelongsTo
    {
        return $this->belongsTo(FilamentLot::class);
    }

    /**
     * Total gram dari roll utama dan roll huruf.
     */
    public function totalFilamentGrams(): float
    {
        return round($this->filament_grams + $this->letter_filament_grams, 2);
    }

    /**
     * @return BelongsTo<FilamentLot, $this>
     */
    public function letterFilamentLot(): BelongsTo
    {
        return $this->belongsTo(FilamentLot::class, 'letter_filament_lot_id');
    }

    /**
     * @return BelongsTo<StoreItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
