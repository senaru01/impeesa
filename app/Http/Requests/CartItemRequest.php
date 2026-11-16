<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Models\StoreItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CartItemRequest extends FormRequest
{
    /**
     * Error formulir item satuan dipisah dari formulir lain di halaman kasir.
     *
     * @var string
     */
    protected $errorBag = 'cartItem';

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
            'store_item_id' => ['required', 'integer', Rule::exists('store_items', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'store_item_id' => 'item',
            'quantity' => 'jumlah',
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

                $cart = $this->session()->get(Sale::CART_SESSION_KEY, []);
                $summary = Sale::summarizeCart([...$cart, $this->cartEntry()]);

                foreach ($summary['shortages'] as $shortage) {
                    $validator->errors()->add('quantity', $shortage);
                }
            },
        ];
    }

    /**
     * @return array{label: string, items: array<int, int>}
     */
    public function cartEntry(): array
    {
        $item = StoreItem::findOrFail($this->integer('store_item_id'));
        $quantity = $this->integer('quantity');

        return [
            'label' => $item->label().($quantity > 1 ? " ×{$quantity}" : ''),
            'items' => [$item->id => $quantity],
        ];
    }
}
