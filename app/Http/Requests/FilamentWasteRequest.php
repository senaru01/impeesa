<?php

namespace App\Http\Requests;

use App\Enums\FilamentWasteReason;
use App\Models\FilamentLot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilamentWasteRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'waste';

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
            'filament_lot_id' => ['required', 'integer', Rule::exists('filament_lots', 'id')->where(fn ($query) => $query->where('remaining_grams', '>', 0))],
            'reason' => ['required', Rule::enum(FilamentWasteReason::class)],
            'grams' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'filament_lot_id' => 'roll',
            'reason' => 'alasan',
            'grams' => 'gram terbuang',
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

                $lot = $this->lot();

                if ($this->wastedGrams() > (float) $lot->remaining_grams) {
                    $left = number_format($lot->remaining_grams, 1, ',', '.');
                    $validator->errors()->add('grams', "Sisa {$lot->label()} hanya {$left} g.");
                }
            },
        ];
    }

    public function lot(): FilamentLot
    {
        return FilamentLot::findOrFail($this->integer('filament_lot_id'));
    }

    /**
     * Gram yang dibuang; kosong berarti seluruh sisa roll.
     */
    public function wastedGrams(): float
    {
        return $this->filled('grams') ? round((float) $this->input('grams'), 2) : (float) $this->lot()->remaining_grams;
    }
}
