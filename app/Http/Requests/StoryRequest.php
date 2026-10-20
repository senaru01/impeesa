<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $story = $this->route('story');

        return $story === null || $this->user()->can('update', $story);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'mountain_name' => ['nullable', 'string', 'max:100'],
            'climbed_on' => ['nullable', 'date'],
            'body' => ['required', 'string', 'min:50', 'max:5000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'is_public' => ['required', 'boolean'],
        ];
    }
}
