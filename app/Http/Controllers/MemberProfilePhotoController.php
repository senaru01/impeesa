<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto profil hanya terbuka untuk tamu selama profil anggota itu tampil di web utama.
 */
class MemberProfilePhotoController extends Controller
{
    public function __invoke(User $user): StreamedResponse
    {
        abort_unless($user->is_active && $user->isProfileFeatured(), 404);
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response($user->avatar_path, headers: ['Cache-Control' => 'public, max-age=86400']);
    }
}
