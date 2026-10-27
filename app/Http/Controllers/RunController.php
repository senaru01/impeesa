<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunRequest;
use App\Models\Run;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RunController extends Controller
{
    public function store(RunRequest $request): RedirectResponse
    {
        $run = $request->user()->runs()->create($request->runData());

        return to_route('health.index')->with('status', "Lari {$run->distance_km} km berhasil dicatat (pace {$run->formattedPace()}).");
    }

    public function destroy(Run $run): RedirectResponse
    {
        Gate::authorize('delete', $run);

        $run->delete();

        return to_route('health.index')->with('status', 'Catatan lari dihapus.');
    }
}
