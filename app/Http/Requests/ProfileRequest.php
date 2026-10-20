<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    /** Pilihan agama sesuai isian KTP. */
    public const RELIGIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan'];

    /** Pilihan status perkawinan sesuai isian KTP. */
    public const MARITAL_STATUSES = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];

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
            ...self::personalRules(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user())],
        ];
    }

    /**
     * Data diri yang boleh diubah sendiri oleh member maupun oleh super admin.
     *
     * @return array<string, array<mixed>>
     */
    public static function personalRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'medications' => ['nullable', 'string', 'max:500'],
            'nik' => ['nullable', 'digits:16'],
            'religion' => ['nullable', Rule::in(self::RELIGIONS)],
            'marital_status' => ['nullable', Rule::in(self::MARITAL_STATUSES)],
            'occupation' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:30'],
            'id_card_address' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'signature_place' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Data yang aman disimpan langsung ke model (tanpa file avatar).
     *
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        return $this->safe()->except(['avatar', 'password', 'password_confirmation']);
    }
}
