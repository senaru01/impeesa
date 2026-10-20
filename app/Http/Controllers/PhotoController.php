<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoUploadRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.photos.index', [
            'photos' => $request->user()->photos()->with('uploader')->latest()->get(),
        ]);
    }

    public function store(PhotoUploadRequest $request): RedirectResponse
    {
        $count = $request->storePhotos($request->user());

        return back()->with('status', "{$count} foto berhasil diunggah.");
    }

    /**
     * Kirim berkas foto dari disk privat setelah memastikan hak akses.
     */
    public function show(Photo $photo): StreamedResponse
    {
        Gate::authorize('view', $photo);

        return Storage::disk('local')->response($photo->path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        Gate::authorize('delete', $photo);

        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Foto dihapus.');
    }
}
