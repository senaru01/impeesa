<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemBulkRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'bulk';

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
            'action' => ['required', 'in:reset_stock,delete'],
            'all_items' => ['boolean'],
            'ids' => ['required_unless:all_items,1', 'array'],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'action' => 'aksi',
            'ids' => 'item terpilih',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required_unless' => 'Centang minimal satu item, atau pilih "semua item".',
        ];
    }
}
