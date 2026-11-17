<?php

namespace App\Enums;

enum FilamentWasteReason: string
{
    case Discarded = 'discarded';
    case FailedPrint = 'failed_print';

    public function label(): string
    {
        return match ($this) {
            self::Discarded => 'Sisa dibuang / rusak',
            self::FailedPrint => 'NG / gagal cetak',
        };
    }
}
