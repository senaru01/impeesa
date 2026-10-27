<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoLocationRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Pemilik/pengirim foto atau super admin mengatur lokasi foto yang tidak terhubung ke pendakian.
 */
class PhotoLocationController extends Controller
{
    public function update(PhotoLocationRequest $request, Photo $photo): RedirectResponse
    {
        Gate::authorize('locate', $photo);

        $photo->update(['mountain_name' => $request->validated('mountain_name')]);

        return back()->with('status', $photo->mountain_name
            ? "Lokasi foto diatur ke {$photo->mountain_name}."
            : 'Lokasi foto dihapus.')->withFragment("foto-{$photo->id}");
    }
}
