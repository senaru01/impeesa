<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\User;

/**
 * Cerita privat hanya untuk pemilik dan super admin. Cerita publik bisa dibaca semua
 * member yang login. Cerita yang tampil di beranda boleh dibaca siapa saja, termasuk tamu.
 */
class StoryPolicy
{
    public function view(?User $user, Story $story): bool
    {
        if ($story->isFeatured()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $story->is_public || $story->user_id === $user->id || $user->isSuperAdmin();
    }

    public function update(User $user, Story $story): bool
    {
        return $story->user_id === $user->id || $user->isSuperAdmin();
    }

    public function delete(User $user, Story $story): bool
    {
        return $story->user_id === $user->id || $user->isSuperAdmin();
    }

    /**
     * Hanya cerita yang dijadikan publik oleh pemiliknya yang bisa ditampilkan di beranda.
     */
    public function feature(User $user, Story $story): bool
    {
        return $user->isSuperAdmin() && $story->is_public;
    }
}
