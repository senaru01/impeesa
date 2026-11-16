<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class FilamentLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'color' => Str::title(trim((string) $this->input('color'))),
            'material' => trim((string) $this->input('material')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'material' => ['required', 'string', 'max:20'],
            'color' => ['required', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:60'],
            'quantity' => [$this->isMethod('POST') ? 'required' : 'exclude', 'integer', 'min:1', 'max:100'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:100000'],
            'remaining_grams' => [$this->isMethod('POST') ? 'exclude' : 'required', 'numeric', 'min:0', 'lte:weight_grams'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'purchased_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'material' => 'jenis filament',
            'color' => 'warna',
            'brand' => 'merek',
            'quantity' => 'jumlah roll',
            'weight_grams' => 'berat per roll',
            'remaining_grams' => 'sisa filament',
            'price' => 'harga per roll',
            'purchased_on' => 'tanggal beli',
            'notes' => 'catatan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lotData(): array
    {
        return collect($this->validated())->except('quantity')->all();
    }
}
