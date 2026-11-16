<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemBatchRequest;
use App\Models\StoreItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StoreItemBatchController extends Controller
{
    /**
     * Buat satu set keycap (A-Z, 0-9) atau base cap (1-10 huruf) sekaligus untuk satu warna.
     */
    public function __invoke(StoreItemBatchRequest $request): RedirectResponse
    {
        $rows = $request->itemsData();
        $lot = $request->filamentLot();
        $letterLot = $request->letterFilamentLot();

        [$created, $skipped] = DB::transaction(function () use ($rows, $request, $lot, $letterLot) {
            $created = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $exists = StoreItem::query()
                    ->where('category', $row['category'])
                    ->where('name', $row['name'])
                    ->where('color', $row['color'])
                    ->where('letter_color', $row['letter_color'])
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $item = new StoreItem([...$row, 'stock' => 0]);

                if ($lot) {
                    $item->purchase_price = $item->unitCostFrom($lot, $item->hasLetterColor() ? $letterLot : null);
                }

                $item->save();
                $created++;

                if ($row['stock'] > 0) {
                    $item->produce($row['stock'], $request->user(), $lot?->refresh(), notes: 'Stok awal', letterLot: $letterLot?->refresh());
                }
            }

            return [$created, $skipped];
        });

        $message = "{$created} item dibuat.".($skipped > 0 ? " {$skipped} item dilewati karena sudah ada." : '');

        return to_route('admin.store.items.index')->with('status', $message);
    }
}
