<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Pemilik membagikan foto pribadinya ke Galeri Bersama, atau menariknya kembali.
 * Foto tetap tinggal di galeri pribadi pemiliknya.
 */
class SharedPhotoRequestController extends Controller
{
    public function store(Request $request, Photo $photo): RedirectResponse
    {
        Gate::authorize('share', $photo);

        if (! $photo->isShared()) {
            $photo->markShared($request->user());
            $photo->save();
        }

        return back()->with('status', $photo->isPending()
            ? 'Foto diajukan ke Galeri Bersama dan menunggu persetujuan super admin.'
            : 'Foto sekarang tampil di Galeri Bersama.');
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        Gate::authorize('unshare', $photo);

        $photo->forceFill(['shared_at' => null, 'approved_at' => null])->save();

        return back()->with('status', 'Foto ditarik dari Galeri Bersama dan kembali pribadi.');
    }
}
