<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RestockRequest;
use App\Models\StoreItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreRestockController extends Controller
{
    /**
     * Tambah stok hasil produksi/pembelian, atau koreksi stok setelah dihitung ulang.
     */
    public function __invoke(RestockRequest $request, StoreItem $item): RedirectResponse
    {
        $type = StockMovementType::from($request->input('type'));
        $quantity = $request->integer('quantity');

        DB::transaction(function () use ($request, $item, $type, $quantity) {
            if ($type === StockMovementType::Production) {
                $item->produce(
                    $quantity,
                    $request->user(),
                    $request->filamentLot(),
                    $request->filled('unit_cost') ? $request->integer('unit_cost') : null,
                    $request->input('notes'),
                    $request->letterFilamentLot(),
                );

                return;
            }

            $item->increment('stock', $quantity);
            $item->movements()->create([
                'user_id' => $request->user()->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => 0,
                'notes' => $request->input('notes'),
            ]);
        });

        $sign = $quantity > 0 ? '+' : '';
        $cost = $type === StockMovementType::Production ? ', modal Rp '.number_format($item->purchase_price, 0, ',', '.').'/pcs' : '';

        return back()->with('status', "Stok {$item->label()} {$sign}{$quantity}, sekarang {$item->stock}{$cost}.");
    }
}
