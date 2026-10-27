<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoCaptionRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Super admin merapikan keterangan foto Galeri Bersama satu per satu,
 * mis. setelah unggahan banyak foto yang keterangannya sama semua.
 */
class PhotoCaptionController extends Controller
{
    public function update(PhotoCaptionRequest $request, Photo $photo): RedirectResponse
    {
        Gate::authorize('rename', $photo);

        $photo->update(['caption' => $request->validated('caption')]);

        return back()->with('status', $photo->caption
            ? "Keterangan foto diubah menjadi \"{$photo->caption}\"."
            : 'Keterangan foto dihapus.')->withFragment("foto-{$photo->id}");
    }
}
