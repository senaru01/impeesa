<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunRequest extends FormRequest
{
    /**
     * Durasi ditulis "mm:ss" atau "j:mm:ss".
     */
    private const DURATION_PATTERN = '/^(?:(\d{1,2}):)?(\d{1,3}):([0-5]\d)$/';

    /**
     * Error formulir lari dipisah dari formulir berat badan di halaman yang sama.
     *
     * @var string
     */
    protected $errorBag = 'run';

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
            'run_on' => ['required', 'date', 'before_or_equal:today'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:300'],
            'duration' => ['required', 'string', 'regex:'.self::DURATION_PATTERN, 'not_in:0:00,00:00,0:00:00,00:00:00'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Data lari dengan durasi yang sudah diubah ke detik.
     *
     * @return array{run_on: string, distance_km: string, duration_seconds: int, notes: ?string}
     */
    public function runData(): array
    {
        preg_match(self::DURATION_PATTERN, $this->validated('duration'), $parts);

        return [
            'run_on' => $this->validated('run_on'),
            'distance_km' => $this->validated('distance_km'),
            'duration_seconds' => (int) $parts[1] * 3600 + (int) $parts[2] * 60 + (int) $parts[3],
            'notes' => $this->validated('notes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'run_on' => 'tanggal',
            'distance_km' => 'jarak',
            'duration' => 'durasi',
            'notes' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'duration.regex' => 'Tulis durasi seperti 35:20 (menit:detik) atau 1:05:30 (jam:menit:detik).',
            'duration.not_in' => 'Durasi tidak boleh nol.',
        ];
    }
}
