<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\FilamentLot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RestockRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'restock';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([StockMovementType::Production->value, StockMovementType::Adjustment->value])],
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'filament_lot_id' => ['nullable', 'integer', 'exists:filament_lots,id'],
            'letter_filament_lot_id' => ['nullable', 'integer', 'exists:filament_lots,id'],
            'unit_cost' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Lot filament yang dipakai mencetak; hanya berlaku untuk produksi.
     */
    public function filamentLot(): ?FilamentLot
    {
        if ($this->input('type') !== StockMovementType::Production->value || ! $this->filled('filament_lot_id')) {
            return null;
        }

        return FilamentLot::find($this->integer('filament_lot_id'));
    }

    /**
     * Roll untuk warna huruf keycap dua warna; hanya dipakai bila roll utama juga dipilih.
     */
    public function letterFilamentLot(): ?FilamentLot
    {
        if (! $this->filamentLot() || ! $this->filled('letter_filament_lot_id') || ! $this->route('item')->hasLetterColor()) {
            return null;
        }

        return FilamentLot::find($this->integer('letter_filament_lot_id'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis',
            'quantity' => 'jumlah',
            'unit_cost' => 'biaya per pcs',
            'filament_lot_id' => 'filament',
            'letter_filament_lot_id' => 'roll huruf',
            'notes' => 'catatan',
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

                $quantity = $this->integer('quantity');

                if ($this->input('type') === StockMovementType::Production->value && $quantity < 0) {
                    $validator->errors()->add('quantity', 'Jumlah produksi harus lebih dari 0. Pakai "Koreksi stok" untuk mengurangi.');
                }

                if ($this->filamentLot() && $this->route('item')->filament_grams <= 0) {
                    $validator->errors()->add('filament_lot_id', 'Gram filament item ini masih 0. Isi dulu lewat tombol Ubah supaya modal dan pemakaian roll terhitung.');
                }

                if ($this->route('item')->stock + $quantity < 0) {
                    $validator->errors()->add('quantity', 'Stok tidak boleh kurang dari 0.');
                }

                $demand = $this->route('item')->filamentDemand(max(0, $quantity), $this->filamentLot(), $this->letterFilamentLot());

                foreach (FilamentLot::shortagesFor($demand) as $message) {
                    $validator->errors()->add('filament_lot_id', $message);
                }
            },
        ];
    }
}
