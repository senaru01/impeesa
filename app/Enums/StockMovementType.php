<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Production = 'production';
    case Sale = 'sale';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Production => 'Produksi / beli',
            self::Sale => 'Terjual',
            self::Adjustment => 'Koreksi stok',
        };
    }
}
