<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Super admin menyetujui atau menolak foto kiriman member untuk Galeri Bersama.
 */
class SharedPhotoApprovalController extends Controller
{
    public function store(Photo $photo): RedirectResponse
    {
        abort_unless($photo->isPending(), 404);

        $photo->forceFill(['approved_at' => now()])->save();

        return back()->with('status', 'Foto disetujui dan tampil di Galeri Bersama.');
    }

    /**
     * Foto yang hanya dikirim ke Galeri Bersama dihapus; foto dari galeri pribadi
     * cukup batal dibagikan dan tetap tersimpan di galeri pemiliknya.
     */
    public function destroy(Photo $photo): RedirectResponse
    {
        abort_unless($photo->isPending(), 404);

        if (! $photo->isSharedOnly()) {
            $photo->forceFill(['shared_at' => null, 'approved_at' => null])->save();

            return back()->with('status', 'Foto ditolak dan tetap ada di galeri pribadi pemiliknya.');
        }

        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Foto ditolak dan dihapus.');
    }
}
