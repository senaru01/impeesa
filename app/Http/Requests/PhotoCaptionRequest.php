<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Keterangan baru untuk satu foto Galeri Bersama. Hak akses dicek lewat PhotoPolicy::rename.
 */
class PhotoCaptionRequest extends FormRequest
{
    /**
     * Error keterangan dipisah agar tidak tercampur dengan formulir unggah di halaman yang sama.
     *
     * @var string
     */
    protected $errorBag = 'caption';

    /**
     * Kembali ke kartu foto yang sedang diubah bila validasi gagal.
     */
    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#foto-'.$this->route('photo')->id;
    }

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
            'caption' => ['nullable', 'string', 'max:150'],
        ];
    }
}
