<?php

namespace App\Enums;

enum StoreItemCategory: string
{
    case Keycap = 'keycap';
    case BaseCap = 'base_cap';
    case Switch = 'switch';
    case Accessory = 'accessory';

    public function label(): string
    {
        return match ($this) {
            self::Keycap => 'Keycap',
            self::BaseCap => 'Base cap',
            self::Switch => 'Switch',
            self::Accessory => 'Aksesoris',
        };
    }
}
