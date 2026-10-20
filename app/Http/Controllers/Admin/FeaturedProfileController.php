<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Menampilkan atau menurunkan profil singkat anggota dari web utama.
 */
class FeaturedProfileController extends Controller
{
    public function store(User $member): RedirectResponse
    {
        if (! $member->isProfileFeatured()) {
            $member->forceFill(['profile_featured_at' => now()])->save();
        }

        return back()->with('status', "Profil {$member->name} sekarang tampil di Profil Singkat Anggota.");
    }

    public function destroy(User $member): RedirectResponse
    {
        $member->forceFill(['profile_featured_at' => null])->save();

        return back()->with('status', "Profil {$member->name} tidak lagi tampil di web utama.");
    }
}
