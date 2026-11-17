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

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? Str::upper(trim((string) $this->input('code'))) : null,
            'color' => $this->filled('color') ? Str::title(trim((string) $this->input('color'))) : null,
            'letter_color' => $this->filled('letter_color') ? Str::title(trim((string) $this->input('letter_color'))) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(StoreItemCategory::class)],
            'name' => [Rule::requiredIf(! in_array($this->input('category'), [StoreItemCategory::Keycap->value, StoreItemCategory::BaseCap->value], true)), 'nullable', 'string', 'max:120'],
            'code' => [Rule::requiredIf($this->input('category') === StoreItemCategory::Keycap->value), 'nullable', 'string', 'max:4'],
            'size' => [Rule::requiredIf($this->input('category') === StoreItemCategory::BaseCap->value), 'nullable', 'integer', 'min:1', 'max:'.StoreItem::MAX_BASE_SIZE],
            'color' => [Rule::requiredIf(in_array($this->input('category'), [StoreItemCategory::Keycap->value, StoreItemCategory::BaseCap->value], true)), 'nullable', 'string', 'max:40'],
            'letter_color' => ['nullable', 'string', 'max:40'],
            'stock' => [$this->isMethod('POST') ? 'required' : 'exclude', 'integer', 'min:0', 'max:100000'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'filament_lot_id' => [$this->isMethod('POST') ? 'nullable' : 'exclude', 'integer', 'exists:filament_lots,id'],
            'letter_filament_lot_id' => [$this->isMethod('POST') && $this->filled('filament_lot_id') ? 'nullable' : 'exclude', 'integer', 'exists:filament_lots,id'],
            'purchase_price' => [$this->filled('filament_lot_id') && $this->isMethod('POST') ? 'nullable' : 'required', 'integer', 'min:0', 'max:100000000'],
            'fixed_cost' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'selling_price' => [$this->input('category') === StoreItemCategory::BaseCap->value ? 'nullable' : 'required', 'integer', 'min:0', 'max:100000000'],
            'filament_grams' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category' => 'kategori',
            'name' => 'nama item',
            'code' => 'huruf/angka keycap',
            'size' => 'jumlah huruf base',
            'color' => 'warna',
            'letter_color' => 'warna huruf',
            'letter_filament_lot_id' => 'roll huruf',
            'stock' => 'stok awal',
            'min_stock' => 'stok minimum',
            'purchase_price' => 'harga beli/modal',
            'fixed_cost' => 'biaya tetap',
            'filament_lot_id' => 'filament',
            'selling_price' => 'harga jual',
            'filament_grams' => 'filament',
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

                $data = $this->itemData();
                $duplicate = StoreItem::query()
                    ->where('category', $data['category'])
                    ->where('name', $data['name'])
                    ->where('color', $data['color'])
                    ->where('letter_color', $data['letter_color'])
                    ->when($this->route('item'), fn ($query, StoreItem $item) => $query->whereKeyNot($item->id))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('name', 'Item dengan nama dan warna yang sama sudah ada.');
                }

                if ($this->filamentLot() && (float) $this->input('filament_grams') <= 0) {
                    $validator->errors()->add('filament_grams', 'Isi gram filament per pcs (lebih dari 0) karena dicetak dari roll.');

                    return;
                }

                $demand = (new StoreItem($data))->filamentDemand($this->integer('stock'), $this->filamentLot(), $this->letterFilamentLot());

                foreach (FilamentLot::shortagesFor($demand) as $message) {
                    $validator->errors()->add('filament_lot_id', $message.' (stok awal)');
                }
            },
        ];
    }

    /**
     * Lot filament untuk mencetak stok awal (hanya saat item baru dibuat).
     */
    public function filamentLot(): ?FilamentLot
    {
        return $this->isMethod('POST') && $this->filled('filament_lot_id') ? FilamentLot::find($this->integer('filament_lot_id')) : null;
    }

    /**
     * Roll untuk warna huruf keycap dua warna (5% gram).
     */
    public function letterFilamentLot(): ?FilamentLot
    {
        return $this->filamentLot() && $this->filled('letter_filament_lot_id') ? FilamentLot::find($this->integer('letter_filament_lot_id')) : null;
    }

    /**
     * Data item siap disimpan; nama keycap/base dibuat otomatis bila kosong.
     *
     * @return array<string, mixed>
     */
    public function itemData(): array
    {
        $data = collect($this->validated())->except(['filament_lot_id', 'letter_filament_lot_id'])->all();
        $data['purchase_price'] = (int) ($data['purchase_price'] ?? 0);
        $data['fixed_cost'] = (int) ($data['fixed_cost'] ?? 0);
        $data['filament_grams'] = (float) ($data['filament_grams'] ?? 0);
        $category = StoreItemCategory::from($data['category']);

        $data['code'] = $category === StoreItemCategory::Keycap ? $data['code'] : null;
        $data['letter_color'] = $category === StoreItemCategory::Keycap ? ($data['letter_color'] ?? null) : null;
        $data['size'] = $category === StoreItemCategory::BaseCap ? (int) $data['size'] : null;
        $data['selling_price'] = isset($data['selling_price']) ? (int) $data['selling_price'] : StoreItem::baseCapPrice((int) $data['size']);
        $data['name'] = filled($data['name'] ?? null) ? $data['name'] : StoreItem::defaultName($category, $data['code'], $data['size']);

        return $data;
    }
}
