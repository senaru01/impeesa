<?php

namespace App\Http\Controllers;

use App\Models\Climb;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Memilih foto utama pendakian dari foto-foto pendakian tersebut.
 */
class ClimbCoverController extends Controller
{
    public function update(Climb $climb, Photo $photo): RedirectResponse
    {
        Gate::authorize('update', $climb);
        abort_unless($photo->climb_id === $climb->id, 404);

        $climb->coverPhoto()->associate($photo)->save();

        return back()->with('status', 'Foto utama pendakian diganti.');
    }
}
