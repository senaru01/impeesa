<?php

namespace App\Http\Requests;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * Unggahan foto ke galeri pribadi (milik sendiri atau milik member) maupun Galeri Bersama.
 * Hak akses ditentukan oleh middleware route masing-masing; semua member boleh mengirim
 * ke Galeri Bersama, tetapi kiriman member baru tampil setelah disetujui.
 */
class PhotoUploadRequest extends FormRequest
{
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
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:150'],
            'taken_on' => ['nullable', 'date'],
            'mountain_name' => PhotoLocationRequest::mountainRules(),
        ];
    }

    /**
     * Simpan semua foto ke disk privat. Pemilik null berarti foto masuk Galeri Bersama.
     *
     * @return int Jumlah foto yang tersimpan.
     */
    public function storePhotos(?User $owner): int
    {
        DB::transaction(function () use ($owner) {
            foreach ($this->file('photos') as $file) {
                Photo::storeUpload($file, $this->user(), $owner, attributes: [
                    'caption' => $this->validated('caption'),
                    'taken_on' => $this->validated('taken_on'),
                    'mountain_name' => $this->validated('mountain_name'),
                ]);
            }
        });

        return count($this->file('photos'));
    }
}
