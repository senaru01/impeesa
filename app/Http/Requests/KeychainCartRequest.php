<?php

namespace App\Http\Requests;

use App\Enums\StoreItemCategory;
use App\Models\Sale;
use App\Models\StoreItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KeychainCartRequest extends FormRequest
{
    /**
     * Error formulir rakit gantungan kunci dipisah dari formulir lain di halaman kasir.
     *
     * @var string
     */
    protected $errorBag = 'keychain';

    /**
     * Item hasil rakitan: id item => jumlah, urut base, keycap, switch, aksesoris.
     *
     * @var array<int, int>
     */
    private array $resolvedItems = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'text' => Str::upper((string) preg_replace('/\s+/', '', (string) $this->input('text'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:'.StoreItem::MAX_BASE_SIZE, 'regex:/^[A-Z0-9]+$/'],
            'keycap_color' => ['required', 'string', 'max:81'],
            'letter_colors' => ['nullable', 'array'],
            'letter_colors.*' => ['nullable', 'string', 'max:81'],
            'base_color' => ['required', 'string', 'max:40'],
            'switch_item_id' => ['required', 'integer', Rule::exists('store_items', 'id')->where('category', StoreItemCategory::Switch->value)],
            'accessory_item_id' => ['nullable', 'integer', Rule::exists('store_items', 'id')->where('category', StoreItemCategory::Accessory->value)],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'text' => 'tulisan',
            'keycap_color' => 'warna keycap',
            'base_color' => 'warna base',
            'switch_item_id' => 'switch',
            'accessory_item_id' => 'aksesoris',
            'quantity' => 'jumlah',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.regex' => 'Tulisan hanya boleh huruf A-Z dan angka 0-9.',
            'text.max' => 'Tulisan maksimal :max karakter (base cap terpanjang :max huruf).',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->resolveItems($validator);
            },
        ];
    }

    /**
     * Entri keranjang siap disimpan ke session.
     *
     * @return array{label: string, items: array<int, int>}
     */
    public function cartEntry(): array
    {
        $quantity = $this->integer('quantity');
        $label = 'Gantungan kunci "'.$this->input('text').'"'.($quantity > 1 ? " ×{$quantity}" : '');

        return ['label' => $label, 'items' => $this->resolvedItems];
    }

    /**
     * Cari base cap, keycap per huruf, switch, dan aksesoris lalu cek stoknya.
     */
    private function resolveItems(Validator $validator): void
    {
        $text = (string) $this->input('text');
        $characters = str_split($text);
        $quantity = $this->integer('quantity');
        $defaultColor = (string) $this->input('keycap_color');
        $letterColors = (array) $this->input('letter_colors', []);

        $base = StoreItem::query()->active()
            ->where('category', StoreItemCategory::BaseCap)
            ->where('size', count($characters))
            ->where('color', $this->input('base_color'))
            ->first();

        if (! $base) {
            $validator->errors()->add('base_color', 'Base cap '.count($characters).' huruf warna '.$this->input('base_color').' belum ada di daftar item.');
        }

        $keycaps = StoreItem::query()->active()
            ->where('category', StoreItemCategory::Keycap)
            ->whereIn('code', array_unique($characters))
            ->get();

        $keycapCounts = [];

        foreach ($characters as $position => $character) {
            $color = filled($letterColors[$position] ?? null) ? (string) $letterColors[$position] : $defaultColor;
            $keycap = $this->findKeycap($keycaps, $character, $color);

            if (! $keycap) {
                $validator->errors()->add('text', "Keycap \"{$character}\" warna ".StoreItem::variantLabelFor($color).' belum ada di daftar item.');

                continue;
            }

            $keycapCounts[$keycap->id] = ($keycapCounts[$keycap->id] ?? 0) + 1;
        }

        $switch = StoreItem::query()->active()->find($this->integer('switch_item_id'));
        $accessory = $this->filled('accessory_item_id') ? StoreItem::query()->active()->find($this->integer('accessory_item_id')) : null;

        if (! $switch) {
            $validator->errors()->add('switch_item_id', 'Switch yang dipilih sedang nonaktif.');
        }

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $items = [$base->id => $quantity];

        foreach ($keycapCounts as $itemId => $count) {
            $items[$itemId] = $count * $quantity;
        }

        $items[$switch->id] = count($characters) * $quantity;

        if ($accessory) {
            $items[$accessory->id] = $quantity;
        }

        $this->resolvedItems = $items;
        $this->checkStock($validator, $items);
    }

    /**
     * @param  EloquentCollection<int, StoreItem>  $keycaps
     */
    private function findKeycap(EloquentCollection $keycaps, string $character, string $color): ?StoreItem
    {
        return $keycaps->first(fn (StoreItem $item) => $item->code === $character && Str::lower($item->variantKey()) === Str::lower($color));
    }

    /**
     * Stok harus cukup untuk rakitan ini ditambah isi keranjang yang sudah ada.
     *
     * @param  array<int, int>  $items
     */
    private function checkStock(Validator $validator, array $items): void
    {
        $cart = $this->session()->get(Sale::CART_SESSION_KEY, []);
        $summary = Sale::summarizeCart([...$cart, ['label' => 'baru', 'items' => $items]]);

        foreach ($summary['shortages'] as $shortage) {
            $validator->errors()->add('stock', $shortage);
        }
    }
}
