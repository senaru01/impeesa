<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\StoreItemCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Http\Requests\KeychainCartRequest;
use App\Models\Sale;
use App\Models\StoreItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreCartController extends Controller
{
    /**
     * Halaman kasir: rakit gantungan kunci, isi keranjang, lalu checkout.
     */
    public function index(Request $request): View
    {
        $items = StoreItem::query()->active()->catalogOrder()->get();
        $todaySales = Sale::query()->whereDate('created_at', today());

        return view('admin.store.pos', [
            'cart' => Sale::summarizeCart($request->session()->get(Sale::CART_SESSION_KEY, [])),
            'items' => $items,
            'keycapColors' => $this->keycapVariants($items),
            'baseColors' => $this->colorsOf($items, StoreItemCategory::BaseCap),
            'switches' => $items->where('category', StoreItemCategory::Switch)->values(),
            'accessories' => $items->where('category', StoreItemCategory::Accessory)->values(),
            'catalog' => $this->catalog($items),
            'paymentMethods' => PaymentMethod::cases(),
            'today' => [
                'count' => (clone $todaySales)->count(),
                'total' => (int) (clone $todaySales)->sum('total'),
            ],
        ]);
    }

    public function storeKeychain(KeychainCartRequest $request): RedirectResponse
    {
        $entry = $request->cartEntry();
        $request->session()->push(Sale::CART_SESSION_KEY, $entry);

        return to_route('admin.store.pos')->with('status', "{$entry['label']} masuk keranjang.");
    }

    public function storeItem(CartItemRequest $request): RedirectResponse
    {
        $entry = $request->cartEntry();
        $request->session()->push(Sale::CART_SESSION_KEY, $entry);

        return to_route('admin.store.pos')->with('status', "{$entry['label']} masuk keranjang.");
    }

    public function destroy(Request $request, int $index): RedirectResponse
    {
        $cart = $request->session()->get(Sale::CART_SESSION_KEY, []);
        unset($cart[$index]);
        $request->session()->put(Sale::CART_SESSION_KEY, array_values($cart));

        return to_route('admin.store.pos');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(Sale::CART_SESSION_KEY);

        return to_route('admin.store.pos')->with('status', 'Keranjang dikosongkan.');
    }

    /**
     * @param  EloquentCollection<int, StoreItem>  $items
     * @return list<string>
     */
    private function colorsOf(EloquentCollection $items, StoreItemCategory $category): array
    {
        return $items->where('category', $category)->pluck('color')->filter()->unique()->sort()->values()->all();
    }

    /**
     * Kombinasi warna keycap (cap + huruf) yang tersedia.
     *
     * @param  EloquentCollection<int, StoreItem>  $items
     * @return array<string, string> kunci varian => label
     */
    private function keycapVariants(EloquentCollection $items): array
    {
        return $items->where('category', StoreItemCategory::Keycap)
            ->filter(fn (StoreItem $item) => filled($item->color))
            ->mapWithKeys(fn (StoreItem $item) => [$item->variantKey() => $item->variantLabel()])
            ->sort()
            ->all();
    }

    /**
     * Harga & stok untuk estimasi langsung di browser sebelum masuk keranjang.
     *
     * @param  EloquentCollection<int, StoreItem>  $items
     * @return array{keycaps: array<string, array<string, array{price: int, stock: int}>>, bases: array<string, array<int, array{price: int, stock: int}>>, items: array<int, array{price: int, stock: int}>}
     */
    private function catalog(EloquentCollection $items): array
    {
        $catalog = ['keycaps' => [], 'bases' => [], 'items' => []];

        foreach ($items as $item) {
            $entry = ['price' => $item->selling_price, 'stock' => $item->stock];

            match ($item->category) {
                StoreItemCategory::Keycap => $catalog['keycaps'][$item->variantKey()][(string) $item->code] = $entry,
                StoreItemCategory::BaseCap => $catalog['bases'][(string) $item->color][(int) $item->size] = $entry,
                default => $catalog['items'][$item->id] = $entry,
            };
        }

        return $catalog;
    }
}
