<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FilamentWasteReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilamentWasteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class FilamentWasteController extends Controller
{
    /**
     * Kurangi sisa roll karena dibuang/rusak atau gagal cetak. Tidak ada uang kembali dan
     * tidak ada stok bertambah; nilainya dicatat sebagai kerugian filament.
     */
    public function __invoke(FilamentWasteRequest $request): RedirectResponse
    {
        $lot = $request->lot();
        $grams = $request->wastedGrams();
        $reason = FilamentWasteReason::from($request->input('reason'));

        DB::transaction(function () use ($request, $lot, $grams, $reason) {
            $lot->wastes()->create([
                'user_id' => $request->user()->id,
                'reason' => $reason,
                'grams' => $grams,
                'value' => (int) round($grams * $lot->pricePerGram()),
                'notes' => $request->input('notes'),
            ]);

            $lot->decrement('remaining_grams', $grams);
        });

        $gram = number_format($grams, $grams == floor($grams) ? 0 : 1, ',', '.');

        return to_route('admin.store.filament.index')
            ->with('status', "{$gram} g {$lot->groupLabel()} dicatat sebagai {$reason->label()}. Sisa roll sekarang ".number_format($lot->fresh()->remaining_grams, 0, ',', '.').' g.');
    }
}
