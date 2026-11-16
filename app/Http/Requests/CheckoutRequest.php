<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'checkout';

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
            'customer_name' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nama pembeli',
            'payment_method' => 'metode bayar',
            'paid_amount' => 'uang dibayar',
            'notes' => 'catatan',
        ];
    }
}
