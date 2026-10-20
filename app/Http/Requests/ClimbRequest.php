<?php

namespace App\Http\Requests;

use App\Enums\ClimbStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClimbRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $climb = $this->route('climb');

        return $climb === null || $this->user()->can('update', $climb);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mountain_name' => ['required', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:100'],
            'elevation' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'started_on' => ['required', 'date'],
            'finished_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'status' => ['required', Rule::enum(ClimbStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'participants' => ['nullable', 'array', 'max:30'],
            'participants.*' => [
                'integer',
                'distinct',
                Rule::notIn([$this->user()->id]),
                Rule::exists('users', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * Data pendakian tanpa foto dan daftar peserta.
     *
     * @return array<string, mixed>
     */
    public function climbData(): array
    {
        return $this->safe()->except(['photos', 'participants']);
    }

    /**
     * @return list<int>
     */
    public function participantIds(): array
    {
        return array_map('intval', $this->validated('participants') ?? []);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'participants.*.exists' => 'Anggota yang dipilih tidak ditemukan atau sudah nonaktif.',
            'participants.*.not_in' => 'Kamu tidak perlu mengajak dirimu sendiri.',
        ];
    }
}
