<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilamentLotRequest;
use App\Models\FilamentLot;
use App\Models\StoreItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FilamentLotController extends Controller
{
    public function index(): View
    {
        $lots = FilamentLot::query()->withCount(['movements', 'letterMovements'])->orderByDesc('remaining_grams')->latest('purchased_on')->get();

        return view('admin.store.filament.index', [
            'lots' => $lots,
            'newLot' => new FilamentLot(['material' => 'PLA', 'weight_grams' => 1000, 'purchased_on' => today()]),
            'materials' => FilamentLot::MATERIALS,
            'colors' => $this->knownColors(),
            'totals' => [
                'remaining' => $lots->sum('remaining_grams'),
                'value' => (int) round($lots->sum(fn (FilamentLot $lot) => max(0, $lot->remaining_grams) * $lot->pricePerGram())),
                'spent' => (int) $lots->sum('price'),
            ],
        ]);
    }

    /**
     * Satu baris per roll supaya tiap roll punya harga per gramnya sendiri.
     */
    public function store(FilamentLotRequest $request): RedirectResponse
    {
        $quantity = $request->integer('quantity');
        $data = $request->lotData();

        DB::transaction(function () use ($quantity, $data) {
            foreach (range(1, $quantity) as $ignored) {
                FilamentLot::create([...$data, 'remaining_grams' => $data['weight_grams']]);
            }
        });

        return to_route('admin.store.filament.index')->with('status', "{$quantity} roll filament {$data['material']} {$data['color']} dicatat sebagai pembelian.");
    }

    public function edit(FilamentLot $filament): View
    {
        return view('admin.store.filament.edit', [
            'lot' => $filament,
            'materials' => FilamentLot::MATERIALS,
            'colors' => $this->knownColors(),
        ]);
    }

    public function update(FilamentLotRequest $request, FilamentLot $filament): RedirectResponse
    {
        $filament->update($request->lotData());

        return to_route('admin.store.filament.index')->with('status', 'Data filament disimpan.');
    }

    public function destroy(FilamentLot $filament): RedirectResponse
    {
        if ($filament->movements()->exists() || $filament->letterMovements()->exists()) {
            return back()->with('status', 'Filament ini sudah dipakai produksi, jadi tidak bisa dihapus. Hapus dulu item hasil cetakannya bila ini data uji coba.');
        }

        $filament->delete();

        return to_route('admin.store.filament.index')->with('status', 'Filament dihapus.');
    }

    /**
     * @return list<string>
     */
    private function knownColors(): array
    {
        return FilamentLot::query()->distinct()->pluck('color')
            ->merge(StoreItem::query()->whereNotNull('color')->distinct()->pluck('color'))
            ->unique()->sort()->values()->all();
    }
}
