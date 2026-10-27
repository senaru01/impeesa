<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeightLog;

class WeightLogPolicy
{
    public function delete(User $user, WeightLog $weightLog): bool
    {
        return $weightLog->user_id === $user->id;
    }
}
