<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\FilamentLot;
use App\Models\FilamentWaste;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StoreItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Riwayat penjualan dan arus kas per bulan.
     */
    public function index(Request $request): View
    {
        $month = rescue(
            fn () => CarbonImmutable::createFromFormat('Y-m', $request->string('bulan')->toString())->startOfMonth(),
            fn () => CarbonImmutable::today()->startOfMonth(),
            report: false,
        );
        $range = [$month, $month->endOfMonth()];

        $sales = Sale::query()->whereBetween('created_at', $range);
        $production = StockMovement::query()
            ->with('item')
            ->where('type', StockMovementType::Production)
            ->whereBetween('created_at', $range);

        $revenue = (int) (clone $sales)->sum('total');
        $cost = (int) (clone $sales)->sum('total_cost');
        $productionMovements = (clone $production)->with(['filamentLot', 'letterFilamentLot'])->latest()->latest('id')->get();
        $goodsPurchases = $productionMovements->sum(fn (StockMovement $movement) => $movement->cashOut());
        $filamentLots = FilamentLot::query()->whereBetween('purchased_on', [$month->toDateString(), $month->endOfMonth()->toDateString()])->latest('purchased_on')->get();
        $filamentPurchases = (int) $filamentLots->sum('price');
        $wastes = FilamentWaste::query()->whereBetween('created_at', $range);

        return view('admin.store.sales.index', [
            'month' => $month,
            'summary' => [
                'count' => (clone $sales)->count(),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
                'filamentSold' => (float) (clone $sales)->sum('filament_grams'),
                'filamentPurchases' => $filamentPurchases,
                'goodsPurchases' => $goodsPurchases,
                'cashOut' => $filamentPurchases + $goodsPurchases,
                'filamentProduced' => round($productionMovements->sum(fn (StockMovement $movement) => $movement->totalFilamentGrams()), 2),
                'netCash' => $revenue - $filamentPurchases - $goodsPurchases,
                'wastedGrams' => (float) (clone $wastes)->sum('grams'),
                'wastedValue' => (int) (clone $wastes)->sum('value'),
            ],
            'sales' => (clone $sales)->with('cashier')->withCount('lines')->latest()->latest('id')->paginate(30)->withQueryString(),
            'productionMovements' => $productionMovements->take(20),
            'lowStockItems' => StoreItem::query()->active()->lowStock()->orderBy('stock')->catalogOrder()->take(30)->get(),
            'filamentLots' => $filamentLots,
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $sale = Sale::checkout(
            $request->user(),
            $request->session()->get(Sale::CART_SESSION_KEY, []),
            $request->validated(),
        );

        $request->session()->forget(Sale::CART_SESSION_KEY);

        return to_route('admin.store.sales.show', $sale)->with('status', "Transaksi {$sale->number} tersimpan. Stok sudah dikurangi.");
    }

    public function show(Sale $sale): View
    {
        $sale->load(['lines', 'cashier']);

        return view('admin.store.sales.show', ['sale' => $sale]);
    }

    /**
     * Struk siap cetak untuk printer thermal 58 mm.
     */
    public function receipt(Sale $sale): View
    {
        $sale->load(['lines', 'cashier']);

        return view('admin.store.sales.receipt', ['sale' => $sale]);
    }
}
