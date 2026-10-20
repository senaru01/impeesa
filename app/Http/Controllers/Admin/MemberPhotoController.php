<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhotoUploadRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Super admin mengunggah foto langsung ke galeri pribadi seorang member.
 */
class MemberPhotoController extends Controller
{
    public function store(PhotoUploadRequest $request, User $member): RedirectResponse
    {
        $count = $request->storePhotos($member);

        return back()->with('status', "{$count} foto ditambahkan ke galeri pribadi {$member->name}.");
    }
}
