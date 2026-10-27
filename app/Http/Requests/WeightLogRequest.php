<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WeightLogRequest extends FormRequest
{
    /**
     * Error formulir berat badan dipisah dari formulir lari di halaman yang sama.
     *
     * @var string
     */
    protected $errorBag = 'weight';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'min:20', 'max:300'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recorded_on' => 'tanggal',
            'weight_kg' => 'berat badan',
            'height_cm' => 'tinggi badan',
            'notes' => 'catatan',
        ];
    }
}
