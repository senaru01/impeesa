<?php

namespace App\Enums;

enum ClimbStatus: string
{
    case Planned = 'planned';
    case Summit = 'summit';
    case NotSummit = 'not_summit';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Rencana',
            self::Summit => 'Sampai puncak',
            self::NotSummit => 'Tidak sampai puncak',
        };
    }
}
