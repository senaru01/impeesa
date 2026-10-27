<?php

namespace App\Policies;

use App\Models\Photo;
use App\Models\User;

/**
 * Galeri pribadi hanya untuk pemilik foto dan super admin. Foto yang dibagikan ke
 * Galeri Bersama terlihat oleh semua member setelah disetujui; sebelum itu hanya
 * pemilik/pengirim dan super admin yang bisa melihatnya.
 */
class PhotoPolicy
{
    public function view(User $user, Photo $photo): bool
    {
        if ($user->isSuperAdmin() || $this->ownsOrSent($user, $photo)) {
            return true;
        }

        return $photo->isShared() && ! $photo->isPending();
    }

    public function delete(User $user, Photo $photo): bool
    {
        return $user->isSuperAdmin() || $this->ownsOrSent($user, $photo);
    }

    /**
     * Mengubah keterangan foto Galeri Bersama; khusus super admin.
     */
    public function rename(User $user, Photo $photo): bool
    {
        return $user->isSuperAdmin() && $photo->isShared();
    }

    /**
     * Mengatur lokasi foto; foto pendakian selalu mengikuti gunung pendakiannya.
     */
    public function locate(User $user, Photo $photo): bool
    {
        return $photo->climb_id === null && $this->delete($user, $photo);
    }

    /**
     * Membagikan foto pribadi ke Galeri Bersama, atau menariknya kembali.
     */
    public function share(User $user, Photo $photo): bool
    {
        return ! $photo->isSharedOnly() && $photo->user_id === $user->id;
    }

    /**
     * Menurunkan foto pribadi dari Galeri Bersama tanpa menghapusnya dari galeri pemilik.
     */
    public function unshare(User $user, Photo $photo): bool
    {
        return ! $photo->isSharedOnly() && ($photo->user_id === $user->id || $user->isSuperAdmin());
    }

    /**
     * Pemilik galeri pribadi, atau pengirim foto yang langsung masuk Galeri Bersama.
     */
    private function ownsOrSent(User $user, Photo $photo): bool
    {
        return $photo->isSharedOnly() ? $photo->uploaded_by === $user->id : $photo->user_id === $user->id;
    }
}
