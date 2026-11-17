<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Enums\StoreItemCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Models\FilamentLot;
use App\Models\StockMovement;
use App\Models\StoreItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreItemController extends Controller
{
    public function index(Request $request): View
    {
        $filters = self::filtersFrom($request);

        $items = StoreItem::query()
            ->matchingFilters($filters)
            ->catalogOrder()
            ->paginate(60)
            ->withQueryString();

        $totals = StoreItem::query()
            ->selectRaw('COUNT(*) as item_count, COALESCE(SUM(stock), 0) as units, COALESCE(SUM(stock * purchase_price), 0) as stock_value, COALESCE(SUM(stock * selling_price), 0) as potential_revenue')
            ->first();

        return view('admin.store.items.index', [
            'items' => $items,
            'categories' => StoreItemCategory::cases(),
            'lots' => FilamentLot::activeRolls(),
            'colors' => StoreItem::query()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
            'filters' => $filters,
            'isFiltered' => $filters['category'] !== null || $filters['color'] !== '' || $filters['search'] !== '' || $filters['lowStock'],
            'totals' => $totals,
            'lowStockCount' => StoreItem::query()->active()->lowStock()->count(),
            'movements' => StockMovement::query()
                ->with(['item', 'sale'])
                ->where('type', '!=', StockMovementType::Sale)
                ->latest()
                ->latest('id')
                ->take(10)
                ->get(),
        ]);
    }

    /**
     * @return array{category: ?StoreItemCategory, color: string, search: string, lowStock: bool}
     */
    public static function filtersFrom(Request $request): array
    {
        return [
            'category' => StoreItemCategory::tryFrom($request->string('kategori')->toString()),
            'color' => $request->string('warna')->trim()->toString(),
            'search' => $request->string('q')->trim()->toString(),
            'lowStock' => $request->boolean('menipis'),
        ];
    }

    public function create(Request $request): View
    {
        $category = StoreItemCategory::tryFrom($request->string('kategori')->toString()) ?? StoreItemCategory::Keycap;

        return view('admin.store.items.form', [
            'item' => new StoreItem(['category' => $category, 'is_active' => true, 'stock' => 0, 'min_stock' => 5, 'filament_grams' => 0]),
            'categories' => StoreItemCategory::cases(),
            'lots' => FilamentLot::activeRolls(),
            'colors' => StoreItem::query()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = DB::transaction(function () use ($request) {
            $data = $request->itemData();
            $lot = $request->filamentLot();
            $letterLot = $request->letterFilamentLot();
            $item = new StoreItem([...$data, 'stock' => 0]);

            if ($lot) {
                $item->purchase_price = $item->unitCostFrom($lot, $item->hasLetterColor() ? $letterLot : null);
            }

            $item->save();

            if ($data['stock'] > 0) {
                $item->produce($data['stock'], $request->user(), $lot, notes: 'Stok awal', letterLot: $letterLot);
            }

            return $item;
        });

        return to_route('admin.store.items.index')->with('status', "{$item->label()} berhasil ditambahkan.");
    }

    public function edit(StoreItem $item): View
    {
        return view('admin.store.items.form', [
            'item' => $item,
            'movements' => $item->movements()->with('sale')->latest()->latest('id')->take(20)->get(),
            'categories' => StoreItemCategory::cases(),
            'lots' => FilamentLot::activeRolls(),
            'colors' => StoreItem::query()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
        ]);
    }

    public function update(StoreItemRequest $request, StoreItem $item): RedirectResponse
    {
        $item->update($request->itemData());

        return to_route('admin.store.items.index')->with('status', "{$item->label()} berhasil disimpan.");
    }

    /**
     * Riwayat penjualan tetap utuh karena baris struk menyimpan nama item sendiri.
     */
    public function destroy(StoreItem $item): RedirectResponse
    {
        $item->delete();

        return to_route('admin.store.items.index')->with('status', "{$item->label()} dihapus.");
    }
}
