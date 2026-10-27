<?php

namespace App\Http\Requests;

use App\Models\Climb;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lokasi manual untuk foto tanpa pendakian. Kosongkan untuk menghapus lokasi.
 * Hak akses dicek lewat PhotoPolicy::locate di controller.
 */
class PhotoLocationRequest extends FormRequest
{
    /**
     * Error lokasi dipisah agar tidak tercampur dengan formulir unggah di halaman yang sama.
     *
     * @var string
     */
    protected $errorBag = 'location';

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
            'mountain_name' => self::mountainRules(),
        ];
    }

    /**
     * Aturan isian gunung foto; nama harus dikenali agar foto bisa tampil di peta.
     *
     * @return array<int, mixed>
     */
    public static function mountainRules(): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (Climb::locateMountain($value) === null) {
                    $fail('Gunung tidak dikenali. Pilih nama gunung dari daftar.');
                }
            },
        ];
    }
}
