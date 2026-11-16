<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_item_id', 'group_index', 'group_label', 'description', 'quantity', 'unit_price', 'unit_cost', 'filament_grams'])]
class SaleLine extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_index' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'unit_cost' => 'integer',
            'filament_grams' => 'float',
        ];
    }

    public function subtotal(): int
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<StoreItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }
}
