<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemBulkRequest;
use App\Models\StoreItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreItemBulkController extends Controller
{
    /**
     * Reset stok ke 0 atau hapus banyak item sekaligus, mis. setelah uji coba.
     */
    public function __invoke(StoreItemBulkRequest $request): RedirectResponse
    {
        $items = StoreItem::query()
            ->when(
                $request->boolean('all_items'),
                fn ($query) => $query->matchingFilters(StoreItemController::filtersFrom($request)),
                fn ($query) => $query->whereKey($request->input('ids', [])),
            )
            ->get();

        $count = DB::transaction(function () use ($request, $items) {
            if ($request->input('action') === 'delete') {
                StoreItem::query()->whereKey($items->modelKeys())->delete();

                return $items->count();
            }

            $changed = 0;

            foreach ($items->where('stock', '!=', 0) as $item) {
                $item->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => StockMovementType::Adjustment,
                    'quantity' => -$item->stock,
                    'unit_cost' => 0,
                    'notes' => 'Reset stok',
                ]);
                $item->update(['stock' => 0]);
                $changed++;
            }

            return $changed;
        });

        $message = $request->input('action') === 'delete'
            ? "{$count} item dihapus. Riwayat penjualan tetap tersimpan."
            : "Stok {$count} item direset ke 0.";

        return to_route('admin.store.items.index')->with('status', $message);
    }
}
