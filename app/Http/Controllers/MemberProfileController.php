<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

/**
 * Halaman publik "Profil Singkat Anggota": hanya anggota yang dipilih super admin.
 */
class MemberProfileController extends Controller
{
    public function __invoke(): View
    {
        return view('profiles.index', [
            'members' => User::featuredProfile()->oldest('profile_featured_at')->get(),
        ]);
    }
}
