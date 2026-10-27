<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeightLogRequest;
use App\Models\WeightLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeightLogController extends Controller
{
    public function store(WeightLogRequest $request): RedirectResponse
    {
        $request->user()->weightLogs()->create($request->validated());

        return to_route('health.index')->with('status', 'Berat badan berhasil dicatat.');
    }

    public function destroy(WeightLog $weightLog): RedirectResponse
    {
        Gate::authorize('delete', $weightLog);

        $weightLog->delete();

        return to_route('health.index')->with('status', 'Catatan berat badan dihapus.');
    }
}
