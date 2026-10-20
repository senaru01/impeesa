<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'superadmin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Member => 'Member',
        };
    }
}
