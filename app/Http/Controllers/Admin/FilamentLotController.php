<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FilamentWasteReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilamentLotRequest;
use App\Models\FilamentLot;
use App\Models\FilamentWaste;
use App\Models\StoreItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FilamentLotController extends Controller
{
    public function index(): View
    {
        $lots = FilamentLot::query()->withCount(['movements', 'letterMovements'])->fifo()->get();
        $wastes = FilamentWaste::query()->with('lot')->latest()->latest('id')->take(10)->get();

        return view('admin.store.filament.index', [
            'groups' => $this->groupsFrom($lots),
            'newLot' => new FilamentLot(['material' => 'PLA', 'weight_grams' => 1000, 'purchased_on' => today()]),
            'materials' => FilamentLot::MATERIALS,
            'colors' => $this->knownColors(),
            'wasteReasons' => FilamentWasteReason::cases(),
            'wastes' => $wastes,
            'totals' => [
                'remaining' => $lots->sum('remaining_grams'),
                'rolls' => $lots->where('remaining_grams', '>', 0)->count(),
                'value' => (int) round($lots->sum(fn (FilamentLot $lot) => max(0, $lot->remaining_grams) * $lot->pricePerGram())),
                'spent' => (int) $lots->sum('price'),
                'wasted' => (float) FilamentWaste::query()->sum('grams'),
                'wastedValue' => (int) FilamentWaste::query()->sum('value'),
            ],
        ]);
    }

    /**
     * Kelompokkan roll per jenis & warna (master filament). Roll yang masih ada isinya
     * diurutkan FIFO: yang pertama sedang dipakai, sisanya cadangan.
     *
     * @param  Collection<int, FilamentLot>  $lots
     * @return list<array{label: string, color: string, remaining: float, value: int, rolls: Collection<int, FilamentLot>, empty: Collection<int, FilamentLot>}>
     */
    private function groupsFrom(Collection $lots): array
    {
        return $lots->groupBy(fn (FilamentLot $lot) => $lot->groupKey())
            ->map(function ($groupLots) {
                $rolls = $groupLots->where('remaining_grams', '>', 0)->values();

                return [
                    'label' => $groupLots->first()->groupLabel(),
                    'color' => $groupLots->first()->color,
                    'remaining' => (float) $rolls->sum('remaining_grams'),
                    'value' => (int) round($rolls->sum(fn (FilamentLot $lot) => $lot->remaining_grams * $lot->pricePerGram())),
                    'rolls' => $rolls,
                    'empty' => $groupLots->where('remaining_grams', '<=', 0)->values(),
                ];
            })
            ->sortBy([fn ($a, $b) => $a['rolls']->isEmpty() <=> $b['rolls']->isEmpty(), fn ($a, $b) => $a['label'] <=> $b['label']])
            ->values()
            ->all();
    }

    /**
     * Satu baris per roll supaya tiap roll punya harga per gramnya sendiri. Roll dengan jenis &
     * warna sama otomatis masuk master yang sama dan dipakai FIFO.
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
