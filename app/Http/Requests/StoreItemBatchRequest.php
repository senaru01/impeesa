<?php

namespace App\Http\Requests;

use App\Enums\StoreItemCategory;
use App\Models\FilamentLot;
use App\Models\StoreItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreItemBatchRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'batch';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'color' => Str::title(trim((string) $this->input('color'))),
            'letter_color' => $this->filled('letter_color') ? Str::title(trim((string) $this->input('letter_color'))) : null,
            'codes' => Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('codes', StoreItem::KEYCAP_CODES))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isBase = $this->input('category') === StoreItemCategory::BaseCap->value;

        return [
            'category' => ['required', Rule::in([StoreItemCategory::Keycap->value, StoreItemCategory::BaseCap->value])],
            'color' => ['required', 'string', 'max:40'],
            'letter_color' => [$isBase ? 'exclude' : 'nullable', 'string', 'max:40'],
            'codes' => [$isBase ? 'exclude' : 'required', 'string', 'max:72'],
            'size_from' => [$isBase ? 'required' : 'exclude', 'integer', 'min:1', 'max:'.StoreItem::MAX_BASE_SIZE],
            'size_to' => [$isBase ? 'required' : 'exclude', 'integer', 'gte:size_from', 'max:'.StoreItem::MAX_BASE_SIZE],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'filament_lot_id' => ['nullable', 'integer', 'exists:filament_lots,id'],
            'letter_filament_lot_id' => [$isBase || ! $this->filled('filament_lot_id') ? 'exclude' : 'nullable', 'integer', 'exists:filament_lots,id'],
            'purchase_price' => [$this->filled('filament_lot_id') ? 'nullable' : 'required', 'integer', 'min:0', 'max:100000000'],
            'fixed_cost' => ['required', 'integer', 'min:0', 'max:100000000'],
            'selling_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'filament_grams' => ['required', 'numeric', 'min:0', 'max:99999'],
            'purchase_price_step' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'selling_price_step' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'filament_grams_step' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'color' => 'warna',
            'letter_color' => 'warna huruf',
            'letter_filament_lot_id' => 'roll huruf',
            'codes' => 'daftar karakter',
            'size_from' => 'ukuran awal',
            'size_to' => 'ukuran akhir',
            'stock' => 'stok awal',
            'min_stock' => 'stok minimum',
            'purchase_price' => 'harga beli/modal',
            'fixed_cost' => 'biaya tetap',
            'filament_lot_id' => 'filament',
            'selling_price' => 'harga jual',
            'filament_grams' => 'filament',
        ];
    }

    public function filamentLot(): ?FilamentLot
    {
        return $this->filled('filament_lot_id') ? FilamentLot::find($this->integer('filament_lot_id')) : null;
    }

    /**
     * Roll untuk warna huruf keycap dua warna (5% gram).
     */
    public function letterFilamentLot(): ?FilamentLot
    {
        return $this->filamentLot() && $this->filled('letter_filament_lot_id') ? FilamentLot::find($this->integer('letter_filament_lot_id')) : null;
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

                if ($this->filled('filament_lot_id') && (float) $this->input('filament_grams') <= 0) {
                    $validator->errors()->add('filament_grams', 'Isi gram filament per pcs (lebih dari 0) karena dicetak dari roll.');

                    return;
                }

                $lot = $this->filamentLot();

                if (! $lot) {
                    return;
                }

                $letterLot = $this->letterFilamentLot();
                $demand = [];

                foreach ($this->itemsData() as $row) {
                    foreach ((new StoreItem($row))->filamentDemand($row['stock'], $lot, $letterLot) as $lotId => $grams) {
                        $demand[$lotId] = ($demand[$lotId] ?? 0) + $grams;
                    }
                }

                foreach (FilamentLot::shortagesFor($demand) as $message) {
                    $validator->errors()->add('filament_lot_id', $message.' (stok awal satu set)');
                }
            },
        ];
    }

    /**
     * Daftar item yang akan dibuat. Untuk base cap, harga & filament naik per tambahan huruf.
     *
     * @return list<array<string, mixed>>
     */
    public function itemsData(): array
    {
        $data = $this->validated();
        $category = StoreItemCategory::from($data['category']);
        $shared = [
            'category' => $category,
            'color' => $data['color'],
            'letter_color' => $category === StoreItemCategory::Keycap ? ($data['letter_color'] ?? null) : null,
            'stock' => (int) $data['stock'],
            'min_stock' => (int) $data['min_stock'],
            'fixed_cost' => (int) $data['fixed_cost'],
            'is_active' => true,
        ];

        if ($category === StoreItemCategory::Keycap) {
            return collect(array_unique(str_split($data['codes'])))
                ->map(fn (string $code) => [
                    ...$shared,
                    'code' => $code,
                    'size' => null,
                    'name' => StoreItem::defaultName($category, $code, null),
                    'purchase_price' => (int) ($data['purchase_price'] ?? 0),
                    'selling_price' => (int) $data['selling_price'],
                    'filament_grams' => (float) $data['filament_grams'],
                ])
                ->values()
                ->all();
        }

        return collect(range((int) $data['size_from'], (int) $data['size_to']))
            ->map(function (int $size) use ($data, $shared, $category) {
                $extra = $size - (int) $data['size_from'];

                return [
                    ...$shared,
                    'code' => null,
                    'size' => $size,
                    'name' => StoreItem::defaultName($category, null, $size),
                    'purchase_price' => (int) ($data['purchase_price'] ?? 0) + $extra * (int) ($data['purchase_price_step'] ?? 0),
                    'selling_price' => (int) $data['selling_price'] + $extra * (int) ($data['selling_price_step'] ?? 0),
                    'filament_grams' => round((float) $data['filament_grams'] + $extra * (float) ($data['filament_grams_step'] ?? 0), 2),
                ];
            })
            ->all();
    }
}
