<?php

namespace App\Models;

use Database\Factories\WeightLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recorded_on', 'weight_kg', 'height_cm', 'notes'])]
class WeightLog extends Model
{
    /** @use HasFactory<WeightLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'weight_kg' => 'float',
            'height_cm' => 'integer',
        ];
    }

    /**
     * Indeks massa tubuh; butuh tinggi badan.
     */
    public function bmi(?int $heightCm = null): ?float
    {
        $heightCm ??= $this->height_cm;

        if (! $heightCm) {
            return null;
        }

        return round($this->weight_kg / (($heightCm / 100) ** 2), 1);
    }

    /**
     * Kategori IMT menurut Kemenkes RI.
     */
    public static function bmiCategory(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'Kurus',
            $bmi <= 25.0 => 'Normal',
            $bmi <= 27.0 => 'Gemuk',
            default => 'Obesitas',
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
