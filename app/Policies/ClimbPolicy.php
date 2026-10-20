<?php

namespace App\Policies;

use App\Models\Climb;
use App\Models\User;

class ClimbPolicy
{
    public function update(User $user, Climb $climb): bool
    {
        return $climb->user_id === $user->id;
    }

    public function delete(User $user, Climb $climb): bool
    {
        return $climb->user_id === $user->id;
    }
}
