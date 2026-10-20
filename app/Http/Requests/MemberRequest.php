<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Super admin membuat atau mengubah akun member, termasuk peran dan status akun.
 */
class MemberRequest extends ProfileRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->route('member');

        return [
            ...self::personalRules(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($member)],
            'password' => [$member ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            'member_number' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($member)],
            'position' => ['nullable', 'string', 'max:100'],
            'joined_on' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        return [
            ...parent::profileData(),
            'is_active' => $this->boolean('is_active'),
            ...array_filter(['password' => $this->validated('password')]),
        ];
    }
}
