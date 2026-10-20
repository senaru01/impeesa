<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu jawaban',
            self::Accepted => 'Ikut',
            self::Declined => 'Tidak ikut',
        };
    }
}
