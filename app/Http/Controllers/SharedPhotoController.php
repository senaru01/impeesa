<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoUploadRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Galeri Bersama: semua member bisa mengirim foto; kiriman member tampil setelah disetujui super admin.
 */
class SharedPhotoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('member.photos.shared', [
            'photos' => Photo::shared()->approved()->with(['user', 'uploader'])->latest('approved_at')->paginate(48),
            'pending' => Photo::shared()
                ->pending()
                ->with(['user', 'uploader'])
                ->unless($user->isSuperAdmin(), fn ($query) => $query->where(fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->orWhere(fn ($query) => $query->whereNull('user_id')->where('uploaded_by', $user->id))))
                ->oldest()
                ->get(),
        ]);
    }

    public function store(PhotoUploadRequest $request): RedirectResponse
    {
        $count = $request->storePhotos(null);

        return back()->with('status', $request->user()->isSuperAdmin()
            ? "{$count} foto ditambahkan ke Galeri Bersama."
            : "{$count} foto terkirim dan menunggu persetujuan super admin.");
    }
}
